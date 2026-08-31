<?php

namespace App\Services\Invoices;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\SecurityGuard;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateDraftInvoiceForEvent
{
    private const SECURE_PREMISES = 'Secure Premises LTD';

    private const PAYMENT_NOTES_SECURE_PREMISES =
        "Please make payment to this account.\n" .
        "Secure premises LTD\n" .
        "Sort Code: 30-99-50\n" .
        "AC No: 17788163";

    public function __construct(
        private readonly InvoiceNumberGenerator $numberGenerator,
    ) {}

    public function handle(Event $event): Invoice
    {
        return DB::transaction(function () use ($event) {

            $event->loadMissing('supplier');

            $eventName  = (string) $event->event_name;
            $chargeRate = (float) $event->charge_rate;

            $clientType = (string) $event->client_type;
            $vatRate    = $this->vatRateFromClientType($clientType);

            $shiftMode = Str::lower(trim((string) $event->shift_mode));
            if (!in_array($shiftMode, ['same', 'different'], true)) {
                $shiftMode = 'same';
            }

            $supplierName = $this->resolveSupplierName($event);
            $notes        = $this->paymentNotesForSupplier($supplierName);

            $existing = Invoice::query()
                ->where('event_id', $event->id)
                ->first();

            if ($existing) {
                if ($existing->status !== 'draft') {
                    return $existing->load('lines');
                }

                $existing->lines()->delete();

                $existing->update([
                    'event_name_snapshot'    => $eventName,
                    'client_type_snapshot'   => $clientType,
                    'charge_rate_snapshot'   => $chargeRate,
                    'vat_rate'               => $vatRate,
                    'supplier_name_snapshot' => $supplierName,
                    'payment_notes'          => $notes,
                    'total_hours'            => 0,
                    'subtotal'               => 0,
                    'vat_amount'             => 0,
                    'total'                  => 0,
                ]);

                $invoice = $existing;
            } else {
                $invoiceNumber = $this->numberGenerator->nextForEventName($eventName);

                $invoice = Invoice::create([
                    'event_id'               => $event->id,
                    'invoice_number'         => $invoiceNumber,
                    'status'                 => 'draft',
                    'event_name_snapshot'    => $eventName,
                    'client_type_snapshot'   => $clientType,
                    'charge_rate_snapshot'   => $chargeRate,
                    'vat_rate'               => $vatRate,
                    'supplier_name_snapshot' => $supplierName,
                    'payment_notes'          => $notes,
                    'total_hours'            => 0,
                    'subtotal'               => 0,
                    'vat_amount'             => 0,
                    'total'                  => 0,
                ]);
            }

            $shifts = EventShift::query()
                ->where('event_id', $event->id)
                ->whereNull('cancelled_at')
                ->whereNotNull('date')
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->get();

            if ($shifts->isEmpty()) {
                return $invoice->fresh('lines');
            }

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
                            'event_id'   => $event->id,
                            'date'       => $date,
                            'slot_count' => count($slots),
                        ]);
                    }
                }
            }

            /*
             * Group stays the same as requested:
             * one line per date + start_time + end_time
             *
             * Since break hours now affect payable hours, we include break_hours in the
             * internal grouping key to keep line math correct if break values ever differ.
             * Visually, the line still remains same date/start/end structure.
             */
            /*
             * V3-P3: guard categories drive the charge rate. Fetched once
             * for every guard on the event rather than per shift.
             */
            $guardCategories = SecurityGuard::withTrashed()
                ->whereIn('id', $shifts->pluck('guard_id')->filter()->unique()->all())
                ->pluck('category', 'id');

            $groups = [];

            foreach ($shifts as $s) {
                $breakHours = round((float) ($s->break_hours ?? 0), 2);

                /*
                 * V3-P3: the grouping key carries the RESOLVED rate, not the
                 * guard's category. Guards whose rate resolves to the same
                 * number stay on one line, so an event with no category rates
                 * set produces byte-identical output to pre-V3. A slot only
                 * splits into multiple lines when the rates genuinely differ.
                 */
                $lineRate = round(
                    $event->rateFor($guardCategories[$s->guard_id] ?? null, 'charge'),
                    2
                );

                $key = implode('|', [
                    (string) $s->date,
                    (string) $s->start_time,
                    (string) $s->end_time,
                    number_format($breakHours, 2, '.', ''),
                    number_format($lineRate, 2, '.', ''),
                ]);

                $groups[$key] ??= [
                    'date'        => (string) $s->date,
                    'start'       => (string) $s->start_time,
                    'end'         => (string) $s->end_time,
                    'break_hours' => $breakHours,
                    'rate'        => $lineRate,
                    'guards'      => [],
                ];

                $groups[$key]['guards'][(string) $s->guard_id] = true;
            }

            // Rate appended to the sort key so split lines order deterministically.
            uasort($groups, fn ($a, $b) => strcmp(
                $a['date'] . $a['start'] . number_format($a['rate'], 2, '.', ''),
                $b['date'] . $b['start'] . number_format($b['rate'], 2, '.', '')
            ));

            $lineNo     = 1;
            $subtotal   = 0.0;
            $totalHours = 0.0;

            foreach ($groups as $g) {
                $qty = count($g['guards']);

                $hoursPerGuard = $this->computeNetHours(
                    $g['start'],
                    $g['end'],
                    (float) ($g['break_hours'] ?? 0)
                );

                $lineRate = (float) $g['rate'];
                $amount   = round($qty * $hoursPerGuard * $lineRate, 2);

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
                    'hours'        => round($hoursPerGuard, 2),
                    'rate'         => round($lineRate, 2),
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
        return Str::upper(trim($clientType)) === 'VAT' ? 0.20 : 0.00;
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
        if (!$supplierName) {
            return null;
        }

        return Str::lower(trim($supplierName)) === Str::lower(self::SECURE_PREMISES)
            ? self::PAYMENT_NOTES_SECURE_PREMISES
            : null;
    }

    private function computeNetHours(string $start, string $end, float $breakHours = 0): float
    {
        $workedHours = $this->hoursBetween($start, $end);
        $netHours = max($workedHours - max(0, $breakHours), 0);

        return round($netHours, 2);
    }

    private function hoursBetween(string $start, string $end): float
    {
        $s = $this->toMinutes($start);
        $e = $this->toMinutes($end);

        if ($e < $s) {
            $e += 1440;
        }

        return max(0, ($e - $s) / 60);
    }

    private function toMinutes(string $t): int
    {
        [$h, $m] = array_pad(explode(':', $t), 2, 0);
        return ((int) $h * 60) + (int) $m;
    }
}