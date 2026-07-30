<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\SecurityGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuardStatementController extends Controller
{
    /**
     * Filter screen: pick one guard, an optional date range, and
     * optionally narrow by clients / events. Nothing is persisted.
     */
    public function form()
    {
        return view('reports.guard-statement.form', [
            'guards'  => SecurityGuard::query()->orderBy('fullname')->get(['id', 'fullname']),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'events'  => Event::query()->orderBy('event_name')->get(['id', 'event_name', 'client_id']),
        ]);
    }

    /**
     * Build and stream the guard's statement PDF on demand. One guard,
     * grouped by event, each shift valued at that event's pay_rate.
     * Cancelled shifts are omitted (they bill/pay as zero).
     */
    public function download(Request $request)
    {
        $data = $request->validate([
            'guard_id'     => 'required|exists:security_guards,id',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date|after_or_equal:date_from',
            'client_ids'   => 'nullable|array',
            'client_ids.*' => 'integer|exists:clients,id',
            'event_ids'    => 'nullable|array',
            'event_ids.*'  => 'integer|exists:events,id',
        ]);

        $guard = SecurityGuard::query()->findOrFail($data['guard_id']);

        $query = EventShift::query()
            ->with(['event.client'])
            ->where('guard_id', $guard->id)
            ->whereNull('cancelled_at')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        if (!empty($data['date_from'])) {
            $query->whereDate('date', '>=', $data['date_from']);
        }
        if (!empty($data['date_to'])) {
            $query->whereDate('date', '<=', $data['date_to']);
        }
        if (!empty($data['event_ids'])) {
            $query->whereIn('event_id', $data['event_ids']);
        }
        if (!empty($data['client_ids'])) {
            $clientIds = $data['client_ids'];
            $query->whereHas('event', fn ($q) => $q->whereIn('client_id', $clientIds));
        }

        $shifts = $query->orderBy('date')->orderBy('start_time')->get();

        $groups = [];
        $grandHours = 0.0;
        $grandAmount = 0.0;

        foreach ($shifts as $shift) {
            $event = $shift->event;
            $eid   = $event?->id ?? 0;
            $rate  = (float) ($event?->pay_rate ?? 0);
            $hours = $this->shiftNetHours($shift);
            $amount = round($hours * $rate, 2);

            if (!isset($groups[$eid])) {
                $groups[$eid] = [
                    'event_name'  => $event?->event_name ?? '—',
                    'client_name' => $event?->client?->name ?? '—',
                    'pay_rate'    => $rate,
                    'rows'        => [],
                    'hours'       => 0.0,
                    'amount'      => 0.0,
                ];
            }

            $groups[$eid]['rows'][] = [
                'date'  => optional($shift->date)->format('d/m/Y'),
                'start' => substr((string) $shift->start_time, 0, 5),
                'end'   => substr((string) $shift->end_time, 0, 5),
                'break' => number_format((float) ($shift->break_hours ?? 0), 2),
                'hours' => number_format($hours, 2),
                'amount' => number_format($amount, 2),
            ];
            $groups[$eid]['hours']  += $hours;
            $groups[$eid]['amount'] += $amount;
            $grandHours  += $hours;
            $grandAmount += $amount;
        }

        $pdf = Pdf::loadView('reports.guard-statement.pdf', [
            'guard'       => $guard,
            'groups'      => array_values($groups),
            'grandHours'  => number_format($grandHours, 2),
            'grandAmount' => number_format($grandAmount, 2),
            'dateFrom'    => $data['date_from'] ?? null,
            'dateTo'      => $data['date_to'] ?? null,
            'generatedAt' => now('Europe/London')->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        $slug = Str::slug($guard->fullname ?: ('guard-' . $guard->id));

        return $pdf->download('statement-' . $slug . '.pdf');
    }

    /**
     * Net payable hours for a shift (worked minus break, overnight-safe).
     * Mirrors the logic used in the guard reports.
     */
    private function shiftNetHours(EventShift $shift): float
    {
        if (!$shift->date || !$shift->start_time || !$shift->end_time) {
            return 0.0;
        }

        $start = strtotime((string) $shift->start_time);
        $end   = strtotime((string) $shift->end_time);

        if ($end < $start) {
            $end += 86400;
        }

        $workedHours = ($end - $start) / 3600;
        $breakHours  = (float) ($shift->break_hours ?? 0);

        return round(max($workedHours - max(0, $breakHours), 0), 2);
    }
}
