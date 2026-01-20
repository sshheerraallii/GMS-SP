<?php

namespace App\Services\Invoices;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;

class GenerateDraftInvoiceForEvent
{
    private const SECURE_PREMISES = 'Secure Premises LTD';

    private const PAYMENT_NOTES_SECURE_PREMISES =
        "Please make payment to this account.\n".
        "Secure premises LTD\n".
        "Sort Code: 30-99-50\n".
        "AC No: 17788163";

    public function __construct(
        private readonly InvoiceNumberGenerator $numberGenerator,
    ) {}

    public function handle(Event $event): Invoice
    {
        return DB::transaction(function () use ($event) {

            // Ensure supplier relation is available
            $event->loadMissing('supplier');

            // --- Snapshots / config (define BEFORE invoice create/rebuild) ---
            $eventName  = (string) $event->event_name;
            $chargeRate = (float) $event->charge_rate;

            $clientType = (string) $event->client_type; // VAT / NON_VAT
            $vatRate    = $this->vatRateFromClientType($clientType); // 0.20 or 0.00

            $shiftMode = Str::lower(trim((string) $event->shift_mode));
            if (!in_array($shiftMode, ['same', 'different'], true)) {
                $shiftMode = 'same';
            }

            $supplierName = $this->resolveSupplierName($event);
            $notes        = $this->paymentNotesForSupplier($supplierName);

            // --- Idempotency + rebuild draft invoices (B) ---
            $existing = Invoice::query()
                ->where('event_id', $event->id)
                ->first();

            if ($existing) {
                // Never regenerate non-draft invoices (issued/paid/voided)
                if ($existing->status !== 'draft') {
                    return $existing->load('lines');
                }

                // Draft invoice: rebuild lines + totals
                $existing->lines()->delete();

                $existing->update([
                    'event_name_snapshot'     => $eventName,
                    'client_type_snapshot'    => $clientType,
                    'charge_rate_snapshot'    => $chargeRate,
                    'vat_rate'                => $vatRate,
                    'supplier_name_snapshot'  => $supplierName,
                    'payment_notes'           => $notes,

                    'total_hours' => 0,
                    'subtotal'    => 0,
                    'vat_amount'  => 0,
                    'total'       => 0,
                ]);

                $invoice = $existing;
            } else {
                $invoiceNumber = $this->numberGenerator->nextForEventName($eventName);

                $invoice = Invoice::create([
                    'event_id'        => $event->id,
                    'invoice_number'  => $invoiceNumber,
                    'status'          => 'draft',

                    'event_name_snapshot'    => $eventName,
                    'client_type_snapshot'   => $clientType,
                    'charge_rate_snapshot'   => $chargeRate,
                    'vat_rate'               => $vatRate,
                    'supplier_name_snapshot' => $supplierName,
                    'payment_notes'          => $notes,

                    'total_hours' => 0,
                    'subtotal'    => 0,
                    'vat_amount'  => 0,
                    'total'       => 0,
                ]);
            }

            // --- Pull shifts ---
            $shifts = EventShift::query()
                ->where('event_id', $event->id)
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->get();

            if ($shifts->isEmpty()) {
                return $invoice->fresh('lines');
            }

            // --- Mini-Step 6: A2 handling (detect only, do not block) ---
            if ($shiftMode === 'same') {
                $slotCountByDate = [];

                foreach ($shifts as $s) {
                    $d = (string) $s->date;
                    $slotKey = (string) $s->start_time . '|' . (string) $s->end_time;
                    $slotCountByDate[$d] ??= [];
                    $slotCountByDate[$d][$slotKey] = true;
                }

                foreach ($slotCountByDate as $date => $slots) {
                    if (count($slots) > 1) {
                        Log::warning('Invoice generator: shift_mode=same but multiple slots found for date', [
                            'event_id'    => $event->id,
                            'date'        => $date,
                            'slot_count'  => count($slots),
                        ]);
                    }
                }
            }

            // --- Group shifts by (date + start_time + end_time) ---
            $groups = [];
            foreach ($shifts as $s) {
                $key = $s->date . '|' . $s->start_time . '|' . $s->end_time;

                $groups[$key] ??= [
                    'date'   => (string) $s->date,
                    'start'  => (string) $s->start_time,
                    'end'    => (string) $s->end_time,
                    'guards' => [],
                ];

                $groups[$key]['guards'][(string) $s->guard_id] = true;
            }

            // Stable ordering
            uasort($groups, fn ($a, $b) => strcmp($a['date'] . $a['start'], $b['date'] . $b['start']));

            // --- Create invoice lines + totals ---
            $lineNo     = 1;
            $subtotal   = 0.0;
            $totalHours = 0.0;

            foreach ($groups as $g) {
                $qty          = count($g['guards']);
                $hoursPerGuard = $this->hoursBetween($g['start'], $g['end']);
                $amount       = round($qty * $hoursPerGuard * $chargeRate, 2);

                $subtotal   += $amount;
                $totalHours += ($qty * $hoursPerGuard);

                InvoiceLine::create([
                    'invoice_id'   => $invoice->id,
                    'line_no'      => $lineNo++,
                    'service_date' => $g['date'],
                    'description'  => 'Provision of Guards',
                    'shift_start'  => $g['start'],
                    'shift_end'    => $g['end'],
                    'quantity'     => $qty,
                    'hours'        => $hoursPerGuard,
                    'rate'         => $chargeRate,
                    'amount'       => $amount,
                ]);
            }

            $vatAmount = round($subtotal * $vatRate, 2);
            $total     = round($subtotal + $vatAmount, 2);

            $invoice->update([
                'total_hours' => round($totalHours, 2),
                'subtotal'    => round($subtotal, 2),
                'vat_amount'  => $vatAmount,
                'total'       => $total,
            ]);

            return $invoice->fresh('lines');
        });
    }

    private function vatRateFromClientType(string $clientType): float
    {
        return Str::upper(trim($clientType)) === 'VAT' ? 0.20 : 0.00; // NON_VAT => 0
    }

    private function resolveSupplierName(Event $event): ?string
    {
        if ($event->relationLoaded('supplier')) {
            return optional($event->supplier)->name;
        }

        if ($event->supplier_id) {
            return optional($event->supplier)->name
                ?? Supplier::query()->where('id', $event->supplier_id)->value('name');
        }

        return null;
    }

    private function paymentNotesForSupplier(?string $supplierName): ?string
    {
        if (!$supplierName) return null;

        return Str::lower(trim($supplierName)) === Str::lower(self::SECURE_PREMISES)
            ? self::PAYMENT_NOTES_SECURE_PREMISES
            : null;
    }

    private function hoursBetween(string $start, string $end): float
    {
        $s = $this->toMinutes($start);
        $e = $this->toMinutes($end);
        if ($e < $s) $e += 1440; // overnight shift wrap
        return round(max(0, $e - $s) / 60, 2);
    }

    private function toMinutes(string $t): int
    {
        [$h, $m] = array_pad(explode(':', $t), 2, 0);
        return ((int) $h * 60) + (int) $m;
    }
}
