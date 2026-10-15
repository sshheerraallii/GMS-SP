<?php

namespace App\Services\GuardPayments;

use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Support\Collection;

/**
 * V5-P3: one place that turns a guard's shifts on an event into hours,
 * pay amount and expense, honouring per-shift pay-rate overrides.
 *
 * Rate per shift: shift override -> guard category rate -> event base rate
 * (EventShift::resolvedRate()).
 *
 * Safety rule: when NO shift carries an override the amount is computed
 * exactly as before V5 (rounded total hours x rate), so existing events
 * produce identical figures. Only when an override exists is the amount
 * the sum of hours x rate per shift.
 *
 * Cancelled shifts and shifts without times are ignored (hours, amount
 * and expense alike).
 */
class ShiftPayCalculator
{
    /** Net hours of one shift: overnight-safe, minus break, never negative. */
    public static function netHours(EventShift $s): float
    {
        if (!$s->start_time || !$s->end_time) {
            return 0.0;
        }

        $start = strtotime((string) $s->start_time);
        $end   = strtotime((string) $s->end_time);

        if ($end < $start) {
            $end += 86400;
        }

        return max(($end - $start) / 3600 - (float) ($s->break_hours ?? 0), 0.0);
    }

    /**
     * @param Collection<int, EventShift> $shifts one guard's shifts on ONE event
     * @return array{hours: float, amount: float, expense: float, rate: float, rate_varies: bool, has_override: bool}
     */
    public static function summarise(Event $event, ?string $category, Collection $shifts): array
    {
        $live = $shifts->filter(fn (EventShift $s) => $s->cancelled_at === null && $s->start_time && $s->end_time);

        $hoursExact  = 0.0;
        $perShiftSum = 0.0;
        $expense     = 0.0;
        $hasOverride = false;
        $rates       = [];

        foreach ($live as $s) {
            $h    = self::netHours($s);
            $rate = $s->resolvedRate($event, $category, 'pay');

            $hoursExact  += $h;
            $perShiftSum += $h * $rate;
            $expense     += (float) ($s->expense ?? 0);
            $hasOverride = $hasOverride || ($s->pay_rate !== null && $s->pay_rate !== '');
            $rates[number_format($rate, 2, '.', '')] = true;
        }

        $hours       = round($hoursExact, 2);
        $baseRate    = $event->rateFor($category, 'pay');
        $amount      = $hasOverride ? round($perShiftSum, 2) : round($hours * $baseRate, 2);
        $rateVaries  = count($rates) > 1;
        $displayRate = $rateVaries
            ? ($hours > 0 ? round($amount / $hours, 2) : round($baseRate, 2))
            : (count($rates) === 1 ? (float) array_key_first($rates) : round($baseRate, 2));

        return [
            'hours'        => $hours,
            'amount'       => $amount,
            'expense'      => round($expense, 2),
            'rate'         => $displayRate,
            'rate_varies'  => $rateVaries,
            'has_override' => $hasOverride,
        ];
    }
}
