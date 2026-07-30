<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\SecurityGuard;
use App\Models\Supplier;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Collection;
use App\Exports\EventStaffSheetExport;
use App\Exports\EventStaffPaymentSheetExport;
use App\Exports\EventTimeSheetExport;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 30], true) ? $perPage : 10;

        $q = trim((string) $request->get('q', ''));

        $eventsQuery = Event::query()
            ->with(['client', 'guards', 'supplier', 'shifts'])
            ->orderByDesc('created_at');

        if ($q !== '') {
            $eventsQuery->where(function ($sub) use ($q) {
                $sub->where('event_name', 'like', '%' . $q . '%')
                    ->orWhere('address', 'like', '%' . $q . '%')
                    ->orWhereHas('client', function ($c) use ($q) {
                        $c->where('name', 'like', '%' . $q . '%');
                    });
            });
        }

        $events = $eventsQuery
            ->paginate($perPage)
            ->withQueryString();

        $events->getCollection()->transform(function ($event) {
            $totalHours = (float) $event->shifts->sum(function ($shift) {
                if (!$shift->start_time || !$shift->end_time || !$shift->date) {
                    return 0;
                }

                $start = Carbon::parse($shift->date->format('Y-m-d') . ' ' . $shift->start_time);
                $end = Carbon::parse($shift->date->format('Y-m-d') . ' ' . $shift->end_time);

                if ($end->lt($start)) {
                    $end->addDay();
                }

                $worked = $end->diffInMinutes($start) / 60;
                $break = (float) ($shift->break_hours ?? 0);

                return max($worked - $break, 0);
            });

            $event->calculated_total_hours = round($totalHours, 2);

            return $event;
        });

        return view('events.index', compact('events', 'perPage'));
    }

    public function create()
    {
        return view('events.create', [
            'clients'     => Client::query()->orderBy('name')->get(),
            'guards'      => SecurityGuard::query()->orderBy('fullname')->get(),
            'suppliers'   => Supplier::query()->orderBy('name')->get(),
            'clientTypes' => ['VAT' => 'VAT', 'NON_VAT' => 'Non VAT'],
        ]);
    }
    
    
    
    
    
    
           public function importPreview(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls',
        ]);

        $importedRows = $this->parseImportedEventFile($request->file('import_file'));

        if ($importedRows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid rows found in Excel. Make sure the file contains Date, Start Time, End Time, and optional Break Hours.',
            ], 422);
        }

        $dailyGuards = $importedRows
            ->groupBy('date')
            ->map(fn ($rows) => $rows->count())
            ->sortKeys();

        $firstStart = $importedRows->first()['start_time'] ?? null;
        $firstEnd   = $importedRows->first()['end_time'] ?? null;

        $allSameTimes = $importedRows->every(function ($row) use ($firstStart, $firstEnd) {
            return ($row['start_time'] ?? null) === $firstStart
                && ($row['end_time'] ?? null) === $firstEnd;
        });

        return response()->json([
            'success'      => true,
            'start_date'   => $importedRows->min('date'),
            'end_date'     => $importedRows->max('date'),
            'daily_guards' => $dailyGuards,
            'row_count'    => $importedRows->count(),
            'shift_mode'   => $allSameTimes ? 'same' : 'different',
            'global_start' => $allSameTimes ? $firstStart : null,
            'global_end'   => $allSameTimes ? $firstEnd : null,
        ]);
    }
    
    
    
    
    

      public function store(Request $request)
    {
        $validated = $request->validate([
            'event_name'      => 'required|string|max:255',
            'address'         => 'nullable|string|max:500',
            'client_id'       => 'required|exists:clients,id',
            'client_type'     => 'required|in:VAT,NON_VAT',
            'supplier_id'     => 'nullable|exists:suppliers,id',
            'payment_terms'   => 'nullable|string',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'charge_rate'     => 'nullable|numeric',
            'invoice_date'    => 'nullable|date',
            'pay_rate'        => 'nullable|numeric',
            'client_contact'  => 'nullable|string|max:100',
            'instructions'    => 'nullable|string',
            'shift_mode'      => 'required|in:same,different',
            'daily_guards'    => 'nullable|array',
            'daily_guards.*'  => 'nullable|integer|min:0',
            'import_file'     => 'nullable|file|mimes:xlsx,xls',
        ]);

        $importedRows = collect();
        $dailyGuards = collect($validated['daily_guards'] ?? [])
            ->mapWithKeys(fn ($count, $date) => [$date => (int) $count])
            ->filter(fn ($count) => $count > 0);

        $startDate = $validated['start_date'] ?? null;
        $endDate   = $validated['end_date'] ?? null;

        if ($request->hasFile('import_file')) {
            $importedRows = $this->parseImportedEventFile($request->file('import_file'));
            
                        $firstStart = $importedRows->first()['start_time'] ?? null;
            $firstEnd   = $importedRows->first()['end_time'] ?? null;

            $allSameTimes = $importedRows->every(function ($row) use ($firstStart, $firstEnd) {
                return ($row['start_time'] ?? null) === $firstStart
                    && ($row['end_time'] ?? null) === $firstEnd;
            });

            $validated['shift_mode'] = $allSameTimes ? 'same' : 'different';
            
            

            if ($importedRows->isEmpty()) {
                return back()
                    ->withErrors([
                        'import_file' => 'No valid rows found in Excel. Make sure the file contains Date, Start Time, End Time, and optional Break Hours.',
                    ])
                    ->withInput();
            }

            $dailyGuards = $importedRows
                ->groupBy('date')
                ->map(fn ($rows) => $rows->count())
                ->sortKeys();

            $startDate = $importedRows->min('date');
            $endDate   = $importedRows->max('date');
        }

        if (empty($startDate) || empty($endDate)) {
            return back()
                ->withErrors([
                    'start_date' => 'Start Date and End Date are required when no Excel file is uploaded.',
                ])
                ->withInput();
        }

        if ($dailyGuards->isEmpty()) {
            return back()
                ->withErrors([
                    'daily_guards' => 'At least one day must have guards greater than 0, or upload a valid Excel file.',
                ])
                ->withInput();
        }

        $event = DB::transaction(function () use ($validated, $dailyGuards, $startDate, $endDate, $importedRows) {
            $event = Event::query()->create([
                'event_name'      => $validated['event_name'],
                'address'         => $validated['address'] ?? null,
                'client_id'       => $validated['client_id'],
                'client_type'     => $validated['client_type'],
                'supplier_id'     => $validated['supplier_id'] ?? null,
                'payment_terms'   => $validated['payment_terms'] ?? null,
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'charge_rate'     => $validated['charge_rate'] ?? null,
                'invoice_date'    => $validated['invoice_date'] ?? null,
                'pay_rate'        => $validated['pay_rate'] ?? null,
                'client_contact'  => $validated['client_contact'] ?? null,
                'instructions'    => $validated['instructions'] ?? null,
                'daily_guards'    => $dailyGuards->toArray(),
                'shift_mode'      => $validated['shift_mode'],
            ]);

            if ($importedRows->isNotEmpty()) {
                foreach ($importedRows as $index => $row) {
                    $slotNo = $this->nextSlotNumberForDate($importedRows, $row['date'], $index);

                   $event->assignmentSlots()->create([
    'date'        => $row['date'],
    'slot_no'     => $slotNo,
    'guard_id'    => null,
    'start_time'  => $row['start_time'],
    'end_time'    => $row['end_time'],
    'break_hours' => $row['break_hours'],
    'location'    => $row['location'] ?? null,
]);
                }
            }

            return $event;
        });

        return redirect()->route('events.guards', $event);
    }
    
    
    
        private function parseImportedEventFile($file): Collection
    {
        $rows = Excel::toArray([], $file)[0] ?? [];

        if (empty($rows) || count($rows) < 2) {
            return collect();
        }

        $headerRow = array_shift($rows);
        $headers = collect($headerRow)->map(function ($header) {
            return $this->normalizeImportHeader($header);
        })->values()->all();

        $mapped = collect();

        foreach ($rows as $rowIndex => $row) {
            $assoc = [];

            foreach ($headers as $i => $header) {
                if ($header === '') {
                    continue;
                }

                $assoc[$header] = $row[$i] ?? null;
            }

       $dateValue = $assoc['date'] ?? null;
$startValue = $assoc['start_time'] ?? $assoc['start'] ?? null;
$endValue = $assoc['end_time'] ?? $assoc['end'] ?? null;
$breakValue = $assoc['break_hours'] ?? $assoc['break'] ?? null;
$locationValue = $assoc['location'] ?? $assoc['shift_location'] ?? $assoc['site_location'] ?? null;

            $normalizedDate = $this->normalizeImportedDate($dateValue);
            $normalizedStart = $this->normalizeImportedTime($startValue);
            $normalizedEnd = $this->normalizeImportedTime($endValue);
            $normalizedBreak = $this->normalizeImportedBreak($breakValue);

            if (!$normalizedDate) {
    continue;
}

           $mapped->push([
    'date'        => $normalizedDate,
    'start_time'  => $normalizedStart,
    'end_time'    => $normalizedEnd,
    'break_hours' => $normalizedBreak,
    'location'    => $this->normalizeImportedLocation($locationValue),
]);
        }

        return $mapped->values();
    }

    private function normalizeImportHeader($value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim($value, '_');

        return match ($value) {
    'date' => 'date',
    'start_time', 'start', 'starttime' => 'start_time',
    'end_time', 'end', 'endtime', 'finish_time', 'finish' => 'end_time',
    'break_hours', 'break', 'break_hour', 'breakhrs', 'break_hrs' => 'break_hours',
    'location', 'shift_location', 'site_location', 'venue_location' => 'location',
    default => $value,
};
    }
    
    
    private function normalizeImportedLocation($value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string) $value);

    return $value === '' ? null : mb_substr($value, 0, 255);
}
    
    
    

    private function normalizeImportedDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            }

            $value = trim((string) $value);

            $formats = [
                'Y-m-d',
                'd-m-Y',
                'd/m/Y',
                'm/d/Y',
                'd.m.Y',
                'j M Y',
                'j F Y',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('Y-m-d');
                } catch (\Throwable $e) {
                }
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeImportedTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('H:i');
            }

            $value = trim((string) $value);

            $formats = [
                'H:i',
                'H:i:s',
                'g:i A',
                'g:iA',
                'h:i A',
                'h:iA',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('H:i');
                } catch (\Throwable $e) {
                }
            }

            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeImportedBreak($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $value = trim((string) $value);

        $map = [
            '0' => 0.0,
            '0.00' => 0.0,
            '0.25' => 0.25,
            '0.5' => 0.5,
            '0.50' => 0.5,
            '0.75' => 0.75,
            '1' => 1.0,
            '1.0' => 1.0,
            '1.00' => 1.0,
            '15' => 0.25,
            '15m' => 0.25,
            '15min' => 0.25,
            '15 mins' => 0.25,
            '30' => 0.5,
            '30m' => 0.5,
            '30min' => 0.5,
            '30 mins' => 0.5,
            '45' => 0.75,
            '45m' => 0.75,
            '45min' => 0.75,
            '45 mins' => 0.75,
            '60' => 1.0,
            '60m' => 1.0,
            '60min' => 1.0,
            '60 mins' => 1.0,
        ];

        $key = strtolower($value);

        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        $float = (float) $value;

        return in_array($float, [0.0, 0.25, 0.5, 0.75, 1.0], true) ? $float : 0.0;
    }

    private function nextSlotNumberForDate(Collection $rows, string $date, int $currentIndex): int
    {
        $count = 0;

        for ($i = 0; $i <= $currentIndex; $i++) {
            if (($rows[$i]['date'] ?? null) === $date) {
                $count++;
            }
        }

        return $count;
    }
    

    /**
     * Compose the combined `location` string from the three structured
     * site fields, in the order Site Name, Postcode, Site Address.
     * Empty segments are dropped. Returns null when all are empty.
     */
    private function composeLocation($siteName, $postcode, $siteAddress): ?string
    {
        $parts = array_filter(
            [trim((string) $siteName), trim((string) $postcode), trim((string) $siteAddress)],
            fn ($p) => $p !== ''
        );

        return count($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Snapshot an event's current shift cancellations, keyed by
     * "date|guard_id" => original cancelled_at. Used to survive the
     * delete-and-recreate save pattern (cancelled_at lives on a shift
     * row that is destroyed on every save).
     */
    private function snapshotCancellations(Event $event): array
    {
        return EventShift::query()
            ->where('event_id', $event->id)
            ->whereNotNull('cancelled_at')
            ->whereNotNull('guard_id')
            ->get(['date', 'guard_id', 'cancelled_at'])
            ->mapWithKeys(fn ($s) => [
                optional($s->date)->toDateString() . '|' . (int) $s->guard_id => $s->cancelled_at,
            ])
            ->all();
    }

    /**
     * Re-apply a cancellation snapshot to freshly-recreated shifts,
     * preserving the original cancelled_at timestamp.
     */
    private function restoreCancellations(Event $event, array $snapshot): void
    {
        foreach ($snapshot as $key => $cancelledAt) {
            [$date, $guardId] = explode('|', $key, 2);

            EventShift::query()
                ->where('event_id', $event->id)
                ->whereDate('date', $date)
                ->where('guard_id', (int) $guardId)
                ->update(['cancelled_at' => $cancelledAt]);
        }
    }

    /**
     * Toggle the soft-cancel state of a guard's shift on a date.
     * Reversible; keyed by the stable business identity
     * (event_id, date, guard_id), never the volatile shift id.
     */
    public function toggleCancelShift(Request $request, Event $event)
    {
        $data = $request->validate([
            'date'     => 'required|date',
            'guard_id' => 'required|integer|exists:security_guards,id',
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $guardId = (int) $data['guard_id'];

        $shifts = EventShift::query()
            ->where('event_id', $event->id)
            ->whereDate('date', $date)
            ->where('guard_id', $guardId)
            ->get();

        if ($shifts->isEmpty()) {
            return back()->withErrors(['cancel' => 'No shift found for that guard on that date.']);
        }

        $currentlyCancelled = $shifts->every(fn ($s) => $s->cancelled_at !== null);

        EventShift::query()
            ->where('event_id', $event->id)
            ->whereDate('date', $date)
            ->where('guard_id', $guardId)
            ->update(['cancelled_at' => $currentlyCancelled ? null : now()]);

        return back()->with('success', $currentlyCancelled ? 'Shift un-cancelled.' : 'Shift cancelled.');
    }

    public function guards(Event $event)
    {
        $event->load(['assignmentSlots.securityGuard']);

        $dailyGuards = collect($event->daily_guards ?? [])
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $slotsByDate = $event->assignmentSlots
            ->sortBy('slot_no')
            ->groupBy(fn ($slot) => optional($slot->date)->toDateString());

        $assignments = [];

        foreach ($dailyGuards as $date => $requiredCount) {
            $requiredCount = (int) $requiredCount;

            if ($requiredCount <= 0) {
                continue;
            }

            $rows = [];
            $dateSlots = $slotsByDate->get($date, collect())->sortBy('slot_no');

            foreach ($dateSlots as $slot) {
                $rows[] = [
                    'guard_id'            => $slot->guard_id,
                    'shift1_start'        => $slot->start_time,
                    'shift1_end'          => $slot->end_time,
                    'break_hours'         => $slot->break_hours ?? 0,
                    'shift1_location'     => $slot->location,
                    'shift1_postcode'     => $slot->site_postcode,
                    'shift1_site_name'    => $slot->site_name,
                    'shift1_site_address' => $slot->site_address,
                ];
            }

            while (count($rows) < $requiredCount) {
                $rows[] = [
                    'guard_id'            => null,
                    'shift1_start'        => null,
                    'shift1_end'          => null,
                    'break_hours'         => 0,
                    'shift1_location'     => null,
                    'shift1_postcode'     => null,
                    'shift1_site_name'    => null,
                    'shift1_site_address' => null,
                ];
            }

            $assignments[$date] = array_slice($rows, 0, $requiredCount);
        }

        return view('events.guards', [
            'event'       => $event,
            'guards'      => SecurityGuard::query()
                ->select('id', 'fullname', 'license_number', 'share_code_expiry')
                ->orderBy('fullname')
                ->get()
                ->toArray(),
            'dailyGuards' => $dailyGuards,
            'assignments' => $assignments,
        ]);
    }

    public function updateGuards(Request $request, Event $event)
    {
        $oldGuards = $event->guards()
            ->pluck('security_guards.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $oldShifts = $event->shifts()
            ->orderBy('date')
            ->orderBy('guard_id')
            ->orderBy('shift_no')
            ->get(['date', 'guard_id', 'shift_no', 'start_time', 'end_time', 'break_hours', 'location'])
            ->map(fn ($s) => [
                'date'        => optional($s->date)->toDateString(),
                'guard_id'    => (int) $s->guard_id,
                'shift_no'    => (int) $s->shift_no,
                'start_time'  => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
                'end_time'    => $s->end_time ? substr((string) $s->end_time, 0, 5) : null,
                'break_hours' => $s->break_hours !== null ? (float) $s->break_hours : 0,
                'location'    => $s->location,
            ])
            ->all();

        $oldSlots = $event->assignmentSlots()
            ->orderBy('date')
            ->orderBy('slot_no')
            ->get(['date', 'slot_no', 'guard_id', 'start_time', 'end_time', 'break_hours', 'location'])
            ->map(fn ($s) => [
                'date'        => optional($s->date)->toDateString(),
                'slot_no'     => (int) $s->slot_no,
                'guard_id'    => $s->guard_id ? (int) $s->guard_id : null,
                'start_time'  => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
                'end_time'    => $s->end_time ? substr((string) $s->end_time, 0, 5) : null,
                'break_hours' => $s->break_hours !== null ? (float) $s->break_hours : 0,
                'location'    => $s->location,
            ])
            ->all();

        $assignments = (array) $request->input('assignments', []);

        foreach ($assignments as $date => $rows) {
            foreach ((array) $rows as $i => $row) {
                foreach (['shift1_start', 'shift1_end'] as $key) {
                    if (!empty($row[$key])) {
                        $assignments[$date][$i][$key] = substr((string) $row[$key], 0, 5);
                    } else {
                        $assignments[$date][$i][$key] = null;
                    }
                }

                $assignments[$date][$i]['guard_id'] = !empty($row['guard_id'])
                    ? (int) $row['guard_id']
                    : null;

                $assignments[$date][$i]['break_hours'] = isset($row['break_hours']) && $row['break_hours'] !== ''
                    ? (float) $row['break_hours']
                    : 0;

                if (!in_array($assignments[$date][$i]['break_hours'], [0.0, 0.25, 0.5, 0.75, 1.0], true)) {
                    $assignments[$date][$i]['break_hours'] = 0;
                }

                foreach (['shift1_postcode', 'shift1_site_name', 'shift1_site_address'] as $siteKey) {
                    $assignments[$date][$i][$siteKey] = array_key_exists($siteKey, $row)
                        ? trim((string) ($row[$siteKey] ?? ''))
                        : null;

                    if ($assignments[$date][$i][$siteKey] === '') {
                        $assignments[$date][$i][$siteKey] = null;
                    }
                }
            }
        }

        $request->merge(['assignments' => $assignments]);

        $validated = $request->validate([
            'assignments'                     => 'required|array',
            'assignments.*'                   => 'array',
            'assignments.*.*.guard_id'        => 'nullable|exists:security_guards,id',
            'assignments.*.*.shift1_start'    => 'nullable|date_format:H:i',
           'assignments.*.*.shift1_end'      => 'nullable|date_format:H:i',
            'assignments.*.*.break_hours'     => 'nullable|numeric|in:0,0.25,0.5,0.75,1',
            'assignments.*.*.shift1_postcode'     => 'nullable|string|max:255',
            'assignments.*.*.shift1_site_name'    => 'nullable|string|max:255',
            'assignments.*.*.shift1_site_address' => 'nullable|string|max:255',
        ]);

        $alreadyAssignedGuardIds = $event->guards()
            ->pluck('security_guards.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $requestedGuardIds = collect($validated['assignments'])
            ->flatten(1)
            ->pluck('guard_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $guardsById = SecurityGuard::query()
            ->whereIn('id', $requestedGuardIds)
            ->get(['id', 'fullname', 'share_code_expiry'])
            ->keyBy('id');

        foreach ($requestedGuardIds as $guardId) {
            $guard = $guardsById->get($guardId);

            if (!$guard) {
                continue;
            }

            $isExpired = !empty($guard->share_code_expiry)
                && Carbon::parse($guard->share_code_expiry)->startOfDay()->lt(now()->startOfDay());

            $wasAlreadyAssignedToThisEvent = in_array((int) $guardId, $alreadyAssignedGuardIds, true);

            if ($isExpired && !$wasAlreadyAssignedToThisEvent) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => [
                        'assignments' => [
                            "Guard {$guard->fullname} cannot be newly assigned because the Share-Code Expiry date has passed."
                        ],
                    ],
                ], 422);
            }
        }

        // Grandfather map: existing composed location keyed by date|guard,
        // used as fallback when the new 3-field site inputs are left empty
        // (so re-saving legacy events does not blank their location).
        $oldLocationByDateGuard = $event->assignmentSlots()
            ->get(['date', 'guard_id', 'location'])
            ->filter(fn ($s) => $s->guard_id)
            ->mapWithKeys(fn ($s) => [
                optional($s->date)->toDateString() . '|' . (int) $s->guard_id => $s->location,
            ])
            ->all();

        $cancellationSnapshot = $this->snapshotCancellations($event);

        DB::transaction(function () use ($validated, $event, $requestedGuardIds, $oldLocationByDateGuard, $cancellationSnapshot) {
            $event->assignmentSlots()->delete();
            $event->guards()->detach();
            $event->shifts()->delete();

            foreach ($validated['assignments'] as $date => $rows) {
                foreach (array_values($rows) as $index => $row) {
                    $composed = $this->composeLocation($row['shift1_site_name'] ?? null, $row['shift1_postcode'] ?? null, $row['shift1_site_address'] ?? null);
                    $finalLocation = $composed ?? (!empty($row['guard_id']) ? ($oldLocationByDateGuard[$date . '|' . (int) $row['guard_id']] ?? null) : null);

                    $event->assignmentSlots()->create([
                        'date'          => $date,
                        'slot_no'       => $index + 1,
                        'guard_id'      => $row['guard_id'] ?? null,
                        'start_time'    => $row['shift1_start'] ?? null,
                        'end_time'      => $row['shift1_end'] ?? null,
                        'break_hours'   => $row['break_hours'] ?? 0,
                        'site_postcode' => $row['shift1_postcode'] ?? null,
                        'site_name'     => $row['shift1_site_name'] ?? null,
                        'site_address'  => $row['shift1_site_address'] ?? null,
                        'location'      => $finalLocation,
                    ]);
                }
            }

            if ($requestedGuardIds->isNotEmpty()) {
                $event->guards()->sync($requestedGuardIds->all());
            }

            foreach ($validated['assignments'] as $date => $rows) {
                foreach ($rows as $row) {
                    $guardId = $row['guard_id'] ?? null;

                    if (!empty($guardId) && !empty($row['shift1_start']) && !empty($row['shift1_end'])) {
                        $composed = $this->composeLocation($row['shift1_site_name'] ?? null, $row['shift1_postcode'] ?? null, $row['shift1_site_address'] ?? null);
                        $finalLocation = $composed ?? ($oldLocationByDateGuard[$date . '|' . (int) $guardId] ?? null);

                        EventShift::create([
                            'event_id'      => $event->id,
                            'guard_id'      => (int) $guardId,
                            'date'          => $date,
                            'start_time'    => $row['shift1_start'],
                            'end_time'      => $row['shift1_end'],
                            'break_hours'   => $row['break_hours'] ?? 0,
                            'shift_no'      => 1,
                            'site_postcode' => $row['shift1_postcode'] ?? null,
                            'site_name'     => $row['shift1_site_name'] ?? null,
                            'site_address'  => $row['shift1_site_address'] ?? null,
                            'location'      => $finalLocation,
                        ]);
                    }
                }
            }

            $this->restoreCancellations($event, $cancellationSnapshot);
        });

        $event->load(['guards', 'shifts', 'assignmentSlots']);

        $newGuards = $event->guards()
            ->pluck('security_guards.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $newShifts = $event->shifts()
            ->orderBy('date')
            ->orderBy('guard_id')
            ->orderBy('shift_no')
            ->get(['date', 'guard_id', 'shift_no', 'start_time', 'end_time', 'break_hours', 'location'])
            ->map(fn ($s) => [
                'date'        => optional($s->date)->toDateString(),
                'guard_id'    => (int) $s->guard_id,
                'shift_no'    => (int) $s->shift_no,
                'start_time'  => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
                'end_time'    => $s->end_time ? substr((string) $s->end_time, 0, 5) : null,
                'break_hours' => $s->break_hours !== null ? (float) $s->break_hours : 0,
                'location'    => $s->location,
            ])
            ->all();

        $newSlots = $event->assignmentSlots()
            ->orderBy('date')
            ->orderBy('slot_no')
            ->get(['date', 'slot_no', 'guard_id', 'start_time', 'end_time', 'break_hours', 'location'])
            ->map(fn ($s) => [
                'date'        => optional($s->date)->toDateString(),
                'slot_no'     => (int) $s->slot_no,
                'guard_id'    => $s->guard_id ? (int) $s->guard_id : null,
                'start_time'  => $s->start_time ? substr((string) $s->start_time, 0, 5) : null,
                'end_time'    => $s->end_time ? substr((string) $s->end_time, 0, 5) : null,
                'break_hours' => $s->break_hours !== null ? (float) $s->break_hours : 0,
                'location'    => $s->location,
            ])
            ->all();

        Audit::log(
            'assignments_saved',
            Event::class,
            (int) $event->id,
            [
                'guards' => $oldGuards,
                'shifts' => $oldShifts,
                'slots'  => $oldSlots,
            ],
            [
                'guards' => $newGuards,
                'shifts' => $newShifts,
                'slots'  => $newSlots,
            ],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Guard assignments saved successfully',
        ], 200);
    }
    
    
    
    
    public function saveGuardSlot(Request $request, Event $event)
{
    $validated = $request->validate([
        'date'            => 'required|date',
        'slot_no'         => 'required|integer|min:1',
        'guard_id'        => 'nullable|exists:security_guards,id',
        'shift1_start'    => 'nullable|date_format:H:i',
        'shift1_end'      => 'nullable|date_format:H:i',
        'break_hours'     => 'nullable|numeric|in:0,0.25,0.5,0.75,1',
        'shift1_postcode'     => 'nullable|string|max:255',
        'shift1_site_name'    => 'nullable|string|max:255',
        'shift1_site_address' => 'nullable|string|max:255',
    ]);

    $date = $validated['date'];
    $slotNo = (int) $validated['slot_no'];
    $guardId = $validated['guard_id'] ?? null;

    $startTime = $validated['shift1_start'] ?? null;
    $endTime = $validated['shift1_end'] ?? null;
    $breakHours = isset($validated['break_hours']) ? (float) $validated['break_hours'] : 0;

    $sitePostcode = array_key_exists('shift1_postcode', $validated)
        ? (trim((string) ($validated['shift1_postcode'] ?? '')) ?: null) : null;
    $siteName = array_key_exists('shift1_site_name', $validated)
        ? (trim((string) ($validated['shift1_site_name'] ?? '')) ?: null) : null;
    $siteAddress = array_key_exists('shift1_site_address', $validated)
        ? (trim((string) ($validated['shift1_site_address'] ?? '')) ?: null) : null;

    $oldSlot = $event->assignmentSlots()
        ->whereDate('date', $date)
        ->where('slot_no', $slotNo)
        ->first();

    $oldValues = $oldSlot ? [
        'date'        => optional($oldSlot->date)->toDateString(),
        'slot_no'     => (int) $oldSlot->slot_no,
        'guard_id'    => $oldSlot->guard_id ? (int) $oldSlot->guard_id : null,
        'start_time'  => $oldSlot->start_time ? substr((string) $oldSlot->start_time, 0, 5) : null,
        'end_time'    => $oldSlot->end_time ? substr((string) $oldSlot->end_time, 0, 5) : null,
        'break_hours' => $oldSlot->break_hours !== null ? (float) $oldSlot->break_hours : 0,
        'location'    => $oldSlot->location,
    ] : null;

    $alreadyAssignedGuardIds = $event->guards()
        ->pluck('security_guards.id')
        ->map(fn ($id) => (int) $id)
        ->all();

    if ($guardId) {
        $guard = SecurityGuard::query()
            ->whereKey($guardId)
            ->first(['id', 'fullname', 'share_code_expiry']);

        if ($guard) {
            $isExpired = !empty($guard->share_code_expiry)
                && Carbon::parse($guard->share_code_expiry)->startOfDay()->lt(now()->startOfDay());

            $wasAlreadyAssignedToThisEvent = in_array((int) $guardId, $alreadyAssignedGuardIds, true);

            if ($isExpired && !$wasAlreadyAssignedToThisEvent) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => [
                        'guard_id' => [
                            "Guard {$guard->fullname} cannot be newly assigned because the Share-Code Expiry date has passed."
                        ],
                    ],
                ], 422);
            }
        }
    }

    // Compose location; fall back to the existing slot's legacy location
    // when the 3 site fields are empty (grandfathering).
    $composed = $this->composeLocation($siteName, $sitePostcode, $siteAddress);
    $location = $composed ?? ($guardId ? optional($oldSlot)->location : null);

    $cancellationSnapshot = $this->snapshotCancellations($event);

    DB::transaction(function () use ($event, $date, $slotNo, $guardId, $startTime, $endTime, $breakHours, $location, $sitePostcode, $siteName, $siteAddress, $cancellationSnapshot) {
        $event->assignmentSlots()->updateOrCreate(
            [
                'event_id' => $event->id,
                'date'    => $date,
                'slot_no' => $slotNo,
            ],
            [
                'guard_id'      => $guardId,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'break_hours'   => $breakHours,
                'site_postcode' => $sitePostcode,
                'site_name'     => $siteName,
                'site_address'  => $siteAddress,
                'location'      => $location,
            ]
        );

        EventShift::query()
            ->where('event_id', $event->id)
            ->whereDate('date', $date)
            ->where('shift_no', $slotNo)
            ->delete();

        if ($guardId && $startTime && $endTime) {
            EventShift::create([
                'event_id'      => $event->id,
                'guard_id'      => (int) $guardId,
                'date'          => $date,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'break_hours'   => $breakHours,
                'shift_no'      => $slotNo,
                'site_postcode' => $sitePostcode,
                'site_name'     => $siteName,
                'site_address'  => $siteAddress,
                'location'      => $location,
            ]);
        }

        $currentGuardIds = $event->assignmentSlots()
            ->whereNotNull('guard_id')
            ->pluck('guard_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $event->guards()->sync($currentGuardIds);

        $this->restoreCancellations($event, $cancellationSnapshot);
    });

    $newSlot = $event->assignmentSlots()
        ->whereDate('date', $date)
        ->where('slot_no', $slotNo)
        ->first();

    Audit::log(
        'assignment_slot_saved',
        Event::class,
        (int) $event->id,
        $oldValues,
        [
            'date'        => optional($newSlot->date)->toDateString(),
            'slot_no'     => (int) $newSlot->slot_no,
            'guard_id'    => $newSlot->guard_id ? (int) $newSlot->guard_id : null,
            'start_time'  => $newSlot->start_time ? substr((string) $newSlot->start_time, 0, 5) : null,
            'end_time'    => $newSlot->end_time ? substr((string) $newSlot->end_time, 0, 5) : null,
            'break_hours' => $newSlot->break_hours !== null ? (float) $newSlot->break_hours : 0,
            'location'    => $newSlot->location,
        ],
        $request
    );

    return response()->json([
        'success' => true,
        'message' => 'Row saved successfully.',
    ]);
}

    
    public function additionalDaysPreview(Request $request, Event $event)
{
    $request->validate([
        'import_file' => 'required|file|mimes:xlsx,xls',
    ]);

    $importedRows = $this->parseImportedEventFile($request->file('import_file'));

    if ($importedRows->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No valid rows found in Excel. At least Date is required.',
        ], 422);
    }

    $existingDates = $this->existingEventDates($event);

    $dates = $importedRows
        ->groupBy('date')
        ->map(function ($rows, $date) use ($existingDates) {
            return [
                'date' => $date,
                'row_count' => $rows->count(),
                'is_duplicate' => in_array($date, $existingDates, true),
                'action' => in_array($date, $existingDates, true) ? 'skip' : 'import',
            ];
        })
        ->values();

    return response()->json([
        'success' => true,
        'row_count' => $importedRows->count(),
        'dates' => $dates,
    ]);
}

public function importAdditionalDays(Request $request, Event $event)
{
    $validated = $request->validate([
        'import_file' => 'required|file|mimes:xlsx,xls',
        'global_action' => 'nullable|in:skip,overwrite,merge',
        'date_actions' => 'nullable|array',
        'date_actions.*' => 'nullable|in:skip,overwrite,merge',
    ]);

    $importedRows = $this->parseImportedEventFile($request->file('import_file'));

    if ($importedRows->isEmpty()) {
        return back()
            ->withErrors([
                'import_file' => 'No valid rows found in Excel. At least Date is required.',
            ])
            ->withInput();
    }

    $oldDailyGuards = $event->daily_guards ?? [];

    $cancellationSnapshot = $this->snapshotCancellations($event);

    DB::transaction(function () use ($event, $importedRows, $validated, $cancellationSnapshot) {
        $dailyGuards = collect($event->daily_guards ?? [])
            ->mapWithKeys(fn ($count, $date) => [$date => (int) $count]);

        $existingDates = $this->existingEventDates($event);
        $globalAction = $validated['global_action'] ?? 'skip';
        $dateActions = $validated['date_actions'] ?? [];

        $rowsByDate = $importedRows->groupBy('date');

        foreach ($rowsByDate as $date => $rows) {
            $rows = $rows->values();
            $isDuplicate = in_array($date, $existingDates, true);

            if (!$isDuplicate) {
                $this->createImportedSlotsForDate($event, $date, $rows);
                $dailyGuards[$date] = $rows->count();
                continue;
            }

            $action = $dateActions[$date] ?? $globalAction ?? 'skip';

            if ($action === 'skip') {
                continue;
            }

            if ($action === 'overwrite') {
                $event->assignmentSlots()
                    ->whereDate('date', $date)
                    ->delete();

                EventShift::query()
                    ->where('event_id', $event->id)
                    ->whereDate('date', $date)
                    ->delete();

                $this->createImportedSlotsForDate($event, $date, $rows);

                $dailyGuards[$date] = $rows->count();

                continue;
            }

            if ($action === 'merge') {
                foreach ($rows as $index => $row) {
                    $slotNo = $index + 1;

                    $event->assignmentSlots()->updateOrCreate(
                        [
                            'event_id' => $event->id,
                            'date' => $date,
                            'slot_no' => $slotNo,
                        ],
                        [
                            'start_time' => $row['start_time'],
                            'end_time' => $row['end_time'],
                            'break_hours' => $row['break_hours'],
                            'location' => $row['location'] ?? null,
                        ]
                    );

                    $this->syncImportedSlotShift($event, $date, $slotNo);
                }

                $existingCount = (int) ($dailyGuards[$date] ?? 0);
                $dailyGuards[$date] = max($existingCount, $rows->count());
            }
        }

        $dailyGuards = $dailyGuards
            ->filter(fn ($count) => (int) $count > 0)
            ->sortKeys();

        $event->update([
            'daily_guards' => $dailyGuards->toArray(),
            'start_date' => $dailyGuards->keys()->min(),
            'end_date' => $dailyGuards->keys()->max(),
        ]);

        $currentGuardIds = $event->assignmentSlots()
            ->whereNotNull('guard_id')
            ->pluck('guard_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $event->guards()->sync($currentGuardIds);

        $this->restoreCancellations($event, $cancellationSnapshot);
    });

    Audit::log(
        'additional_days_imported',
        Event::class,
        (int) $event->id,
        [
            'daily_guards' => $oldDailyGuards,
        ],
        [
            'daily_guards' => $event->fresh()->daily_guards ?? [],
        ],
        $request
    );

    return back()->with('success', 'Additional days imported successfully.');
}

private function existingEventDates(Event $event): array
{
    $dailyGuardDates = collect($event->daily_guards ?? [])
        ->keys();

    $slotDates = $event->assignmentSlots()
        ->pluck('date')
        ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'));

    $shiftDates = $event->shifts()
        ->pluck('date')
        ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'));

    return $dailyGuardDates
        ->merge($slotDates)
        ->merge($shiftDates)
        ->unique()
        ->values()
        ->all();
}

private function createImportedSlotsForDate(Event $event, string $date, Collection $rows): void
{
    foreach ($rows->values() as $index => $row) {
        $event->assignmentSlots()->create([
            'date' => $date,
            'slot_no' => $index + 1,
            'guard_id' => null,
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'break_hours' => $row['break_hours'],
            'location' => $row['location'] ?? null,
        ]);
    }
}

private function syncImportedSlotShift(Event $event, string $date, int $slotNo): void
{
    $slot = $event->assignmentSlots()
        ->whereDate('date', $date)
        ->where('slot_no', $slotNo)
        ->first();

    EventShift::query()
        ->where('event_id', $event->id)
        ->whereDate('date', $date)
        ->where('shift_no', $slotNo)
        ->delete();

    if (!$slot || !$slot->guard_id || !$slot->start_time || !$slot->end_time) {
        return;
    }

    EventShift::create([
        'event_id' => $event->id,
        'guard_id' => (int) $slot->guard_id,
        'date' => $date,
        'start_time' => $slot->start_time,
        'end_time' => $slot->end_time,
        'break_hours' => $slot->break_hours ?? 0,
        'shift_no' => $slotNo,
        'location' => $slot->location,
    ]);
}
    
    

    public function edit(Event $event)
    {
        $event->load(['guards', 'schedules', 'supplier', 'client']);

        return view('events.edit', [
            'event'       => $event,
            'clients'     => Client::query()->orderBy('name')->get(),
            'guards'      => SecurityGuard::query()->orderBy('fullname')->get(),
            'suppliers'   => Supplier::query()->orderBy('name')->get(),
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
            'client_contact' => 'nullable|string|max:100',
            'instructions'   => 'nullable|string',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'shift_mode'     => 'required|in:same,different',
            'daily_guards'   => 'required|array',
            'daily_guards.*' => 'nullable|integer|min:0',
            'guards'         => 'nullable|array',
            'guards.*'       => 'exists:security_guards,id',
        ]);

        DB::transaction(function () use ($event, $validated) {
            $event->update([
                'event_name'     => $validated['event_name'],
                'address'        => $validated['address'] ?? null,
                'client_id'      => $validated['client_id'],
                'client_type'    => $validated['client_type'],
                'supplier_id'    => $validated['supplier_id'] ?? null,
                'payment_terms'  => $validated['payment_terms'] ?? null,
                'charge_rate'    => auth()->user()->can('view-charge-rate')
                    ? ($validated['charge_rate'] ?? null)
                    : $event->charge_rate,
                'invoice_date'   => $validated['invoice_date'] ?? null,
                'pay_rate'       => $validated['pay_rate'] ?? null,
                'client_contact' => $validated['client_contact'] ?? null,
                'instructions'   => $validated['instructions'] ?? null,
                'start_date'     => $validated['start_date'],
                'end_date'       => $validated['end_date'],
                'shift_mode'     => $validated['shift_mode'],
                'daily_guards'   => $validated['daily_guards'],
            ]);

            if (array_key_exists('guards', $validated)) {
                $event->guards()->sync($validated['guards'] ?? []);
            }
        });

        return $request->redirect_to === 'guards'
            ? redirect()->route('events.guards', $event)
            : redirect()->route('events.index')->with('success', 'Event updated successfully!');
    }

    public function show(Event $event)
    {
        $event->load([
            'client',
            'supplier',
            'guards',
            'shifts.securityGuard',
            'assignmentSlots.securityGuard',
        ]);

        $dailyGuards = collect($event->daily_guards ?? [])
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $slotsByDate = $event->assignmentSlots
            ->sortBy('slot_no')
            ->groupBy(fn ($slot) => optional($slot->date)->toDateString());

        // Cancelled shifts keyed date|guard, so rows can render red.
        $cancelledShiftKeys = $event->shifts
            ->filter(fn ($s) => $s->cancelled_at !== null && $s->guard_id)
            ->map(fn ($s) => optional($s->date)->toDateString() . '|' . (int) $s->guard_id)
            ->flip();

        $assignmentRowsByDate = [];
        $assignmentIssuesByDate = [];

        foreach ($dailyGuards as $date => $requiredCount) {
            $requiredCount = (int) $requiredCount;

            if ($requiredCount <= 0) {
                $assignmentRowsByDate[$date] = [];
                $assignmentIssuesByDate[$date] = [];
                continue;
            }

            $dateSlots = $slotsByDate->get($date, collect())->sortBy('slot_no');
            $rows = [];
            $issues = [];

            for ($i = 1; $i <= $requiredCount; $i++) {
                $slot = $dateSlots->firstWhere('slot_no', $i);

                $guardName = $slot?->securityGuard?->fullname;
                $licenseNumber = $slot?->securityGuard?->license_number;
                $breakHours = $slot && $slot->break_hours !== null ? number_format((float) $slot->break_hours, 2, '.', '') : '0.00';

                $rowIssues = [];

                if (!$slot || !$slot->guard_id) {
                    $rowIssues[] = 'Guard missing';
                }
                if (!$slot || !$slot->start_time) {
                    $rowIssues[] = 'Start time missing';
                }
                if (!$slot || !$slot->end_time) {
                    $rowIssues[] = 'End time missing';
                }
                if (!$slot || !$slot->location) {
                    $rowIssues[] = 'Location missing';
                }




              $rows[] = [
    'date'        => $date,
    'slot_no'     => $i,
    'guard_id'    => $slot?->guard_id,
    'guard'       => $guardName ?: '—',
    'license'     => $licenseNumber ?: '—',
    'start'       => $slot?->start_time ? substr((string) $slot->start_time, 0, 5) : '—',
    'end'         => $slot?->end_time ? substr((string) $slot->end_time, 0, 5) : '—',
    'break'       => $breakHours,
    'location'    => $slot?->location ?: '—',

    'start_value'    => $slot?->start_time ? substr((string) $slot->start_time, 0, 5) : '',
    'end_value'      => $slot?->end_time ? substr((string) $slot->end_time, 0, 5) : '',
    'break_value'    => $slot && $slot->break_hours !== null ? (string) (float) $slot->break_hours : '0',
    'location_value' => $slot?->location ?: '',

    'issues'      => $rowIssues,
    'is_red'      => !empty($rowIssues),
    'is_cancelled' => ($slot && $slot->guard_id)
        ? $cancelledShiftKeys->has($date . '|' . (int) $slot->guard_id)
        : false,
];




                if (!empty($rowIssues)) {
                    $issues[] = "Slot {$i}: " . implode(', ', $rowIssues);
                }
            }

            $assignmentRowsByDate[$date] = $rows;
            $assignmentIssuesByDate[$date] = $issues;
        }



      $guards = SecurityGuard::query()
    ->select('id', 'fullname', 'license_number', 'share_code_expiry')
    ->orderBy('fullname')
    ->get();

return view('events.show', compact(
    'event',
    'assignmentRowsByDate',
    'assignmentIssuesByDate',
    'dailyGuards',
    'guards'
));
        
        
        
    }

    public function exportStaffSheet(\App\Models\Event $event)
    {
        $date = $event->start_date ?? $event->invoice_date ?? $event->created_at;
        $filename = $this->staffSheetFilename($event->event_name, $date);

        return Excel::download(new EventStaffSheetExport((int) $event->id), $filename);
    }

    public function exportStaffPaymentSheet(\App\Models\Event $event)
    {
        $date = $event->start_date ?? $event->invoice_date ?? $event->created_at;
        $filename = $this->staffPaymentSheetFilename($event->event_name, $date);

        return Excel::download(
            new EventStaffPaymentSheetExport((int) $event->id),
            $filename
        );
    }
    
    public function exportTimeSheet(\App\Models\Event $event)
{
    $date = $event->start_date ?? $event->invoice_date ?? $event->created_at;
    $filename = $this->timeSheetFilename($event->event_name, $date);

    return Excel::download(
        new EventTimeSheetExport((int) $event->id),
        $filename
    );
}

    public function exportTimeSheetReduced(\App\Models\Event $event)
    {
        $date = $event->start_date ?? $event->invoice_date ?? $event->created_at;
        $filename = str_replace('.xlsx', '-no-details.xlsx', $this->timeSheetFilename($event->event_name, $date));

        return Excel::download(
            new EventTimeSheetExport((int) $event->id, true),
            $filename
        );
    }

    public function exportStaffPaymentSheetReduced(\App\Models\Event $event)
    {
        $date = $event->start_date ?? $event->invoice_date ?? $event->created_at;
        $filename = str_replace('.xlsx', '-no-details.xlsx', $this->staffPaymentSheetFilename($event->event_name, $date));

        return Excel::download(
            new EventStaffPaymentSheetExport((int) $event->id, true),
            $filename
        );
    }
    
    
    

    private function staffSheetFilename(string $eventName, $date): string
    {
        $d = Carbon::parse($date);
        $day = (int) $d->day;

        $suffix = 'th';
        if (!in_array($day % 100, [11, 12, 13], true)) {
            $suffix = match ($day % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        return "{$day}{$suffix} {$d->format('M')} {$eventName}.xlsx";
    }

    private function staffPaymentSheetFilename(string $eventName, $date): string
    {
        $d = Carbon::parse($date);
        $day = (int) $d->day;

        $suffix = 'th';
        if (!in_array($day % 100, [11, 12, 13], true)) {
            $suffix = match ($day % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        return "{$day}{$suffix} {$d->format('M')} {$eventName} Staff Payment Sheet.xlsx";
    }


private function timeSheetFilename(string $eventName, $date): string
{
    $d = Carbon::parse($date);
    $day = (int) $d->day;

    $suffix = 'th';
    if (!in_array($day % 100, [11, 12, 13], true)) {
        $suffix = match ($day % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }

    return "{$day}{$suffix} {$d->format('M')} {$eventName} Time Sheet.xlsx";
}




    public function destroy(Event $event)
    {
        DB::transaction(function () use ($event) {
            $event->guards()->detach();
            $event->schedules()->delete();
            $event->shifts()->delete();
            $event->delete();
        });

        return redirect()
            ->route('events.index')
            ->with('success', 'Event deleted successfully.');
    }
}