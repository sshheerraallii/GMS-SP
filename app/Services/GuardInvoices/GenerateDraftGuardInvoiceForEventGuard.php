<?php

namespace App\Services\GuardInvoices;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\GuardInvoice;
use App\Models\GuardInvoiceLine;
use App\Models\SecurityGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateDraftGuardInvoiceForEventGuard
{
    public function handle(Event $event, int $guardId): GuardInvoice
    {
        return DB::transaction(function () use ($event, $guardId) {

            SecurityGuard::findOrFail($guardId);

            $invoice = GuardInvoice::query()
                ->where('event_id', $event->id)
                ->where('guard_id', $guardId)
                ->lockForUpdate()
                ->first();

            if ($invoice && $invoice->status !== 'draft') {
                return $invoice->load('lines');
            }

            $rate = (float) ($event->pay_rate ?? 0);

            if (!$invoice) {
                $invoice = GuardInvoice::create([
                    'event_id'       => $event->id,
                    'guard_id'       => $guardId,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'status'         => 'draft',
                ]);
            }

            $invoice->event_name_snapshot = $event->event_name;
            $invoice->pay_rate_snapshot   = $rate;
            $invoice->save();

            GuardInvoiceLine::query()
                ->where('guard_invoice_id', $invoice->id)
                ->delete();

            $shifts = EventShift::query()
                ->where('event_id', $event->id)
                ->where('guard_id', $guardId)
                ->whereNull('cancelled_at')
                ->whereNotNull('date')
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            $lineNo = 1;
            $totalHours = 0.0;
            $subtotal = 0.0;

            foreach ($shifts as $shift) {
                $hours = $this->computeNetHours(
                    (string) $shift->start_time,
                    (string) $shift->end_time,
                    (float) ($shift->break_hours ?? 0)
                );

                $amount = round($hours * $rate, 2);

                GuardInvoiceLine::create([
                    'guard_invoice_id' => $invoice->id,
                    'line_no'          => $lineNo++,
                    'service_date'     => $shift->date,
                    'description'      => $this->lineDescription($shift),
                    'shift_start'      => $shift->start_time,
                    'shift_end'        => $shift->end_time,
                    'hours'            => round($hours, 2),
                    'rate'             => round($rate, 2),
                    'amount'           => $amount,
                ]);

                $totalHours += $hours;
                $subtotal += $amount;
            }

            $invoice->update([
                'total_hours' => round($totalHours, 2),
                'subtotal'    => round($subtotal, 2),
                'total'       => round($subtotal, 2),
            ]);

            return $invoice->load('lines');
        });
    }

    /**
     * V3-P2: the line description is the shift's composed site location
     * (Site Name, Postcode, Site Address). Falls back to the previous
     * generic label when a shift carries no location at all — legacy
     * events and any slot saved without the three site fields.
     * The field remains hand-editable on the draft edit screen.
     */
    private function lineDescription(EventShift $shift): string
    {
        $location = trim((string) ($shift->location ?? ''));

        return $location !== '' ? $location : 'Guard Services';
    }

    private function computeNetHours(string $start, string $end, float $breakHours = 0): float
    {
        $workedHours = $this->computeWorkedHours($start, $end);
        $netHours = max($workedHours - max(0, $breakHours), 0);

        return round($netHours, 2);
    }

    private function computeWorkedHours(string $start, string $end): float
    {
        $s = strtotime($start);
        $e = strtotime($end);

        if ($e < $s) {
            $e += 86400; // overnight shift
        }

        return max(0, ($e - $s) / 3600);
    }

    private function generateInvoiceNumber(): string
    {
        return 'GINV-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(4));
    }
}