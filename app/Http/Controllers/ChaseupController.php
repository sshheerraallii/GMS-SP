<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Chaseup;
use App\Models\Event;
use App\Models\EventAssignmentSlot;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ChaseupController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $clientId     = $request->input('client_id');
        $eventId      = $request->input('event_id');
        $guardId      = $request->input('guard_id');
        $allOk        = $request->input('all_ok');
        $onWay        = $request->input('on_way');
        $missingOnly  = $request->boolean('missing_only');

        // NOTE: no more syncChaseupsForDate() here. Chaseup rows are created lazily on
        // first edit (updateField), keyed to the assignment identity rather than the
        // volatile event_shifts.id, so nothing needs pre-seeding or cleaning up per view.

        $query = EventShift::query()
            ->with([
                'event.client:id,name,color',
                'securityGuard:id,fullname',
            ])
            ->whereDate('date', $selectedDate);

        if (!empty($clientId)) {
            $query->whereHas('event', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });
        }

        if (!empty($eventId)) {
            $query->where('event_id', $eventId);
        }

        if (!empty($guardId)) {
            $query->where('guard_id', $guardId);
        }

        $shifts = $query->get();

        // Notes are anchored to the assignment identity (event + date + guard), NOT the
        // event_shifts.id which is destroyed and recreated on every save. Build a lookup
        // for the selected date and resolve each shift's note from it.
        $chaseupLookup = Chaseup::query()
            ->whereDate('date', $selectedDate)
            ->whereNotNull('event_id')
            ->whereNotNull('guard_id')
            ->get()
            ->keyBy(fn (Chaseup $c) => $this->identityKey(
                (int) $c->event_id,
                optional($c->date)->format('Y-m-d'),
                (int) $c->guard_id
            ));

        $rows = $shifts->map(function (EventShift $shift) use ($chaseupLookup) {
            $clientName = optional(optional($shift->event)->client)->name;
            $eventName  = optional($shift->event)->event_name;

            $totalHours = $this->calculateTotalHours(
                $shift->date?->format('Y-m-d'),
                $shift->start_time,
                $shift->end_time,
                $shift->break_hours
            );

            $missingFields = [];

            if (!$shift->guard_id || !$shift->securityGuard) {
                $missingFields[] = 'guard';
            }
            if (!$shift->location) {
                $missingFields[] = 'location';
            }
            if (!$shift->start_time) {
                $missingFields[] = 'start_time';
            }
            if (!$shift->end_time) {
                $missingFields[] = 'end_time';
            }
            if ($shift->break_hours === null) {
                $missingFields[] = 'break_hours';
            }
            if (!$clientName) {
                $missingFields[] = 'client';
            }

            $chaseup = $chaseupLookup->get($this->identityKey(
                (int) $shift->event_id,
                optional($shift->date)->format('Y-m-d'),
                (int) $shift->guard_id
            ));

            return [
                'shift_id'           => (int) $shift->id,
                'date'               => optional($shift->date)->format('Y-m-d'),
                'day'                => optional($shift->date)->format('l'),
                'guard_name'         => optional($shift->securityGuard)->fullname,
                'location'           => $shift->location,
                'start_time'         => $shift->start_time ? substr((string) $shift->start_time, 0, 5) : null,
                'end_time'           => $shift->end_time ? substr((string) $shift->end_time, 0, 5) : null,
                'break_hours'        => $shift->break_hours !== null ? (float) $shift->break_hours : null,
                'total_hours'        => $totalHours,
                'client_name'        => $clientName,
                'client_color'       => optional(optional($shift->event)->client)->color,
                'event_id'           => (int) $shift->event_id,
                'event_name'         => $eventName,
                'client_event_label' => trim(collect([$clientName, $eventName])->filter()->implode(' · ')) ?: null,

                'reminder'           => $chaseup?->reminder,
                'all_ok'             => $chaseup?->all_ok,
                'all_ok_response'    => $chaseup?->all_ok_response,
                'on_way'             => $chaseup?->on_way,
                'on_way_response'    => $chaseup?->on_way_response,
                'reaching_time'      => $chaseup?->reaching_time ? substr((string) $chaseup->reaching_time, 0, 5) : null,
                'book_on'            => $chaseup?->book_on,
                'comments'           => $chaseup?->comments,
                'approved_by'        => $chaseup?->approved_by,
                'verified_by'        => $chaseup?->verified_by,
                'is_empty'           => false,

                'missing_fields'     => $missingFields,
                'is_missing'         => !empty($missingFields),
                'is_cancelled'       => $shift->cancelled_at !== null,
            ];
        });

        /*
         * V4-P4: unfilled slots. Chaseup rows come from event_shifts, which
         * only exist once a guard AND times are set, so an unfilled required
         * slot was invisible here. Show them as rows the team can act on.
         * Skipped when a guard / All Ok / On Way filter is active, since an
         * unfilled slot has none of those.
         */
        $noEmpties = !empty($guardId)
            || ($allOk !== null && $allOk !== '')
            || ($onWay !== null && $onWay !== '');

        if (!$noEmpties) {
            $rows = $rows->concat($this->emptySlotRows($selectedDate, $clientId, $eventId))->values();
        }

        if ($allOk !== null && $allOk !== '') {
            $rows = $rows->filter(fn ($row) => ($row['all_ok'] ?? '') === $allOk)->values();
        }

        if ($onWay !== null && $onWay !== '') {
            $rows = $rows->filter(fn ($row) => ($row['on_way'] ?? '') === $onWay)->values();
        }

        if ($missingOnly) {
            $rows = $rows->filter(fn ($row) => $row['is_missing'] === true)->values();
        }

        $rows = $rows->sort(function ($a, $b) {
            if ($a['is_missing'] !== $b['is_missing']) {
                return $a['is_missing'] ? -1 : 1;
            }

            $aTime = $a['start_time'] ?? '99:99';
            $bTime = $b['start_time'] ?? '99:99';

            if ($aTime === $bTime) {
                return strcmp(($a['guard_name'] ?? ''), ($b['guard_name'] ?? ''));
            }

            return strcmp($aTime, $bTime); // earliest first
        })->values();

        $clients = DB::table('clients')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $events = DB::table('events')
            ->select('id', 'event_name')
            ->orderBy('event_name')
            ->get();

        $guards = DB::table('security_guards')
            ->select('id', 'fullname')
            ->orderBy('fullname')
            ->get();

        // V4-P4: events running on the selected date, for the Add shift popup.
        $eventsOnDate = Event::query()
            ->with('client:id,name')
            ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $selectedDate))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $selectedDate))
            ->orderBy('event_name')
            ->get(['id', 'event_name', 'client_id'])
            ->map(fn ($e) => [
                'id'    => (int) $e->id,
                'label' => trim(collect([optional($e->client)->name, $e->event_name])->filter()->implode(' · ')),
            ])
            ->values();

        return view('chaseup.index', [
            'eventsOnDate'   => $eventsOnDate,
            'canAssign'      => auth()->user()?->can('shifts.assign') ?? false,
            'rows'           => $rows,
            'selectedDate'   => $selectedDate,
            'clients'        => $clients,
            'events'         => $events,
            'guards'         => $guards,
            'canViewAudit'   => auth()->user()?->hasRole('Super Admin') ?? false,
            'filters'        => [
                'client_id'    => $clientId,
                'event_id'     => $eventId,
                'guard_id'     => $guardId,
                'all_ok'       => $allOk,
                'on_way'       => $onWay,
                'missing_only' => $missingOnly,
            ],
        ]);
    }

    /**
     * V4-P4: rows for required slots that have no usable shift yet — no
     * guard, or a guard without start/end times (no event_shifts row).
     */
    private function emptySlotRows(string $date, $clientId, $eventId): Collection
    {
        $events = Event::query()
            ->with('client:id,name,color')
            ->when(!empty($clientId), fn ($q) => $q->where('client_id', $clientId))
            ->when(!empty($eventId), fn ($q) => $q->where('id', $eventId))
            ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $date))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date))
            ->get()
            ->filter(fn (Event $e) => (int) (((array) ($e->daily_guards ?? []))[$date] ?? 0) > 0);

        if ($events->isEmpty()) {
            return collect();
        }

        $slotsByEvent = EventAssignmentSlot::query()
            ->with('securityGuard:id,fullname')
            ->whereIn('event_id', $events->pluck('id'))
            ->whereDate('date', $date)
            ->get()
            ->groupBy('event_id');

        $rows = collect();

        foreach ($events as $event) {
            $required = (int) ((array) $event->daily_guards)[$date];
            $slots    = $slotsByEvent->get($event->id, collect());

            for ($i = 1; $i <= $required; $i++) {
                $slot = $slots->firstWhere('slot_no', $i);

                // Complete slot (guard + both times) already shows as a normal row.
                if ($slot && $slot->guard_id && $slot->start_time && $slot->end_time) {
                    continue;
                }

                $start = $slot?->start_time ? substr((string) $slot->start_time, 0, 5) : null;
                $end   = $slot?->end_time ? substr((string) $slot->end_time, 0, 5) : null;
                $break = $slot && $slot->break_hours !== null ? (float) $slot->break_hours : null;

                $missing = [];
                if (!$slot || !$slot->guard_id) { $missing[] = 'guard'; }
                if (!$start) { $missing[] = 'start_time'; }
                if (!$end) { $missing[] = 'end_time'; }

                $clientName = optional($event->client)->name;

                $rows->push([
                    'shift_id'           => null,
                    'is_empty'           => true,
                    'slot_no'            => $i,
                    'date'               => $date,
                    'day'                => Carbon::parse($date)->format('l'),
                    'guard_id'           => $slot?->guard_id ? (int) $slot->guard_id : null,
                    'guard_name'         => optional($slot?->securityGuard)->fullname,
                    'temp_phone'         => ($slot && !$slot->guard_id) ? $slot->temp_phone : null,
                    'location'           => $slot?->location,
                    'start_time'         => $start,
                    'end_time'           => $end,
                    'break_hours'        => $break,
                    'total_hours'        => $this->calculateTotalHours($date, $start, $end, $break),
                    'site_name'          => $slot?->site_name,
                    'site_postcode'      => $slot?->site_postcode,
                    'site_address'       => $slot?->site_address,
                    'client_name'        => $clientName,
                    'client_color'       => optional($event->client)->color,
                    'event_id'           => (int) $event->id,
                    'event_name'         => $event->event_name,
                    'client_event_label' => trim(collect([$clientName, $event->event_name])->filter()->implode(' · ')) ?: null,

                    'reminder' => null, 'all_ok' => null, 'all_ok_response' => null,
                    'on_way' => null, 'on_way_response' => null, 'reaching_time' => null,
                    'book_on' => null, 'comments' => null, 'approved_by' => null, 'verified_by' => null,

                    'missing_fields' => $missing,
                    'is_missing'     => true,
                    'is_cancelled'   => false,
                ]);
            }
        }

        return $rows;
    }

    /**
     * V4-P4: add a brand-new shift to an event on a date. That means one
     * more required guard slot for the day (daily_guards + 1) and then the
     * SAME save the event page uses (EventController@saveGuardSlot), so
     * validation, share-code expiry check, event_shifts rebuild, guard sync,
     * cancellation preservation and audit all behave identically.
     * All-or-nothing: if the slot save is refused, the count bump is
     * rolled back.
     */
    public function addShift(Request $request)
    {
        $data = $request->validate([
            'event_id'            => 'required|exists:events,id',
            'date'                => 'required|date',
            'guard_id'            => 'nullable|required_without:temp_phone|exists:security_guards,id',
            'temp_phone'          => ['nullable', 'required_without:guard_id', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{7,32}$/'],
            'shift1_start'        => 'required|date_format:H:i',
            'shift1_end'          => 'required|date_format:H:i',
            'break_hours'         => 'required|numeric|in:0,0.25,0.5,0.75,1',
            'shift1_postcode'     => 'nullable|string|max:255',
            'shift1_site_name'    => 'nullable|string|max:255',
            'shift1_site_address' => 'nullable|string|max:255',
        ]);

        $event = Event::findOrFail($data['event_id']);
        $date  = Carbon::parse($data['date'])->toDateString();

        if (($event->start_date && $date < $event->start_date->toDateString())
            || ($event->end_date && $date > $event->end_date->toDateString())) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => ['date' => ['That date is outside the event dates.']],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $daily    = (array) ($event->daily_guards ?? []);
            $maxSlot  = (int) EventAssignmentSlot::query()
                ->where('event_id', $event->id)->whereDate('date', $date)->max('slot_no');
            $slotNo   = max((int) ($daily[$date] ?? 0), $maxSlot) + 1;

            $daily[$date] = $slotNo;
            $event->daily_guards = $daily;
            $event->save();

            $request->merge(['date' => $date, 'slot_no' => $slotNo, 'source' => 'chaseup']);

            $response = app(EventController::class)->saveGuardSlot($request, $event);

            if ($response->getStatusCode() >= 400) {
                DB::rollBack();
                return $response;
            }

            DB::commit();

            try {
                \App\Services\NotificationService::notify(
                    'chaseup',
                    'Chaseup: shift added on ' . $event->event_name,
                    'New shift on ' . $date . ' (slot ' . $slotNo . ').',
                    route('chaseup.index', ['date' => $date], false),
                    ['ops']
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return $response;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateField(Request $request, EventShift $eventShift)
    {
        $validated = $request->validate([
            'field' => 'required|string|in:reminder,all_ok,all_ok_response,on_way,on_way_response,reaching_time,book_on,comments,approved_by,verified_by',
            'value' => 'nullable',
        ]);

        $chaseup = $this->resolveChaseupForShift($eventShift);

        $field = $validated['field'];
        $value = $validated['value'];

        if (in_array($field, ['all_ok', 'on_way'], true)) {
            $value = in_array($value, ['yes', 'no', '', null], true) ? ($value === '' ? null : $value) : null;
        }

        if ($field === 'reaching_time') {
            $value = $value ? substr((string) $value, 0, 5) : null;
        }

        if (in_array($field, ['reminder', 'all_ok_response', 'on_way_response', 'book_on', 'comments', 'approved_by', 'verified_by'], true)) {
            $value = $value !== null ? trim((string) $value) : null;
            if ($value === '') {
                $value = null;
            }
        }

        $chaseup->update([
            $field => $value,
        ]);

        $latest = null;

        if (auth()->user()?->hasRole('Super Admin')) {
            $latest = AuditLog::query()
                ->with('user:id,name')
                ->where('auditable_type', Chaseup::class)
                ->where('auditable_id', $chaseup->id)
                ->latest('created_at')
                ->first();
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Saved successfully.',
            'saved_value'  => $value,
            'actor_name'   => optional($latest?->user)->name,
            'timestamp'    => optional($latest?->created_at)?->format('Y-m-d H:i:s'),
        ]);
    }

    public function latestActor(EventShift $eventShift)
    {
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403);

        $chaseup = $this->findChaseupForShift($eventShift);

        if (!$chaseup) {
            return response()->json([
                'actor_name' => null,
                'timestamp'  => null,
            ]);
        }

        $latest = AuditLog::query()
            ->with('user:id,name')
            ->where('auditable_type', Chaseup::class)
            ->where('auditable_id', $chaseup->id)
            ->latest('created_at')
            ->first();

        return response()->json([
            'actor_name' => optional($latest?->user)->name,
            'timestamp'  => optional($latest?->created_at)?->format('Y-m-d H:i:s'),
        ]);
    }

    public function history(EventShift $eventShift)
    {
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403);

        $chaseup = $this->findChaseupForShift($eventShift);

        if (!$chaseup) {
            return response()->json([
                'history' => [],
            ]);
        }

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->where('auditable_type', Chaseup::class)
            ->where('auditable_id', $chaseup->id)
            ->latest('created_at')
            ->get()
            ->map(function ($log) {
                return [
                    'id'         => (int) $log->id,
                    'action'     => $log->action,
                    'actor_name' => optional($log->user)->name,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'created_at' => optional($log->created_at)?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'history' => $logs,
        ]);
    }

    /**
     * Find-or-create the note for a shift, keyed on the stable assignment identity
     * (event + date + guard). The event_shifts.id is stored only as a convenience
     * pointer to the current row; it is NOT part of the identity.
     */
    protected function resolveChaseupForShift(EventShift $shift): Chaseup
    {
        return Chaseup::updateOrCreate(
            [
                'event_id' => (int) $shift->event_id,
                'date'     => optional($shift->date)->format('Y-m-d'),
                'guard_id' => (int) $shift->guard_id,
            ],
            [
                'event_shift_id' => $shift->id,
            ]
        );
    }

    /**
     * Look up an existing note for a shift by assignment identity, without creating one.
     */
    protected function findChaseupForShift(EventShift $shift): ?Chaseup
    {
        return Chaseup::query()
            ->where('event_id', (int) $shift->event_id)
            ->whereDate('date', optional($shift->date)->format('Y-m-d'))
            ->where('guard_id', (int) $shift->guard_id)
            ->first();
    }

    /**
     * Build the composite key used to match a shift to its note.
     */
    /**
     * Read-only JSON feed of upcoming, unmarked shifts for the in-app
     * reminder poller (no cron). Returns shifts starting within the next
     * ~2h15m (UK time) that have a guard + start time and have NOT been
     * marked (no all_ok / on_way answer). Available to any logged-in user.
     */
    public function reminders(Request $request)
    {
        $today    = now('Europe/London')->toDateString();
        $tomorrow = now('Europe/London')->addDay()->toDateString();

        $shifts = EventShift::query()
            ->with([
                'event:id,event_name,client_id',
                'event.client:id,name',
                'securityGuard:id,fullname',
            ])
            ->whereNull('cancelled_at')
            ->whereNotNull('guard_id')
            ->whereNotNull('start_time')
            ->whereIn('date', [$today, $tomorrow])
            ->get();

        if ($shifts->isEmpty()) {
            return response()->json(['reminders' => []]);
        }

        $chaseupLookup = Chaseup::query()
            ->whereIn('date', [$today, $tomorrow])
            ->whereNotNull('event_id')
            ->whereNotNull('guard_id')
            ->get()
            ->keyBy(fn (Chaseup $c) => $this->identityKey(
                (int) $c->event_id,
                optional($c->date)->format('Y-m-d'),
                (int) $c->guard_id
            ));

        $now = now('Europe/London');

        $reminders = $shifts->map(function (EventShift $shift) use ($chaseupLookup, $now) {
            $dateStr = optional($shift->date)->format('Y-m-d');
            $key     = $this->identityKey((int) $shift->event_id, $dateStr, (int) $shift->guard_id);
            $chaseup = $chaseupLookup->get($key);

            $marked = $chaseup && (
                !in_array($chaseup->all_ok, [null, ''], true) ||
                !in_array($chaseup->on_way, [null, ''], true)
            );
            if ($marked) {
                return null;
            }

            $startTime = substr((string) $shift->start_time, 0, 5);
            $startsAt  = Carbon::parse($dateStr . ' ' . $startTime, 'Europe/London');

            if ($startsAt->lt($now->copy()->subMinutes(5)) || $startsAt->gt($now->copy()->addMinutes(135))) {
                return null;
            }

            $clientName = optional(optional($shift->event)->client)->name;
            $eventName  = optional($shift->event)->event_name;
            $label = trim(collect([$clientName, $eventName])->filter()->implode(' · ')) ?: null;

            return [
                'key'        => $key,
                'guard_name' => optional($shift->securityGuard)->fullname,
                'label'      => $label,
                'location'   => $shift->location,
                'date'       => $dateStr,
                'start_time' => $startTime,
                'starts_at'  => $startsAt->toIso8601String(),
            ];
        })->filter()->values();

        return response()->json(['reminders' => $reminders]);
    }

    protected function identityKey(int $eventId, ?string $date, int $guardId): string
    {
        return implode('|', [$eventId, (string) $date, $guardId]);
    }

    protected function calculateTotalHours(?string $date, $startTime, $endTime, $breakHours): ?float
    {
        if (!$date || !$startTime || !$endTime) {
            return null;
        }

        $start = Carbon::parse($date . ' ' . $startTime);
        $end   = Carbon::parse($date . ' ' . $endTime);

        if ($end->lt($start)) {
            $end->addDay();
        }

        $worked = $end->diffInMinutes($start) / 60;
        $break  = (float) ($breakHours ?? 0);

        return round(max($worked - $break, 0), 2);
    }
}