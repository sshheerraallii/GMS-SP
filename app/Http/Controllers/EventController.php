<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\Client;
use App\Models\SecurityGuard;
use App\Models\Supplier;
use App\Models\EventSchedule; // keep for now (edit/update still uses schedules)
use App\Models\EventShift;
use App\Support\Audit;


class EventController extends Controller
{
    public function index(Request $request)
{
    $perPage = (int) $request->get('per_page', 10);
    $perPage = in_array($perPage, [10, 20, 30], true) ? $perPage : 10;

    $events = Event::with(['client', 'guards', 'supplier'])
        ->orderByDesc('created_at')
        ->paginate($perPage)
        ->withQueryString(); // keep per_page in pagination links

    return view('events.index', compact('events', 'perPage'));
}

    public function create()
{
    return view('events.create', [
        'clients'   => Client::all(),
        'guards'    => SecurityGuard::all(),
        'suppliers' => Supplier::all(),
        'clientTypes' => ['VAT' => 'VAT', 'NON_VAT' => 'Non VAT'],
    ]);
}

    /**
     * PAGE 2 – Guard assignment
     */
   public function guards(Event $event)
{
    $event->load(['shifts.securityGuard']); // prefill page 2

    $dailyGuards = collect($event->daily_guards ?? [])
        ->map(fn ($v) => (int) $v)
        ->toArray();

    // Build assignments for the blade prefill:
    // $assignments[date][index] = { guard_id, shift1_start, shift1_end, shift2_start, shift2_end }
    $assignments = [];

    foreach ($dailyGuards as $date => $count) {
        $count = (int) $count;
        if ($count <= 0) continue;

        // Get all shifts for this date, group by guard_id
        $byGuard = $event->shifts
            ->where('date', $date)
            ->groupBy('guard_id');

        // Turn into rows (one row per guard) with up to 2 shifts
        $rows = [];
        foreach ($byGuard as $guardId => $shifts) {
            $sorted = $shifts->sortBy('start_time')->values();

            $rows[] = [
                'guard_id'    => (int) $guardId,

                'shift1_start'=> optional($sorted->get(0))->start_time,
                'shift1_end'  => optional($sorted->get(0))->end_time,

                'shift2_start'=> optional($sorted->get(1))->start_time,
                'shift2_end'  => optional($sorted->get(1))->end_time,
            ];
        }

        // Ensure the array length matches required count (pad with empty rows)
        while (count($rows) < $count) {
            $rows[] = [
                'guard_id'     => null,
                'shift1_start' => null,
                'shift1_end'   => null,
                'shift2_start' => null,
                'shift2_end'   => null,
            ];
        }

        // If there are more rows than required (because data exists), trim
        $assignments[$date] = array_slice($rows, 0, $count);
    }

    return view('events.guards', [
        'event'       => $event,
        'guards'      => SecurityGuard::select('id', 'fullname', 'license_number')->get()->toArray(),
        'dailyGuards' => $dailyGuards,
        'assignments' => $assignments,
    ]);
}


    /**
     * AJAX – Save assignments (per date, per guard, per shift) into event_shifts
     */
    public function updateGuards(Request $request, Event $event)
{
    // ----------------------------
    // AUDIT: capture OLD state
    // ----------------------------
    $oldGuards = $event->guards()
        ->pluck('security_guards.id')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    $oldShifts = $event->shifts()
        ->orderBy('date')
        ->orderBy('guard_id')
        ->orderBy('shift_no')
        ->get(['date', 'guard_id', 'shift_no', 'start_time', 'end_time'])
        ->map(function ($s) {
            return [
                'date'       => (string) $s->date,
                'guard_id'   => (int) $s->guard_id,
                'shift_no'   => (int) $s->shift_no,
                'start_time' => (string) $s->start_time,
                'end_time'   => (string) $s->end_time,
            ];
        })
        ->all();

    $assignments = $request->input('assignments', []);

    foreach ($assignments as $date => $rows) {
        foreach ($rows as $i => $row) {
            foreach (['shift1_start','shift1_end','shift2_start','shift2_end'] as $key) {
                if (!empty($row[$key])) {
                    // "18:24:00" -> "18:24"
                    $assignments[$date][$i][$key] = substr((string) $row[$key], 0, 5);
                }
            }
        }
    }

    $request->merge(['assignments' => $assignments]);

    $validated = $request->validate([
        'assignments' => 'required|array',

        'assignments.*.*.guard_id' => 'required|exists:security_guards,id',

        'assignments.*.*.shift1_start' => 'nullable|date_format:H:i',
        'assignments.*.*.shift1_end'   => 'nullable|date_format:H:i|after_or_equal:shift1_start',

        'assignments.*.*.shift2_start' => 'nullable|date_format:H:i',
        'assignments.*.*.shift2_end'   => 'nullable|date_format:H:i|after_or_equal:shift2_start',
    ]);

    DB::transaction(function () use ($validated, $event) {

        // Clear existing assignments for this event
        $event->guards()->detach();
        $event->shifts()->delete(); // deletes event_shifts rows

        foreach ($validated['assignments'] as $date => $guards) {
            foreach ($guards as $g) {

                // Attach guard once (safe if repeated)
                $event->guards()->syncWithoutDetaching([$g['guard_id']]);

                // Shift 1 (prevent duplicates even if frontend repeats payload)
                if (!empty($g['shift1_start']) && !empty($g['shift1_end'])) {
                    EventShift::firstOrCreate([
                        'event_id'   => $event->id,
                        'guard_id'   => $g['guard_id'],
                        'date'       => $date,
                        'start_time' => $g['shift1_start'],
                        'end_time'   => $g['shift1_end'],
                        'shift_no'   => 1,
                    ]);
                }

                // Shift 2 (optional)
                if (!empty($g['shift2_start']) && !empty($g['shift2_end'])) {
                    EventShift::firstOrCreate([
                        'event_id'   => $event->id,
                        'guard_id'   => $g['guard_id'],
                        'date'       => $date,
                        'start_time' => $g['shift2_start'],
                        'end_time'   => $g['shift2_end'],
                        'shift_no'   => 2,
                    ]);
                }
            }
        }
    });

    // ----------------------------
    // AUDIT: capture NEW state
    // ----------------------------
    $newGuards = $event->guards()
        ->pluck('security_guards.id')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    $newShifts = $event->shifts()
        ->orderBy('date')
        ->orderBy('guard_id')
        ->orderBy('shift_no')
        ->get(['date', 'guard_id', 'shift_no', 'start_time', 'end_time'])
        ->map(function ($s) {
            return [
                'date'       => (string) $s->date,
                'guard_id'   => (int) $s->guard_id,
                'shift_no'   => (int) $s->shift_no,
                'start_time' => (string) $s->start_time,
                'end_time'   => (string) $s->end_time,
            ];
        })
        ->all();

    // One log entry per save
    Audit::log(
        'assignments_saved',
        \App\Models\Event::class,
        (int) $event->id,
        ['guards' => $oldGuards, 'shifts' => $oldShifts],
        ['guards' => $newGuards, 'shifts' => $newShifts],
        $request
    );

    // AJAX response (NO redirect)
    return response()->json([
        'success' => true,
        'message' => 'Guard assignments saved successfully',
    ], 200);
}


    /**
     * PAGE 1 – Save draft + go to page 2
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_name'      => 'required|string|max:255',
            'address'         => 'nullable|string',
            'client_id'       => 'required|exists:clients,id',
            'client_type'     => 'required|in:VAT,NON_VAT',
            'supplier_id'     => 'nullable|exists:suppliers,id',
            'payment_terms'   => 'nullable|string',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'charge_rate'     => 'nullable|numeric',
            'invoice_date'    => 'nullable|date',
            'pay_rate'        => 'nullable|numeric',
            'client_contact'  => 'nullable|string',
            'instructions'    => 'nullable|string',
            'shift_mode'      => 'required|in:same,different',
            'daily_guards'    => 'required|array',
        ]);

        $event = Event::create([
            'event_name'     => $validated['event_name'],
            'address'        => $validated['address'] ?? null,
            'client_id'      => $validated['client_id'],
            'client_type'   => $validated['client_type'],
            'supplier_id'    => $validated['supplier_id'] ?? null,
            'payment_terms'  => $validated['payment_terms'] ?? null,
            'start_date'     => $validated['start_date'],
            'end_date'       => $validated['end_date'],
            'charge_rate'    => $validated['charge_rate'] ?? null,
            'invoice_date'   => $validated['invoice_date'] ?? null,
            'pay_rate'       => $validated['pay_rate'] ?? null,
            'client_contact' => $validated['client_contact'] ?? null,
            'instructions'   => $validated['instructions'] ?? null,
            'daily_guards'   => $validated['daily_guards'],
            'shift_mode'     => $validated['shift_mode'],
        ]);

        return redirect()->route('events.guards', $event);
    }

  public function edit(Event $event)
{
    // Keeping schedules here for now since your edit form expects it (legacy).
    $event->load(['guards', 'schedules']);

    return view('events.edit', [
        'event'     => $event,
        'clients'   => Client::all(),
        'guards'    => SecurityGuard::all(),
        'suppliers' => Supplier::all(),
        'clientTypes' => ['VAT' => 'VAT', 'NON_VAT' => 'Non VAT'],
    ]);
}


  public function update(Request $request, Event $event)
{
    $validated = $request->validate([
        'event_name'     => 'required|string|max:255',
        'address'        => 'nullable|string|max:500',
        'client_id'      => 'required|exists:clients,id',
        'client_type'    => 'required|in:VAT,NON_VAT',
        'supplier_id'    => 'nullable|exists:suppliers,id',
        'payment_terms'  => 'nullable|string|in:weekly,biweekly,monthly,days_55,weeks_5',

        'charge_rate'    => 'nullable|numeric',
        'invoice_date'   => 'nullable|date',
        'pay_rate'       => 'nullable|numeric',
        'client_contact' => 'nullable|string|max:50',
        'instructions'   => 'nullable|string',

        'start_date'     => 'required|date',
        'end_date'       => 'required|date|after_or_equal:start_date',

        'shift_mode'     => 'required|in:same,different',
        'daily_guards'   => 'required|array',
        'daily_guards.*' => 'nullable|integer|min:0',

        // Optional: keep guard list if you still want “selected guards” on page 1
        'guards'         => 'nullable|array',
        'guards.*'       => 'exists:security_guards,id',
    ]);

    DB::transaction(function () use ($event, $validated) {
        // update only event fields
        $event->update([
            'event_name'     => $validated['event_name'],
            'address'        => $validated['address'] ?? null,
            'client_id'      => $validated['client_id'],
            'client_type'    => $validated['client_type'],
            'supplier_id'    => $validated['supplier_id'] ?? null,
            'payment_terms'  => $validated['payment_terms'] ?? null,
            'charge_rate'    => $validated['charge_rate'] ?? null,
            'invoice_date'   => $validated['invoice_date'] ?? null,
            'pay_rate'       => $validated['pay_rate'] ?? null,
            'client_contact' => $validated['client_contact'] ?? null,
            'instructions'   => $validated['instructions'] ?? null,
            'start_date'     => $validated['start_date'],
            'end_date'       => $validated['end_date'],
            'shift_mode'     => $validated['shift_mode'],
            'daily_guards'   => $validated['daily_guards'],
        ]);

        // Optional: if page 1 allows selecting guards, keep it
        if (array_key_exists('guards', $validated)) {
            $event->guards()->sync($validated['guards'] ?? []);
        }
    });

    // After page 1 update, go to page 2 so rows regenerate
    return $request->redirect_to === 'guards'
    ? redirect()->route('events.guards', $event)
    : redirect()->route('events.index')
        ->with('success', 'Event updated successfully!');

}


    public function show(Event $event)
    {
        $event->load([
            'client',
            'supplier',
            'guards',
            'shifts.securityGuard',
        ]);

        return view('events.show', compact('event'));
    }

    public function destroy(Event $event)
{
    $event->guards()->detach();
    $event->schedules()->delete(); // legacy
    $event->shifts()->delete();    // new
    $event->delete();

    return redirect()
        ->route('events.index')
        ->with('success', 'Event deleted successfully.');
}

}
