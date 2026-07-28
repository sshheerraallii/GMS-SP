<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Chaseup;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
                'event.client:id,name',
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

                'missing_fields'     => $missingFields,
                'is_missing'         => !empty($missingFields),
            ];
        });

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

        return view('chaseup.index', [
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

    public function updateField(Request $request, EventShift $eventShift)
    {
        $validated = $request->validate([
            'field' => 'required|string|in:reminder,all_ok,all_ok_response,on_way,on_way_response,reaching_time,book_on,comments',
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

        if (in_array($field, ['reminder', 'all_ok_response', 'on_way_response', 'book_on', 'comments'], true)) {
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