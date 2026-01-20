<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\SecurityGuard;
use App\Models\EventShift;
use Carbon\Carbon;

class GuardReportController extends Controller
{
    public function index()
    {
        // Dropdown data
        $guards = SecurityGuard::query()
            ->orderBy('fullname')
            ->get(['id', 'fullname']);

        $events = Event::query()
            ->orderByDesc('start_date')
            ->get(['id', 'event_name', 'start_date', 'end_date']);

        // Recent (guard,event) pairs based on event_shifts
        $recentPairs = EventShift::query()
            ->selectRaw('event_id, guard_id, MAX(date) as last_worked_date')
            ->groupBy('event_id', 'guard_id')
            ->orderByDesc('last_worked_date')
            ->limit(30)
            ->get();

        // Hydrate names for UI (avoid guessing column names)
        $guardMap = $guards->keyBy('id');
        $eventMap = $events->keyBy('id');

        $recent = $recentPairs->map(function ($row) use ($guardMap, $eventMap) {
            $guard = $guardMap->get($row->guard_id);
            $event = $eventMap->get($row->event_id);

            return [
                'guard_id' => $row->guard_id,
                'guard_name' => $guard?->fullname ?? '—',
                'event_id' => $row->event_id,
                'event_name' => $event?->event_name ?? '—',
                'event_dates' => $event
                    ? trim(($event->start_date ?? '') . ' → ' . ($event->end_date ?? ''))
                    : '—',
                'last_worked_date' => $row->last_worked_date,
            ];
        });

        return view('reports.guards.index', compact('guards', 'events', 'recent'));
    }

    public function show(SecurityGuard $guard, Event $event)
    {
        $shifts = EventShift::query()
            ->where('event_id', $event->id)
            ->where('guard_id', $guard->id)
            ->orderBy('date')
            ->orderBy('shift_no')
            ->get();

        // Aggregate hours per date (one row per date)
        $rowsByDate = [];

        foreach ($shifts as $shift) {
            if (!$shift->date || !$shift->start_time || !$shift->end_time) {
                continue;
            }

            $start = strtotime($shift->start_time);
            $end   = strtotime($shift->end_time);

            // Overnight shift support
            if ($end < $start) {
                $end += 86400;
            }

            $hours = round(($end - $start) / 3600, 2);

            // ✅ FIX: normalize date to string key
            $dateKey = $shift->date instanceof \Carbon\CarbonInterface
                ? $shift->date->toDateString()
                : Carbon::parse($shift->date)->toDateString();

            if (!isset($rowsByDate[$dateKey])) {
                $rowsByDate[$dateKey] = [
                    'date' => $dateKey,
                    'hours' => 0,
                ];
            }

            $rowsByDate[$dateKey]['hours'] += $hours;
        }

        // Convert to ordered list
        $rows = collect($rowsByDate)
            ->sortBy('date')
            ->values()
            ->map(function ($r) use ($event) {
                $payRate = (float) $event->pay_rate;
                $hours   = round((float) $r['hours'], 2);
                $total   = round($hours * $payRate, 2);

                return [
                    'date' => $r['date'],
                    'hours' => $hours,
                    'pay_rate' => round($payRate, 2),
                    'total_pay' => $total,
                ];
            });

        $totalHours = round($rows->sum('hours'), 2);
        $payRate    = round((float) $event->pay_rate, 2);
        $totalPay   = round($totalHours * $payRate, 2);

        return view('reports.guards.show', [
            'guard' => $guard,
            'event' => $event,
            'rows' => $rows,
            'totalHours' => $totalHours,
            'payRate' => $payRate,
            'totalPay' => $totalPay,
        ]);
    }
}
