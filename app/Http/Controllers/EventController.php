<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Invoice;
use App\Models\GuardInvoice;
use App\Models\Chaseup;
use App\Models\EventAssignmentSlot;
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
                    })
                    // V5: search by shift location / postcode / site name
                    ->orWhereHas('shifts', function ($sh) use ($q) {
                        $sh->where('location', 'like', '%' . $q . '%')
                           ->orWhere('site_postcode', 'like', '%' . $q . '%')
                           ->orWhere('site_name', 'like', '%' . $q . '%');
                    })
                    ->orWhereHas('assignmentSlots', function ($sl) use ($q) {
                        $sl->where('location', 'like', '%' . $q . '%')
                           ->orWhere('site_postcode', 'like', '%' . $q . '%')
                           ->orWhere('site_name', 'like', '%' . $q . '%');
                    });
            });
        }

        $events = $eventsQuery
            ->paginate($perPage)
            ->withQueryString();

        $events->getCollection()->transform(function ($event) {
            // V5: cancelled shifts count as 0 hours (net of break otherwise).
            $totalHours = (float) $event->shifts->whereNull('cancelled_at')->sum(function ($shift) {
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

        $tempGuardEventIds = Event::idsWithTempGuards()->flip();

        return view('events.index', compact('events', 'perPage', 'tempGuardEventIds'));
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
            'charge_rate_sia'     => 'nullable|numeric|min:0',
            'charge_rate_steward' => 'nullable|numeric|min:0',
            'invoice_date'    => 'required|date',
            'staff_pay_date'  => 'required|date',
            'pay_rate'        => 'required|numeric|min:0',
            'pay_rate_sia'     => 'nullable|numeric|min:0',
            'pay_rate_steward' => 'nullable|numeric|min:0',
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
                'charge_rate_sia'     => $validated['charge_rate_sia'] ?? null,
                'charge_rate_steward' => $validated['charge_rate_steward'] ?? null,
                'invoice_date'    => $validated['invoice_date'] ?? null,
                'staff_pay_date'  => $validated['staff_pay_date'] ?? null,
                'pay_rate'        => $validated['pay_rate'] ?? null,
                'pay_rate_sia'     => $validated['pay_rate_sia'] ?? null,
                'pay_rate_steward' => $validated['pay_rate_steward'] ?? null,
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
        $badBreaks = [];

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

            if ($normalizedBreak === null) {
                $badBreaks[] = 'Row ' . ($rowIndex + 2) . ': "' . trim((string) $breakValue) . '"';
                continue;
            }

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

        if (!empty($badBreaks)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'import_file' => 'Unrecognised break value in ' . implode('; ', array_slice($badBreaks, 0, 5))
                    . (count($badBreaks) > 5 ? '; and ' . (count($badBreaks) - 5) . ' more' : '')
                    . '. Use 0, 15, 30, 45 or 60 minutes (or 0 / 0.25 / 0.5 / 0.75 / 1 hours). Nothing was imported.',
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

    /**
     * Returns the break in hours (0, 0.25, 0.5, 0.75 or 1), or null when a
     * non-empty value cannot be understood. V5: unrecognised values are no
     * longer silently turned into 0; the import rejects the row instead.
     */
    private function normalizeImportedBreak($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0.0;
        }

        $raw = strtolower(trim((string) $value));

        // "0:30", "1:00" style
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $raw, $m)) {
            $hours = ((int) $m[1]) + ((int) $m[2]) / 60;
            return in_array($hours, [0.0, 0.25, 0.5, 0.75, 1.0], true) ? $hours : null;
        }

        // "30", "30m", "30 min", "30 minutes", "1hr", "1 hour", "0.5h", "1.0"
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(m|min|mins|minute|minutes|h|hr|hrs|hour|hours)?$/', $raw, $m)) {
            $num  = (float) $m[1];
            $unit = $m[2] ?? '';

            if (in_array($unit, ['m', 'min', 'mins', 'minute', 'minutes'], true)) {
                $hours = $num / 60;
            } elseif ($unit !== '') {
                $hours = $num;
            } else {
                // bare number: 0.25 / 0.5 / 0.75 / 1 are hours; 15 / 30 / 45 / 60 are minutes
                $hours = $num > 1 ? $num / 60 : $num;
            }

            $hours = round($hours, 2);

            return in_array($hours, [0.0, 0.25, 0.5, 0.75, 1.0], true) ? $hours : null;
        }

        return null;
    }

    /**
     * V5-P3: blank / non-numeric / out-of-range -> null; otherwise a
     * rounded non-negative amount.
     */
    private function cleanMoney($value, float $max): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $v = round((float) $value, 2);

        return ($v < 0 || $v > $max) ? null : $v;
    }

    /**
     * Pay / charge override for a shift. Returns null when blank OR when it
     * equals what the event would use anyway (category rate, else base), so
     * the boxes can be pre-filled without freezing the value: a later change
     * to the event's rate still flows through to every shift that was not
     * deliberately overridden.
     */
    private function overrideOrNull($submitted, Event $event, ?string $category, string $kind): ?float
    {
        $v = $this->cleanMoney($submitted, 9999.99);

        if ($v === null) {
            return null;
        }

        return abs($v - $event->rateFor($category, $kind)) < 0.005 ? null : $v;
    }

    /**
     * Resolve the three per-shift money fields for a row being saved.
     *
     * Who may change what:
     *   pay rate    every role that can assign guards
     *   charge rate view-charge-rate (Super Admin, Accountant)
     *   expense     edit-guard-payments (Super Admin, Accountant)
     * A field the current user may NOT change (or that the request did not
     * send) keeps the value already on the slot, so a Moderator saving the
     * grid can never wipe or alter a charge rate or expense.
     * If the slot's guard changed, nothing is inherited from the old guard.
     *
     * @param array                   $input   submitted row (keys shift_pay_rate / shift_charge_rate / shift_expense)
     * @param EventAssignmentSlot|null $old    the slot being replaced (same date + slot_no)
     */
    private function resolveShiftMoney(Event $event, array $input, $old, $guardId, ?string $category): array
    {
        $sameGuard = $old && $guardId && (int) $old->guard_id === (int) $guardId;
        $keep = fn ($field) => ($sameGuard && $old->{$field} !== null && $old->{$field} !== '')
            ? (float) $old->{$field}
            : null;

        $user = auth()->user();

        $pay = array_key_exists('shift_pay_rate', $input)
            ? $this->overrideOrNull($input['shift_pay_rate'], $event, $category, 'pay')
            : $keep('pay_rate');

        $charge = ($user && $user->can('view-charge-rate') && array_key_exists('shift_charge_rate', $input))
            ? $this->overrideOrNull($input['shift_charge_rate'], $event, $category, 'charge')
            : $keep('charge_rate');

        $expense = ($user && $user->can('edit-guard-payments') && array_key_exists('shift_expense', $input))
            ? $this->cleanMoney($input['shift_expense'], 99999999.99)
            : $keep('expense');

        // A swapped guard starts clean, whatever the form still holds.
        if ($old && $old->guard_id && $guardId && (int) $old->guard_id !== (int) $guardId) {
            return ['pay_rate' => null, 'charge_rate' => null, 'expense' => null];
        }

        return ['pay_rate' => $pay, 'charge_rate' => $charge, 'expense' => $expense];
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
        // V5: keyed per EXACT shift (date|guard|start|end), so cancelling one of a
        // guard's two same-day shifts leaves the other one alone.
        return EventShift::query()
            ->where('event_id', $event->id)
            ->whereNotNull('cancelled_at')
            ->whereNotNull('guard_id')
            ->get(['date', 'guard_id', 'start_time', 'end_time', 'cancelled_at'])
            ->mapWithKeys(fn ($s) => [
                $this->shiftKey($s->date, $s->guard_id, $s->start_time, $s->end_time) => $s->cancelled_at,
            ])
            ->all();
    }

    /**
     * Stable business identity of one shift: date|guard|HH:MM|HH:MM.
     * Never the volatile shift id, never shift_no.
     */
    private function shiftKey($date, $guardId, $start, $end): string
    {
        $d = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : substr((string) $date, 0, 10);

        return $d . '|' . (int) $guardId . '|' . substr((string) $start, 0, 5) . '|' . substr((string) $end, 0, 5);
    }

    /**
     * Re-apply a cancellation snapshot to freshly-recreated shifts,
     * preserving the original cancelled_at timestamp.
     */
    private function restoreCancellations(Event $event, array $snapshot): void
    {
        foreach ($snapshot as $key => $cancelledAt) {
            $parts = explode('|', $key);

            if (count($parts) !== 4) {
                continue;
            }

            [$date, $guardId, $start, $end] = $parts;

            EventShift::query()
                ->where('event_id', $event->id)
                ->whereDate('date', $date)
                ->where('guard_id', (int) $guardId)
                ->whereRaw("TIME_FORMAT(start_time, '%H:%i') = ?", [$start])
                ->whereRaw("TIME_FORMAT(end_time, '%H:%i') = ?", [$end])
                ->update(['cancelled_at' => $cancelledAt]);
        }
    }

    /**
     * Toggle the soft-cancel state of ONE shift (V5: single shift, not every
     * shift the guard has that day). Reversible; keyed by the stable business
     * identity (event, date, guard, start, end), never the volatile shift id.
     *
     * Older pages that do not send start/end fall back to the previous
     * behaviour (every shift of that guard on that date) so nothing breaks
     * for a page that was open during deployment.
     */
    public function toggleCancelShift(Request $request, Event $event)
    {
        $data = $request->validate([
            'date'     => 'required|date',
            'guard_id' => 'required|integer|exists:security_guards,id',
            'start'    => 'nullable|date_format:H:i',
            'end'      => 'nullable|date_format:H:i',
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $guardId = (int) $data['guard_id'];

        $scope = function () use ($event, $date, $guardId, $data) {
            $q = EventShift::query()
                ->where('event_id', $event->id)
                ->whereDate('date', $date)
                ->where('guard_id', $guardId);

            if (!empty($data['start']) && !empty($data['end'])) {
                $q->whereRaw("TIME_FORMAT(start_time, '%H:%i') = ?", [$data['start']])
                  ->whereRaw("TIME_FORMAT(end_time, '%H:%i') = ?", [$data['end']]);
            }

            return $q;
        };

        $shifts = $scope()->get();

        if ($shifts->isEmpty()) {
            return back()->withErrors(['cancel' => 'No shift found for that guard on that date.']);
        }

        $currentlyCancelled = $shifts->every(fn ($s) => $s->cancelled_at !== null);

        $scope()->update(['cancelled_at' => $currentlyCancelled ? null : now()]);

        return back()->with('success', $currentlyCancelled ? 'Shift un-cancelled.' : 'Shift cancelled.');
    }

    /**
     * PERMANENTLY delete a guard's shift on a date. For mistaken entries.
     *
     * Unlike cancel, this leaves nothing visible: no red row, no zeroed
     * hours. It removes the shift, its chaseup note and the assignment
     * slot, then decrements the day's required guard count and renumbers
     * the remaining slots so the grid stays contiguous.
     *
     * Deleting the event_shifts row alone would NOT hold: the assignment
     * grid is rendered from event_assignment_slots + daily_guards, and
     * updateGuards() rebuilds shifts from that grid — so the shift would
     * reappear on the next "Save Assignments". The slot must go too.
     *
     * Deliberately ALLOWED even when the shift sits on an issued or paid
     * invoice (confirmed decision) — the UI warns hard first. Every
     * deleted shift is written to audit_logs so a mistaken delete can be
     * reconstructed, even though nothing is visible in the UI.
     */
    public function deleteShift(Request $request, Event $event)
    {
        $data = $request->validate([
            'date'     => 'required|date',
            'guard_id' => 'required|integer|exists:security_guards,id',
            'confirm'  => 'required|in:DELETE',
        ]);

        $date    = Carbon::parse($data['date'])->toDateString();
        $guardId = (int) $data['guard_id'];

        $shifts = EventShift::query()
            ->where('event_id', $event->id)
            ->whereDate('date', $date)
            ->where('guard_id', $guardId)
            ->get();

        $slots = EventAssignmentSlot::query()
            ->where('event_id', $event->id)
            ->whereDate('date', $date)
            ->where('guard_id', $guardId)
            ->get();

        if ($shifts->isEmpty() && $slots->isEmpty()) {
            return back()->withErrors(['delete' => 'No shift found for that guard on that date.']);
        }

        DB::transaction(function () use ($event, $date, $guardId, $shifts, $slots) {
            // Audit every row BEFORE it disappears.
            foreach ($shifts as $shift) {
                Audit::log('deleted', EventShift::class, (int) $shift->id, $shift->toArray(), null);
            }

            EventShift::query()
                ->where('event_id', $event->id)
                ->whereDate('date', $date)
                ->where('guard_id', $guardId)
                ->delete();

            // Chaseup note for that guard/date — removed outright.
            $chaseups = Chaseup::withTrashed()
                ->where('event_id', $event->id)
                ->whereDate('date', $date)
                ->where('guard_id', $guardId)
                ->get();

            foreach ($chaseups as $chaseup) {
                Audit::log('deleted', Chaseup::class, (int) $chaseup->id, $chaseup->toArray(), null);
                $chaseup->forceDelete();
            }

            foreach ($slots as $slot) {
                Audit::log('deleted', EventAssignmentSlot::class, (int) $slot->id, $slot->toArray(), null);
            }

            EventAssignmentSlot::query()
                ->where('event_id', $event->id)
                ->whereDate('date', $date)
                ->where('guard_id', $guardId)
                ->delete();

            /*
             * Renumber the surviving slots for that date to 1..N. The grid
             * loops 1..requiredCount and matches on slot_no, so a gap would
             * render an empty row and orphan the higher-numbered slots.
             * Numbers only ever decrease here, so no unique-key collision.
             */
            $remaining = EventAssignmentSlot::query()
                ->where('event_id', $event->id)
                ->whereDate('date', $date)
                ->orderBy('slot_no')
                ->get();

            $slotNo = 1;
            foreach ($remaining as $slot) {
                if ((int) $slot->slot_no !== $slotNo) {
                    $slot->slot_no = $slotNo;
                    $slot->save();
                }
                $slotNo++;
            }

            // Decrement the day's required guard count to match.
            $daily = (array) ($event->daily_guards ?? []);
            if (array_key_exists($date, $daily)) {
                $daily[$date] = max(0, (int) $daily[$date] - $slots->count());
                $event->daily_guards = $daily;
                $event->save();
            }
        });

        return back()->with('success', 'Shift deleted permanently.');
    }

    public function guards(Event $event)
    {
        $event->load(['assignmentSlots.securityGuard']);

        // V5-P3: charge rate and expense only ever reach roles allowed to see them.
        $canSeeCharge  = (bool) auth()->user()?->can('view-charge-rate');
        $canSeeExpense = (bool) auth()->user()?->can('edit-guard-payments');

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
                    'shift_pay_rate'      => $slot->pay_rate,
                    'shift_charge_rate'   => $canSeeCharge ? $slot->charge_rate : null,
                    'shift_expense'       => $canSeeExpense ? $slot->expense : null,
                    'guard_id'            => $slot->guard_id,
                    'temp_phone'          => $slot->temp_phone,
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
                    'temp_phone'          => null,
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
            // V5: City + mobile in the picker, only for roles allowed to see
            // guard personal details (Moderator never receives them).
            'guards'      => SecurityGuard::query()
                ->select(array_merge(
                    ['id', 'fullname', 'license_number', 'share_code_expiry', 'category'],
                    \Illuminate\Support\Facades\Gate::allows('view-guard-details') ? ['phone_number', 'city'] : []
                ))
                ->orderBy('fullname')
                ->get()
                ->toArray(),
            'dailyGuards' => $dailyGuards,
            'assignments' => $assignments,
            // V5-P3: defaults the per-shift boxes are pre-filled with. Charge rates
            // are only sent to roles allowed to see them.
            'eventRates'  => array_filter([
                'pay'            => $event->pay_rate,
                'pay_sia'        => $event->pay_rate_sia,
                'pay_steward'    => $event->pay_rate_steward,
                'charge'         => $canSeeCharge ? $event->charge_rate : null,
                'charge_sia'     => $canSeeCharge ? $event->charge_rate_sia : null,
                'charge_steward' => $canSeeCharge ? $event->charge_rate_steward : null,
            ], fn ($v) => $v !== null),
            'canSeeCharge'  => $canSeeCharge,
            'canSeeExpense' => $canSeeExpense,
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

                // V4-P5: temp phone survives the bulk save; a real guard wins.
                $assignments[$date][$i]['temp_phone'] = $assignments[$date][$i]['guard_id']
                    ? null
                    : $this->cleanTempPhone($row['temp_phone'] ?? null);

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
            'assignments.*.*.temp_phone'      => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{7,32}$/'],
            'assignments.*.*.shift1_start'    => 'nullable|date_format:H:i',
           'assignments.*.*.shift1_end'      => 'nullable|date_format:H:i',
            'assignments.*.*.break_hours'     => 'nullable|numeric|in:0,0.25,0.5,0.75,1',
            'assignments.*.*.shift_pay_rate'    => 'nullable|numeric|min:0|max:9999.99',
            'assignments.*.*.shift_charge_rate' => 'nullable|numeric|min:0|max:9999.99',
            'assignments.*.*.shift_expense'     => 'nullable|numeric|min:0|max:99999999.99',
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
            ->get(['id', 'fullname', 'share_code_expiry', 'category'])
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

        $oldTempPhones = $event->assignmentSlots()->whereNotNull('temp_phone')->pluck('temp_phone')->all();

        $cancellationSnapshot = $this->snapshotCancellations($event);

        // V5-P3: per-shift pay / charge / expense. Resolved BEFORE the slots are
        // wiped, against the slot each row replaces (same date + slot_no), so
        // fields the user may not edit are carried over untouched.
        $oldSlotsByPosition = $event->assignmentSlots()->get()
            ->keyBy(fn ($sl) => optional($sl->date)->toDateString() . '|' . (int) $sl->slot_no);

        $moneyByPosition = [];
        foreach ($validated['assignments'] as $mDate => $mRows) {
            foreach (array_values($mRows) as $mIndex => $mRow) {
                $mGuardId = $mRow['guard_id'] ?? null;
                $moneyByPosition[$mDate . '|' . ($mIndex + 1)] = $this->resolveShiftMoney(
                    $event,
                    $mRow,
                    $oldSlotsByPosition->get($mDate . '|' . ($mIndex + 1)),
                    $mGuardId,
                    $mGuardId ? $guardsById->get((int) $mGuardId)?->category : null
                );
            }
        }

        DB::transaction(function () use ($validated, $event, $requestedGuardIds, $oldLocationByDateGuard, $cancellationSnapshot, $moneyByPosition) {
            $event->assignmentSlots()->delete();
            $event->guards()->detach();
            $event->shifts()->delete();

            foreach ($validated['assignments'] as $date => $rows) {
                foreach (array_values($rows) as $index => $row) {
                    $composed = $this->composeLocation($row['shift1_site_name'] ?? null, $row['shift1_postcode'] ?? null, $row['shift1_site_address'] ?? null);
                    $finalLocation = $composed ?? ((!empty($row['guard_id']) || !empty($row['temp_phone'])) ? ($oldLocationByDateGuard[$date . '|' . (int) $row['guard_id']] ?? null) : null);

                    $event->assignmentSlots()->create([
                        'date'          => $date,
                        'slot_no'       => $index + 1,
                        'guard_id'      => $row['guard_id'] ?? null,
                        'temp_phone'    => $row['temp_phone'] ?? null,
                        'start_time'    => $row['shift1_start'] ?? null,
                        'end_time'      => $row['shift1_end'] ?? null,
                        'break_hours'   => $row['break_hours'] ?? 0,
                        'pay_rate'      => $moneyByPosition[$date . '|' . ($index + 1)]['pay_rate'] ?? null,
                        'charge_rate'   => $moneyByPosition[$date . '|' . ($index + 1)]['charge_rate'] ?? null,
                        'expense'       => $moneyByPosition[$date . '|' . ($index + 1)]['expense'] ?? null,
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
                foreach (array_values($rows) as $rowIndex => $row) {
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
                            'pay_rate'      => $moneyByPosition[$date . '|' . ($rowIndex + 1)]['pay_rate'] ?? null,
                            'charge_rate'   => $moneyByPosition[$date . '|' . ($rowIndex + 1)]['charge_rate'] ?? null,
                            'expense'       => $moneyByPosition[$date . '|' . ($rowIndex + 1)]['expense'] ?? null,
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

        try {
            $newTemps = $event->assignmentSlots->pluck('temp_phone')->filter()->all();
            foreach ($oldTempPhones as $gone) {
                if (!in_array($gone, $newTemps, true)) {
                    $this->forgetTempNotice($event->id, $gone);
                }
            }
            foreach ($event->assignmentSlots as $s) {
                if ($s->temp_phone && !in_array($s->temp_phone, $oldTempPhones, true)) {
                    $this->notifySlotChange($event, null, $s, $request);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

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
        'temp_phone'      => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{7,32}$/'],
        'shift1_start'    => 'nullable|date_format:H:i',
        'shift1_end'      => 'nullable|date_format:H:i',
        'break_hours'     => 'nullable|numeric|in:0,0.25,0.5,0.75,1',
        'shift_pay_rate'    => 'nullable|numeric|min:0|max:9999.99',
        'shift_charge_rate' => 'nullable|numeric|min:0|max:9999.99',
        'shift_expense'     => 'nullable|numeric|min:0|max:99999999.99',
        'shift1_postcode'     => 'nullable|string|max:255',
        'shift1_site_name'    => 'nullable|string|max:255',
        'shift1_site_address' => 'nullable|string|max:255',
    ]);

    $date = $validated['date'];
    $slotNo = (int) $validated['slot_no'];
    $guardId = $validated['guard_id'] ?? null;

    // V4-P5: a real guard always wins; a temp phone only lives on a guard-less slot.
    $tempPhone = $guardId ? null : $this->cleanTempPhone($validated['temp_phone'] ?? null);

    $startTime = $validated['shift1_start'] ?? null;
    $endTime = $validated['shift1_end'] ?? null;
    $breakHours = isset($validated['break_hours']) ? (float) $validated['break_hours'] : 0;

    $oldSlot = $event->assignmentSlots()
        ->whereDate('date', $date)
        ->where('slot_no', $slotNo)
        ->first();

    // V4-P7: when a caller does not send a site field at all, keep what the slot already has
    // (e.g. removing/replacing a temp guard must not wipe the shift's site details).
    $sitePostcode = array_key_exists('shift1_postcode', $validated)
        ? (trim((string) ($validated['shift1_postcode'] ?? '')) ?: null) : optional($oldSlot)->site_postcode;
    $siteName = array_key_exists('shift1_site_name', $validated)
        ? (trim((string) ($validated['shift1_site_name'] ?? '')) ?: null) : optional($oldSlot)->site_name;
    $siteAddress = array_key_exists('shift1_site_address', $validated)
        ? (trim((string) ($validated['shift1_site_address'] ?? '')) ?: null) : optional($oldSlot)->site_address;

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
            ->first(['id', 'fullname', 'share_code_expiry', 'category']);

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
    $hadTemp  = $oldSlot && $oldSlot->temp_phone;
    $location = $composed ?? (($guardId || $tempPhone || $hadTemp) ? optional($oldSlot)->location : null);

    $cancellationSnapshot = $this->snapshotCancellations($event);

    // V5-P3: per-shift pay / charge / expense (see resolveShiftMoney for who may change what).
    $money = $this->resolveShiftMoney(
        $event,
        $validated,
        $oldSlot,
        $guardId,
        $guardId ? SecurityGuard::query()->whereKey($guardId)->value('category') : null
    );

    DB::transaction(function () use ($event, $date, $slotNo, $guardId, $tempPhone, $startTime, $endTime, $breakHours, $location, $sitePostcode, $siteName, $siteAddress, $cancellationSnapshot, $money, $oldSlot) {
        $event->assignmentSlots()->updateOrCreate(
            [
                'event_id' => $event->id,
                'date'    => $date,
                'slot_no' => $slotNo,
            ],
            [
                'guard_id'      => $guardId,
                'temp_phone'    => $tempPhone,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'break_hours'   => $breakHours,
                'pay_rate'      => $money['pay_rate'],
                'charge_rate'   => $money['charge_rate'],
                'expense'       => $money['expense'],
                'site_postcode' => $sitePostcode,
                'site_name'     => $siteName,
                'site_address'  => $siteAddress,
                'location'      => $location,
            ]
        );

        /*
         * V5: remove ONLY this slot's own previous shift.
         *
         * This used to delete every shift on the date with shift_no = slot_no.
         * But "Save Assignments" (updateGuards) writes shift_no = 1 for every
         * row, so after one of those, saving slot 1 here removed ALL guards'
         * shifts for that date and re-created only this one. A shift's real
         * identity is (event, date, guard, start, end), so that is what is
         * matched: the shift the slot had before this save, plus any shift
         * that already sits on the exact key about to be written (which would
         * otherwise hit the unique index).
         */
        $removeShift = function (int $guard, $start, $end) use ($event, $date) {
            if (!$guard || !$start || !$end) {
                return;
            }

            EventShift::query()
                ->where('event_id', $event->id)
                ->whereDate('date', $date)
                ->where('guard_id', $guard)
                ->whereRaw("TIME_FORMAT(start_time, '%H:%i') = ?", [substr((string) $start, 0, 5)])
                ->whereRaw("TIME_FORMAT(end_time, '%H:%i') = ?", [substr((string) $end, 0, 5)])
                ->delete();
        };

        if ($oldSlot) {
            $removeShift((int) $oldSlot->guard_id, $oldSlot->start_time, $oldSlot->end_time);
        }
        $removeShift((int) $guardId, $startTime, $endTime);

        if ($guardId && $startTime && $endTime) {
            EventShift::create([
                'event_id'      => $event->id,
                'guard_id'      => (int) $guardId,
                'date'          => $date,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'break_hours'   => $breakHours,
                'pay_rate'      => $money['pay_rate'],
                'charge_rate'   => $money['charge_rate'],
                'expense'       => $money['expense'],
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

    $this->notifySlotChange($event, $oldSlot, $newSlot, $request);

    Audit::log(
        'assignment_slot_saved',
        Event::class,
        (int) $event->id,
        $oldValues,
        [
            'date'        => optional($newSlot->date)->toDateString(),
            'slot_no'     => (int) $newSlot->slot_no,
            'guard_id'    => $newSlot->guard_id ? (int) $newSlot->guard_id : null,
            'temp_phone'  => $newSlot->temp_phone,
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

    
    /** V4-P7: drop the dedupe record of a removed temp phone so a re-add alerts again. */
    private function forgetTempNotice(int $eventId, string $phone): void
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return;
        }
        \App\Models\AppNotification::where('dedupe_key', 'like', 'temp|' . $eventId . '|%|' . $digits)
            ->update(['dedupe_key' => null]);
    }

    /** V4-P6: bell (+ email for temp guards) after a slot save. Never throws. */
    private function notifySlotChange(Event $event, $oldSlot, $newSlot, Request $request): void
    {
        try {
            $url   = route('events.show', $event, false);
            $dateL = optional($newSlot->date)->format('d M Y');
            $fromChaseup = $request->input('source') === 'chaseup';

            // V4-P7: a temp phone that is gone from this slot may be entered again later and must alert again.
            if ($oldSlot && $oldSlot->temp_phone && $oldSlot->temp_phone !== $newSlot->temp_phone) {
                $this->forgetTempNotice($event->id, $oldSlot->temp_phone);
            }

            if ($newSlot->temp_phone && (!$oldSlot || $oldSlot->temp_phone !== $newSlot->temp_phone)) {
                \App\Services\NotificationService::notify(
                    'temp_guard',
                    'Temporary guard on ' . $event->event_name,
                    'Phone ' . $newSlot->temp_phone . ' assigned for ' . $dateL . ' (slot ' . $newSlot->slot_no . '). Invoices and guard payments are blocked for this event until a real guard replaces them.',
                    $url,
                    ['finance', 'ops'],
                    'temp|' . $event->id . '|' . optional($newSlot->date)->toDateString() . '|' . $newSlot->slot_no . '|' . preg_replace('/\D+/', '', $newSlot->temp_phone),
                    true
                );
            }

            if ($fromChaseup && $newSlot->guard_id && (!$oldSlot || (int) $oldSlot->guard_id !== (int) $newSlot->guard_id)) {
                $guard = SecurityGuard::find($newSlot->guard_id, ['id', 'fullname']);
                \App\Services\NotificationService::notify(
                    'chaseup',
                    'Chaseup: guard assigned on ' . $event->event_name,
                    ($guard->fullname ?? 'Guard') . ' assigned for ' . $dateL . ' (slot ' . $newSlot->slot_no . ').',
                    route('chaseup.index', ['date' => optional($newSlot->date)->toDateString()], false),
                    ['ops']
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** V4-P5: trim a temp-guard phone; blank -> null. */
    private function cleanTempPhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        return $phone !== '' ? preg_replace('/\s+/', ' ', $phone) : null;
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
        'pay_rate' => $slot->pay_rate,
        'charge_rate' => $slot->charge_rate,
        'expense' => $slot->expense,
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
            'charge_rate_sia'     => 'nullable|numeric|min:0',
            'charge_rate_steward' => 'nullable|numeric|min:0',
            'invoice_date'   => 'nullable|date',
            'staff_pay_date' => 'nullable|date',
            'pay_rate'       => 'nullable|numeric',
            'pay_rate_sia'     => 'nullable|numeric|min:0',
            'pay_rate_steward' => 'nullable|numeric|min:0',
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
                // V3-P3: category charge rates are gated exactly like the
                // base charge rate, so a user who cannot see them cannot
                // blank them by saving the form.
                'charge_rate_sia' => auth()->user()->can('view-charge-rate')
                    ? ($validated['charge_rate_sia'] ?? null)
                    : $event->charge_rate_sia,
                'charge_rate_steward' => auth()->user()->can('view-charge-rate')
                    ? ($validated['charge_rate_steward'] ?? null)
                    : $event->charge_rate_steward,
                'invoice_date'   => $validated['invoice_date'] ?? null,
                'staff_pay_date' => $validated['staff_pay_date'] ?? null,
                'pay_rate'       => $validated['pay_rate'] ?? null,
                'pay_rate_sia'     => $validated['pay_rate_sia'] ?? null,
                'pay_rate_steward' => $validated['pay_rate_steward'] ?? null,
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

            // Item 7 root-cause fix: when days are dropped from an event
            // (a day set to 0, or removed by shrinking the date range),
            // delete the now-orphaned rows for those dates so they stop
            // counting in reports, invoices, and the executive page.
            // Guarded days (count > 0) are left completely untouched,
            // including their cancelled shifts. If the submission keeps no
            // days at all, we skip pruning so a malformed/all-zero post can
            // never wipe an event's shifts.
            $keptDates = collect($validated['daily_guards'])
                ->filter(fn ($count) => (int) $count > 0)
                ->keys()
                ->map(fn ($d) => (string) $d)
                ->values()
                ->all();

            if (!empty($keptDates)) {
                $event->shifts()->whereNotIn('date', $keptDates)->delete();
                $event->assignmentSlots()->whereNotIn('date', $keptDates)->delete();
                $event->schedules()->whereNotIn('date', $keptDates)->delete();
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
        /*
         * Delete warning: does this event carry an issued/paid invoice?
         * Deleting a shift covered by one leaves the invoice billing work
         * that no longer exists, with no cancelled row to explain it.
         */
        $clientInvoiceLocked = Invoice::query()
            ->where('event_id', $event->id)
            ->whereIn('status', ['issued', 'paid', 'staff_paid'])
            ->exists();

        $lockedGuardInvoiceIds = GuardInvoice::query()
            ->where('event_id', $event->id)
            ->whereIn('status', ['issued', 'paid'])
            ->pluck('guard_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        // V5: per exact shift (date|guard|start|end) so one of a guard's two
        // same-day shifts can be cancelled without the other turning red.
        $cancelledShiftKeys = $event->shifts
            ->filter(fn ($s) => $s->cancelled_at !== null && $s->guard_id)
            ->map(fn ($s) => $this->shiftKey($s->date, $s->guard_id, $s->start_time, $s->end_time))
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

                $isTemp = $slot && !$slot->guard_id && $slot->temp_phone;

                if ($isTemp) {
                    $rowIssues[] = 'Temporary guard';
                } elseif (!$slot || !$slot->guard_id) {
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
    'guard'       => $guardName ?: ($isTemp ? 'TEMP ' . $slot->temp_phone : '—'),
    'temp_phone'  => $isTemp ? $slot->temp_phone : null,
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
    'is_cancelled' => ($slot && $slot->guard_id && $slot->start_time && $slot->end_time)
        ? $cancelledShiftKeys->has($this->shiftKey($date, $slot->guard_id, $slot->start_time, $slot->end_time))
        : false,
    'is_invoiced' => ($slot && $slot->guard_id)
        ? ($clientInvoiceLocked || $lockedGuardInvoiceIds->has((int) $slot->guard_id))
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