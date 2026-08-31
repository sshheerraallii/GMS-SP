<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\SecurityGuard;
use Carbon\Carbon;

class EventReportController extends Controller
{
    public function index()
    {
        $events = Event::query()
            ->with('client')
            ->orderByDesc('start_date')
            ->get()
            ->map(function ($event) {

                $shiftsQuery = EventShift::query()->where('event_id', $event->id)->whereNull('cancelled_at');

                $totalShifts = (clone $shiftsQuery)->count();

                $totalHours = (clone $shiftsQuery)->get()->sum(function ($shift) {
                    return $this->shiftNetHours($shift);
                });

                return [
                    'id'           => $event->id,
                    'event_name'   => $event->event_name,
                    'client_name'  => $event->client->name ?? '—',
                    'client_color' => $event->client->color ?? null,
                    'start_date'   => $event->start_date,
                    'end_date'     => $event->end_date,
                    'total_days'   => $event->start_date && $event->end_date
                        ? Carbon::parse($event->start_date)->diffInDays(Carbon::parse($event->end_date)) + 1
                        : 0,
                    'total_guards' => (clone $shiftsQuery)->distinct('guard_id')->count('guard_id'),
                    'total_shifts' => $totalShifts,
                    'total_hours'  => round($totalHours, 2),
                ];
            });

        return view('reports.events.index', compact('events'));
    }

    public function show(Event $event)
    {
        $shifts = EventShift::query()
            ->where('event_id', $event->id)
            ->whereNull('cancelled_at')
            ->get();

        $totalShifts = $shifts->count();

        $totalHours = $shifts->sum(function ($shift) {
            return $this->shiftNetHours($shift);
        });

        $totalDays = ($event->start_date && $event->end_date)
            ? Carbon::parse($event->start_date)->diffInDays(Carbon::parse($event->end_date)) + 1
            : 0;

        $totalGuards = $shifts->pluck('guard_id')->filter()->unique()->count();

        return view('reports.events.show', [
            'event'       => $event,
            'totalDays'   => $totalDays,
            'totalGuards' => $totalGuards,
            'totalShifts' => $totalShifts,
            'totalHours'  => round($totalHours, 2),
        ]);
    }

    public function detailed(Event $event)
    {
        $shifts = EventShift::query()
            ->where('event_id', $event->id)
            ->whereNull('cancelled_at')
            ->orderBy('date')
            ->get();

        $guardIds = $shifts->pluck('guard_id')->filter()->unique()->values();
        $guardsById = SecurityGuard::query()
            ->whereIn('id', $guardIds)
            ->get()
            ->keyBy('id');

        $days = [];
        $eventTotalHours = 0.0;
        $uniqueGuards = [];

        foreach ($shifts as $shift) {
            $hours = $this->shiftNetHours($shift);

            if ($hours <= 0) {
                continue;
            }

            if (empty($shift->date)) {
                continue;
            }

            $dateKey = $shift->date instanceof \Carbon\CarbonInterface
                ? $shift->date->toDateString()
                : Carbon::parse($shift->date)->toDateString();

            $guard = $guardsById->get($shift->guard_id);
            $guardName = $guard?->fullname ?? '—';

            if ($shift->guard_id) {
                $uniqueGuards[$shift->guard_id] = true;
            }

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
            $days[$dateKey]['rows'][$shift->guard_id]['charge'] = round(
                $days[$dateKey]['rows'][$shift->guard_id]['hours'] * (float) $event->charge_rate,
                2
            );

            $days[$dateKey]['total_hours'] += $hours;
            $days[$dateKey]['total_charge'] = round(
                $days[$dateKey]['total_hours'] * (float) $event->charge_rate,
                2
            );

            $eventTotalHours += $hours;
        }

        $eventTotalCharge = round($eventTotalHours * (float) $event->charge_rate, 2);

        return view('reports.events.detailed', [
            'event'            => $event,
            'days'             => $days,
            'eventTotalHours'  => round($eventTotalHours, 2),
            'eventTotalCharge' => $eventTotalCharge,
            'totalGuards'      => count($uniqueGuards),
        ]);
    }

    private function shiftNetHours(EventShift $shift): float
    {
        if (!$shift->start_time || !$shift->end_time) {
            return 0.0;
        }

        $start = strtotime((string) $shift->start_time);
        $end   = strtotime((string) $shift->end_time);

        if ($end < $start) {
            $end += 86400;
        }

        $workedHours = ($end - $start) / 3600;
        $breakHours = (float) ($shift->break_hours ?? 0);

        return round(max($workedHours - max(0, $breakHours), 0), 2);
    }
}