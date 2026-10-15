<?php

namespace App\Services\GuardPayments;

use App\Models\Event;
use App\Models\EventGuardPayment;
use App\Models\EventShift;
use Illuminate\Support\Collection;

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
        // V5-P3: shifts are read as models so per-shift pay-rate overrides and
        // expenses are honoured (see ShiftPayCalculator for the exact rules).
        $shifts = EventShift::query()
            ->where('event_id', $event->id)
            ->whereNull('cancelled_at')
            ->whereNotNull('guard_id')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get()
            ->groupBy('guard_id');

        $guards = \App\Models\SecurityGuard::withTrashed()
            ->whereIn('id', $shifts->keys()->all())
            ->orderBy('fullname')
            ->get();

        $payments = EventGuardPayment::query()
            ->where('event_id', $event->id)
            ->get()
            ->keyBy('guard_id');

        return $guards->map(function ($guard) use ($event, $payments, $shifts) {
            $calc    = ShiftPayCalculator::summarise($event, $guard->category, $shifts->get($guard->id, collect()));
            $payment = $payments->get($guard->id);

            return [
                'guard_id'         => (int) $guard->id,
                'guard_name'       => $guard->fullname ?? '',
                'category'         => $guard->category ?? '',
                'total_hours'      => $calc['hours'],
                'pay_rate'         => $calc['rate'],
                'rate_varies'      => $calc['rate_varies'],
                'amount'           => $calc['amount'],
                'expense'          => $calc['expense'],
                'sort_code'        => $guard->sort_code ?? '',
                'account_number'   => $guard->account_number ?? '',
                'beneficiary_name' => $guard->beneficiary_name ?? '',
                'paid_by'          => $payment?->paid_by ?? '',
                'paid_from_account'=> $payment?->paid_from_account ?? '',
                'paid_on'          => $payment?->paid_on?->format('Y-m-d') ?? '',
                'remarks'          => $payment?->remarks ?? '',
            ];
        })->values();
    }
}
