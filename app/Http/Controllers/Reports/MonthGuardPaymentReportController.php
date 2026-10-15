<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\EventGuardPayment;
use App\Models\EventShift;
use App\Models\SecurityGuard;
use App\Services\GuardPayments\ShiftPayCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * V5-P5: Month Wise Guard Payment Report.
 *
 * Every guard who worked any event (any client) in the date range, A-Z.
 * A guard row expands into one card per event: its shifts and the payment
 * form. Paid By / Paid From / Paid On / Remarks are the SAME
 * event_guard_payments rows used everywhere else; the form posts to the
 * existing Guard Payment Report save action, so the side effects (guard
 * invoice marked paid, temp-guard block) are identical.
 *
 * View: any logged-in role.  Edit: edit-guard-payments (Super Admin + Accountant).
 * Contact #: hidden from roles without view-guard-details (Moderator).
 *
 * Amounts follow the Guard Payment Report / Guard Statement method: each
 * shift is valued at its resolved pay rate (shift override, category rate,
 * event rate), rounded per shift, and totals are sums of those rows.
 * Cancelled shifts and shifts without times are excluded. Hours, amount and
 * expense cover only shifts INSIDE the date range.
 */
class MonthGuardPaymentReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        $today = Carbon::now('Europe/London');
        $from  = $data['date_from'] ?? $today->copy()->startOfMonth()->toDateString();
        $to    = $data['date_to']   ?? $today->copy()->endOfMonth()->toDateString();

        $shifts = EventShift::query()
            ->with(['event.client'])
            ->whereNotNull('guard_id')
            ->whereNull('cancelled_at')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $guards = SecurityGuard::withTrashed()
            ->whereIn('id', $shifts->pluck('guard_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $payments = EventGuardPayment::query()
            ->whereIn('event_id', $shifts->pluck('event_id')->unique()->all())
            ->whereIn('guard_id', $guards->keys()->all())
            ->get()
            ->groupBy('guard_id')
            ->map(fn ($g) => $g->keyBy('event_id'));

        $rows = [];

        foreach ($shifts->groupBy('guard_id') as $guardId => $guardShifts) {
            $guard = $guards->get($guardId);
            if (!$guard) {
                continue;
            }

            $events = [];
            foreach ($guardShifts->groupBy('event_id') as $eventId => $eventShifts) {
                $event = $eventShifts->first()->event;
                if (!$event) {
                    continue;
                }

                $p = $payments->get($guardId)?->get($eventId);

                $card = [
                    'event_id'    => (int) $eventId,
                    'event_name'  => $event->event_name ?? '-',
                    'client_name' => $event->client?->name ?? '-',
                    'rows'        => [],
                    'hours'       => 0.0,
                    'amount'      => 0.0,
                    'expense'     => 0.0,
                    'payment'     => [
                        'paid_by'           => $p?->paid_by ?? '',
                        'paid_from_account' => $p?->paid_from_account ?? '',
                        'paid_on'           => $p?->paid_on?->format('Y-m-d') ?? '',
                        'remarks'           => $p?->remarks ?? '',
                    ],
                ];

                foreach ($eventShifts as $s) {
                    $hours   = round(ShiftPayCalculator::netHours($s), 2);
                    $rate    = $s->resolvedRate($event, $guard->category, 'pay');
                    $amount  = round($hours * $rate, 2);
                    $expense = round((float) ($s->expense ?? 0), 2);

                    $card['rows'][] = [
                        'date'     => optional($s->date)->format('d/m/Y'),
                        'start'    => substr((string) $s->start_time, 0, 5),
                        'end'      => substr((string) $s->end_time, 0, 5),
                        'break'    => round((float) ($s->break_hours ?? 0), 2),
                        'hours'    => $hours,
                        'rate'     => round($rate, 2),
                        'amount'   => $amount,
                        'expense'  => $expense,
                        'location' => $s->location ?: '',
                    ];
                    $card['hours']   += $hours;
                    $card['amount']  += $amount;
                    $card['expense'] += $expense;
                }

                foreach (['hours', 'amount', 'expense'] as $f) {
                    $card[$f] = round($card[$f], 2);
                }

                $events[] = $card;
            }

            usort($events, fn ($a, $b) => strcasecmp($a['event_name'], $b['event_name']));

            $paidOns  = collect($events)->pluck('payment.paid_on')->filter()->unique()->values();
            $paidFrom = collect($events)->pluck('payment.paid_from_account')->filter()->unique()->values();
            $paidCnt  = collect($events)->filter(fn ($e) => $e['payment']['paid_on'] !== '')->count();

            $rows[] = [
                'guard_id'   => (int) $guardId,
                'name'       => $guard->fullname ?? '',
                'phone'      => $guard->phone_number ?? '',
                'hours'      => round(collect($events)->sum('hours'), 2),
                'amount'     => round(collect($events)->sum('amount'), 2),
                'expense'    => round(collect($events)->sum('expense'), 2),
                'events'     => $events,
                // Summary only on the guard row; edited on each event card.
                'paid_on'    => $this->summarise($paidOns->map(fn ($d) => Carbon::parse($d)->format('d/m/Y')), $paidCnt, count($events)),
                'paid_from'  => $this->summariseFrom($paidFrom),
            ];
        }

        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return view('reports.month-guard-payments.index', [
            'rows'        => $rows,
            'dateFrom'    => $from,
            'dateTo'      => $to,
            'canEdit'     => (bool) auth()->user()?->can('edit-guard-payments'),
            'showContact' => Gate::allows('view-guard-details'),
            'totals'      => [
                'hours'   => round(collect($rows)->sum('hours'), 2),
                'amount'  => round(collect($rows)->sum('amount'), 2),
                'expense' => round(collect($rows)->sum('expense'), 2),
            ],
        ]);
    }

    /** Guard-row "Paid On": one date, "Partly paid (x/y)", "Multiple dates" or blank. */
    private function summarise($dates, int $paidCount, int $eventCount): string
    {
        if ($paidCount === 0) {
            return '';
        }
        if ($paidCount < $eventCount) {
            return 'Partly paid (' . $paidCount . '/' . $eventCount . ')';
        }

        return $dates->count() === 1 ? $dates->first() : 'Multiple dates';
    }

    private function summariseFrom($accounts): string
    {
        if ($accounts->isEmpty()) {
            return '';
        }

        return $accounts->count() === 1 ? (string) $accounts->first() : 'Multiple';
    }
}
