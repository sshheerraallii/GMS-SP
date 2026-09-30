<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * V4-P6: on the staff_pay_date itself, notify (bell + email) for events
 * that still have unpaid guards. No cron: triggered by the bell feed poll,
 * throttled, and de-duplicated per (event, pay date).
 */
class PayDateNotifier
{
    public static function run(): void
    {
        $today = now('Europe/London')->toDateString();

        // At most one check every 10 minutes across all users.
        if (!Cache::add('paydate-notifier-' . $today, 1, now()->addMinutes(10))) {
            return;
        }

        $events = Event::query()
            ->whereNotNull('staff_pay_date')
            ->whereDate('staff_pay_date', $today)
            ->get(['id', 'event_name', 'staff_pay_date']);

        foreach ($events as $event) {
            $guardIds = DB::table('event_shifts')
                ->where('event_id', $event->id)
                ->whereNull('cancelled_at')
                ->whereNotNull('guard_id')
                ->distinct()->pluck('guard_id')->map(fn ($i) => (int) $i);

            if ($guardIds->isEmpty()) {
                continue;
            }

            $paid = DB::table('event_guard_payments')
                ->where('event_id', $event->id)
                ->whereNotNull('paid_on')
                ->distinct()->pluck('guard_id')->map(fn ($i) => (int) $i);

            $due = $guardIds->diff($paid)->count();
            if ($due === 0) {
                continue;
            }

            NotificationService::notify(
                'pay_date',
                'Staff pay date today: ' . $event->event_name,
                $due . ' of ' . $guardIds->count() . ' guard(s) still unpaid.',
                route('events.guardPayments.index', $event->id, false),
                ['finance'],
                'paydate|' . $event->id . '|' . $today,
                true
            );
        }
    }
}
