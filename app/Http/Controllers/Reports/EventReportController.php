<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\EventShift;
use Carbon\Carbon;
use App\Models\SecurityGuard;

class EventReportController extends Controller
{
    public function index()
    {
        $events = Event::query()
            ->with('client')
            ->orderByDesc('start_date')
            ->get()
            ->map(function ($event) {

                $shiftsQuery = EventShift::where('event_id', $event->id);

                $totalShifts = $shiftsQuery->count();

                $totalHours = $shiftsQuery->get()->sum(function ($shift) {
                    if (!$shift->start_time || !$shift->end_time) {
                        return 0;
                    }

                    $start = strtotime($shift->start_time);
                    $end   = strtotime($shift->end_time);

                    if ($end < $start) {
                        $end += 86400; // crosses midnight
                    }

                    return ($end - $start) / 3600;
                });

                return [
                    'id'              => $event->id,
                    'event_name'      => $event->event_name,
                    'client_name'     => $event->client->name ?? '—',
                    'start_date'      => $event->start_date,
                    'end_date'        => $event->end_date,
                    'total_days'      => $event->start_date && $event->end_date
                        ? Carbon::parse($event->start_date)->diffInDays(Carbon::parse($event->end_date)) + 1
                        : 0,
                    'total_guards'    => $shiftsQuery->distinct('guard_id')->count('guard_id'),
                    'total_shifts'    => $totalShifts,
                    'total_hours'     => round($totalHours, 2),
                ];
            });

        return view('reports.events.index', compact('events'));
    }

    public function show(Event $event)
    {
        $shifts = EventShift::where('event_id', $event->id)->get();

        $totalShifts = $shifts->count();

        $totalHours = $shifts->sum(function ($shift) {
            if (!$shift->start_time || !$shift->end_time) {
                return 0;
            }

            $start = strtotime($shift->start_time);
            $end   = strtotime($shift->end_time);

            if ($end < $start) {
                $end += 86400; // overnight shift
            }

            return ($end - $start) / 3600;
        });

        $totalDays = ($event->start_date && $event->end_date)
            ? Carbon::parse($event->start_date)->diffInDays(Carbon::parse($event->end_date)) + 1
            : 0;

        $totalGuards = $shifts->pluck('guard_id')->unique()->count();

        return view('reports.events.show', [
            'event'        => $event,
            'totalDays'    => $totalDays,
            'totalGuards'  => $totalGuards,
            'totalShifts'  => $totalShifts,
            'totalHours'   => round($totalHours, 2),
        ]);
    }

    public function detailed(Event $event)
    {
        $shifts = EventShift::where('event_id', $event->id)
            ->orderBy('date')
            ->get();

        // Preload guards once (keeps same output, avoids N+1)
        $guardIds = $shifts->pluck('guard_id')->filter()->unique()->values();
        $guardsById = SecurityGuard::whereIn('id', $guardIds)->get()->keyBy('id');

        $days = [];
        $eventTotalHours = 0;
        $uniqueGuards = [];

        foreach ($shifts as $shift) {

            if (!$shift->start_time || !$shift->end_time) {
                continue;
            }

            $start = strtotime($shift->start_time);
            $end   = strtotime($shift->end_time);

            // Handle overnight shifts
            if ($end < $start) {
                $end += 86400;
            }

            $hours = round(($end - $start) / 3600, 2);

            // ✅ FIX: normalize date to a string array key
            if (empty($shift->date)) {
                // if date is missing, skip (prevents bad keys)
                continue;
            }

            $dateKey = $shift->date instanceof \Carbon\CarbonInterface
                ? $shift->date->toDateString()
                : Carbon::parse($shift->date)->toDateString();

            $guard = $guardsById->get($shift->guard_id);
            $guardName = $guard?->fullname ?? '—';

            $uniqueGuards[$shift->guard_id] = true;

            if (!isset($days[$dateKey])) {
                $days[$dateKey] = [
                    'rows' => [],
                    'total_hours' => 0,
                    'total_charge' => 0,
                ];
            }

            if (!isset($days[$dateKey]['rows'][$shift->guard_id])) {
                $days[$dateKey]['rows'][$shift->guard_id] = [
                    'guard_name' => $guardName,
                    'hours' => 0,
                    'charge' => 0,
                ];
            }

            $days[$dateKey]['rows'][$shift->guard_id]['hours'] += $hours;
            $days[$dateKey]['rows'][$shift->guard_id]['charge'] =
                round($days[$dateKey]['rows'][$shift->guard_id]['hours'] * $event->charge_rate, 2);

            $days[$dateKey]['total_hours'] += $hours;
            $days[$dateKey]['total_charge'] =
                round($days[$dateKey]['total_hours'] * $event->charge_rate, 2);

            $eventTotalHours += $hours;
        }

        $eventTotalCharge = round($eventTotalHours * $event->charge_rate, 2);

        return view('reports.events.detailed', [
            'event' => $event,
            'days' => $days,
            'eventTotalHours' => round($eventTotalHours, 2),
            'eventTotalCharge' => $eventTotalCharge,
            'totalGuards' => count($uniqueGuards),
        ]);
    }
}
