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

            $invoice = GuardInvoice::where('event_id', $event->id)
                ->where('guard_id', $guardId)
                ->lockForUpdate()
                ->first();

            if ($invoice && $invoice->status !== 'draft') {
                return $invoice->load('lines');
            }

            $rate = (float) ($event->pay_rate ?? 0);

            if (!$invoice) {
                $invoice = GuardInvoice::create([
                    'event_id' => $event->id,
                    'guard_id' => $guardId,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'status' => 'draft',
                ]);
            }

            $invoice->event_name_snapshot = $event->event_name;
            $invoice->pay_rate_snapshot = $rate;
            $invoice->save();

            GuardInvoiceLine::where('guard_invoice_id', $invoice->id)->delete();

            $shifts = EventShift::where('event_id', $event->id)
                ->where('guard_id', $guardId)
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            $groups = $shifts->groupBy(fn ($s) =>
                $s->date->format('Y-m-d') . '|' . $s->start_time . '|' . $s->end_time
            );

            $lineNo = 1;
            $totalHours = 0;
            $subtotal = 0;

            foreach ($groups as $group) {
                $shift = $group->first();

                $hours = $this->computeHours($shift->start_time, $shift->end_time);
                $amount = round($hours * $rate, 2);

                GuardInvoiceLine::create([
                    'guard_invoice_id' => $invoice->id,
                    'line_no' => $lineNo++,
                    'service_date' => $shift->date,
                    'description' => 'Guard Services',
                    'shift_start' => $shift->start_time,
                    'shift_end' => $shift->end_time,
                    'hours' => $hours,
                    'rate' => $rate,
                    'amount' => $amount,
                ]);

                $totalHours += $hours;
                $subtotal += $amount;
            }

            $invoice->update([
                'total_hours' => round($totalHours, 2),
                'subtotal' => round($subtotal, 2),
                'total' => round($subtotal, 2),
            ]);

            return $invoice->load('lines');
        });
    }

    private function computeHours(string $start, string $end): float
    {
        $s = strtotime($start);
        $e = strtotime($end);
        if ($e < $s) $e += 86400;
        return round(($e - $s) / 3600, 2);
    }

    private function generateInvoiceNumber(): string
    {
        return 'GINV-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(4));
    }
}
