<?php

namespace App\Services\GuardPayments;

use App\Models\Event;
use App\Models\EventGuardPayment;
use App\Models\EventShift;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * V3-P4: single source of truth for the guard pay & payment sheet rows.
 *
 * Used by both the on-screen sheet and the Excel export so the two can
 * never disagree — these are payment figures, so one implementation only.
 *
 * Hours: summed from event_shifts, cancelled shifts excluded, overnight-safe,
 * net of break hours. Rate: resolved per guard category via Event::rateFor()
 * (V3-P3), falling back to the event's base pay rate.
 */
class BuildEventGuardPaymentRows
{
    public function handle(Event $event): Collection
    {
        $shiftRows = EventShift::query()
            ->from('event_shifts as es')
            ->join('security_guards as sg', 'sg.id', '=', 'es.guard_id')
            ->where('es.event_id', $event->id)
            ->whereNull('es.cancelled_at')
            ->whereNotNull('es.start_time')
            ->whereNotNull('es.end_time')
            ->select([
                'es.guard_id',
                'sg.fullname',
                'sg.category',
                'sg.sort_code',
                'sg.account_number',
                'sg.beneficiary_name',
                DB::raw("
                    SUM(
                        GREATEST(
                            (
                                TIME_TO_SEC(
                                    TIMEDIFF(
                                        CASE
                                            WHEN es.end_time >= es.start_time THEN es.end_time
                                            ELSE ADDTIME(es.end_time, '24:00:00')
                                        END,
                                        es.start_time
                                    )
                                ) / 3600
                            ) - COALESCE(es.break_hours, 0),
                            0
                        )
                    ) AS total_hours
                "),
            ])
            ->groupBy(
                'es.guard_id',
                'sg.fullname',
                'sg.category',
                'sg.sort_code',
                'sg.account_number',
                'sg.beneficiary_name'
            )
            ->orderBy('sg.fullname')
            ->get();

        $payments = EventGuardPayment::query()
            ->where('event_id', $event->id)
            ->get()
            ->keyBy('guard_id');

        return $shiftRows->map(function ($row) use ($event, $payments) {
            $hours   = round((float) ($row->total_hours ?? 0), 2);
            $rate    = $event->rateFor($row->category, 'pay');
            $payment = $payments->get($row->guard_id);

            return [
                'guard_id'         => (int) $row->guard_id,
                'guard_name'       => $row->fullname ?? '',
                'category'         => $row->category ?? '',
                'total_hours'      => $hours,
                'pay_rate'         => round($rate, 2),
                'amount'           => round($hours * $rate, 2),
                'sort_code'        => $row->sort_code ?? '',
                'account_number'   => $row->account_number ?? '',
                'beneficiary_name' => $row->beneficiary_name ?? '',
                'paid_by'          => $payment?->paid_by ?? '',
                'paid_on'          => $payment?->paid_on?->format('Y-m-d') ?? '',
                'remarks'          => $payment?->remarks ?? '',
            ];
        });
    }
}
