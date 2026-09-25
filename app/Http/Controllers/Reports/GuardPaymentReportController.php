<?php

namespace App\Http\Controllers\Reports;

use App\Exports\GuardPaymentReportExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventGuardPayment;
use App\Models\EventShift;
use App\Models\GuardInvoice;
use App\Models\SecurityGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Guard Payment Report — on-screen, per guard, across every client/event.
 *
 * Deliberately a VIEW over event_guard_payments (V3-P4), not a second
 * store: the event pay sheet and this report edit the same rows, so a
 * payment recorded on either shows on both. Grouping is by client or by
 * event; groups are paginated (never raw shift rows, which would split a
 * group across pages). Totals are always computed across the WHOLE
 * filtered range, not the visible page. Exports cover the full set.
 *
 * View: can:view-reports (so NOT Moderator — they use the event pay sheet).
 * Edit: can:edit-guard-payments (Super Admin + Accountant).
 * Edit history: Super Admin only.
 */
class GuardPaymentReportController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request)
    {
        $data = $request->validate([
            'guard_id'     => 'nullable|exists:security_guards,id',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date|after_or_equal:date_from',
            'client_ids'   => 'nullable|array',
            'client_ids.*' => 'integer|exists:clients,id',
            'event_ids'    => 'nullable|array',
            'event_ids.*'  => 'integer|exists:events,id',
            'group'        => 'nullable|in:client,event',
            'page'         => 'nullable|integer|min:1',
        ]);

        $group  = $data['group'] ?? 'client';
        $guard  = !empty($data['guard_id']) ? SecurityGuard::find($data['guard_id']) : null;
        $groups = collect();
        $totals = ['hours' => 0.0, 'amount' => 0.0, 'paid' => 0, 'unpaid' => 0];

        if ($guard) {
            $built  = $this->build($guard, $data, $group);
            $groups = $built['groups'];
            $totals = $built['totals'];
        }

        $page      = (int) ($data['page'] ?? 1);
        $paginated = new LengthAwarePaginator(
            $groups->forPage($page, self::PER_PAGE)->values(),
            $groups->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('reports.guard-payments.index', [
            'guards'    => SecurityGuard::query()->orderBy('fullname')->get(['id', 'fullname']),
            'clients'   => Client::query()->orderBy('name')->get(['id', 'name']),
            'events'    => Event::query()->orderBy('event_name')->get(['id', 'event_name', 'client_id']),
            'guard'     => $guard,
            'group'     => $group,
            'groups'    => $paginated,
            'totals'    => $totals,
            'filters'   => $data,
            'canEdit'   => auth()->user()->can('edit-guard-payments'),
            'canHistory' => auth()->user()->hasRole('Super Admin'),
        ]);
    }

    /**
     * Save the editable payment fields for one (event, guard) pair.
     * Mirrors EventGuardPaymentController@save, including the guard
     * invoice side effect, so behaviour does not depend on which screen
     * was used.
     */
    public function save(Request $request)
    {
        $data = $request->validate([
            'event_id'          => 'required|exists:events,id',
            'guard_id'          => 'required|exists:security_guards,id',
            'paid_by'           => 'nullable|string|max:255',
            'paid_from_account' => 'nullable|string|max:255',
            'paid_on'           => 'nullable|date',
            'remarks'           => 'nullable|string',
        ]);

        $payment = EventGuardPayment::updateOrCreate(
            ['event_id' => $data['event_id'], 'guard_id' => $data['guard_id']],
            [
                'paid_by'           => $this->blankToNull($data['paid_by'] ?? null),
                'paid_from_account' => $this->blankToNull($data['paid_from_account'] ?? null),
                'paid_on'           => $data['paid_on'] ?: null,
                'remarks'           => $this->blankToNull($data['remarks'] ?? null),
            ]
        );

        $message = 'Payment details saved.';

        if ($payment->paid_on) {
            $flipped = $this->markGuardInvoicePaid(
                (int) $data['event_id'],
                (int) $data['guard_id'],
                $payment->paid_on->toDateString()
            );

            if ($flipped) {
                $message .= ' Guard invoice marked paid.';
            }
        }

        return back()->with('success', $message);
    }

    /**
     * Edit history for one payment row, read from audit_logs.
     * Super Admin only — enforced on the route.
     */
    public function history(Request $request)
    {
        $data = $request->validate([
            'event_id' => 'required|integer',
            'guard_id' => 'required|integer',
        ]);

        $payment = EventGuardPayment::query()
            ->where('event_id', $data['event_id'])
            ->where('guard_id', $data['guard_id'])
            ->first();

        if (!$payment) {
            return response()->json(['entries' => []]);
        }

        $entries = AuditLog::query()
            ->with('user:id,name')
            ->where('auditable_type', EventGuardPayment::class)
            ->where('auditable_id', $payment->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (AuditLog $log) {
                $tracked = ['paid_by', 'paid_from_account', 'paid_on', 'remarks'];
                $changes = [];

                foreach ($tracked as $field) {
                    $new = data_get($log->new_values, $field);
                    $old = data_get($log->old_values, $field);

                    if ($log->action === 'updated' && !array_key_exists($field, (array) $log->new_values)) {
                        continue; // unchanged on this edit
                    }

                    if ($old === null && $new === null) {
                        continue;
                    }

                    $changes[] = [
                        'field' => $field,
                        'from'  => $this->stringify($old),
                        'to'    => $this->stringify($new),
                    ];
                }

                return [
                    'action'  => $log->action,
                    'user'    => $log->user?->name ?? 'System',
                    'at'      => optional($log->created_at)->format('d/m/Y H:i'),
                    'changes' => $changes,
                ];
            })
            ->filter(fn ($e) => $e['action'] !== 'updated' || count($e['changes']) > 0)
            ->values();

        return response()->json(['entries' => $entries]);
    }

    public function exportExcel(Request $request)
    {
        [$guard, $data, $group] = $this->resolveForExport($request);
        $built = $this->build($guard, $data, $group);

        $name = 'guard-payments-' . Str::slug($guard->fullname ?: ('guard-' . $guard->id)) . '.xlsx';

        return Excel::download(new GuardPaymentReportExport($built['groups'], $group), $name);
    }

    public function exportPdf(Request $request)
    {
        [$guard, $data, $group] = $this->resolveForExport($request);
        $built = $this->build($guard, $data, $group);

        $pdf = Pdf::loadView('reports.guard-payments.pdf', [
            'guard'       => $guard,
            'group'       => $group,
            'groups'      => $built['groups'],
            'totals'      => $built['totals'],
            'dateFrom'    => $data['date_from'] ?? null,
            'dateTo'      => $data['date_to'] ?? null,
            'generatedAt' => now('Europe/London')->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('guard-payments-' . Str::slug($guard->fullname ?: ('guard-' . $guard->id)) . '.pdf');
    }

    // ---------------------------------------------------------------

    private function resolveForExport(Request $request): array
    {
        $data = $request->validate([
            'guard_id'     => 'required|exists:security_guards,id',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date|after_or_equal:date_from',
            'client_ids'   => 'nullable|array',
            'client_ids.*' => 'integer|exists:clients,id',
            'event_ids'    => 'nullable|array',
            'event_ids.*'  => 'integer|exists:events,id',
            'group'        => 'nullable|in:client,event',
        ]);

        return [
            SecurityGuard::findOrFail($data['guard_id']),
            $data,
            $data['group'] ?? 'client',
        ];
    }

    /**
     * Build the grouped rows for one guard.
     *
     * Every shift is valued at that event's category-resolved pay rate
     * (V3-P3). Cancelled shifts are excluded — they pay as zero.
     */
    private function build(SecurityGuard $guard, array $filters, string $group): array
    {
        $query = EventShift::query()
            ->with(['event.client'])
            ->where('guard_id', $guard->id)
            ->whereNull('cancelled_at')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
        if (!empty($filters['event_ids'])) {
            $query->whereIn('event_id', $filters['event_ids']);
        }
        if (!empty($filters['client_ids'])) {
            $ids = $filters['client_ids'];
            $query->whereHas('event', fn ($q) => $q->whereIn('client_id', $ids));
        }

        $shifts = $query->orderBy('date')->orderBy('start_time')->get();

        $eventIds = $shifts->pluck('event_id')->filter()->unique()->all();

        $payments = EventGuardPayment::query()
            ->where('guard_id', $guard->id)
            ->whereIn('event_id', $eventIds)
            ->get()
            ->keyBy('event_id');

        $invoiceStatus = GuardInvoice::query()
            ->where('guard_id', $guard->id)
            ->whereIn('event_id', $eventIds)
            ->pluck('status', 'event_id');

        // Per-event blocks first — the common unit of both groupings.
        $events = [];
        $grandHours = 0.0;
        $grandAmount = 0.0;

        foreach ($shifts as $shift) {
            $event = $shift->event;
            $eid   = $event?->id ?? 0;
            $rate  = $event ? $event->rateForGuard($guard, 'pay') : 0.0;
            $hours = $this->shiftNetHours($shift);
            $amount = round($hours * $rate, 2);

            if (!isset($events[$eid])) {
                $payment = $payments->get($eid);

                $events[$eid] = [
                    'event_id'       => $eid,
                    'event_name'     => $event?->event_name ?? '-',
                    'client_id'      => $event?->client_id ?? 0,
                    'client_name'    => $event?->client?->name ?? '-',
                    'pay_rate'       => round($rate, 2),
                    'hours'          => 0.0,
                    'amount'         => 0.0,
                    'rows'           => [],
                    'invoice_status' => $invoiceStatus[$eid] ?? null,
                    'payment'        => [
                        'paid_by'           => $payment?->paid_by ?? '',
                        'paid_from_account' => $payment?->paid_from_account ?? '',
                        'paid_on'           => $payment?->paid_on?->format('Y-m-d') ?? '',
                        'remarks'           => $payment?->remarks ?? '',
                    ],
                ];
            }

            $events[$eid]['rows'][] = [
                'date'     => optional($shift->date)->format('d/m/Y'),
                'start'    => substr((string) $shift->start_time, 0, 5),
                'end'      => substr((string) $shift->end_time, 0, 5),
                'break'    => round((float) ($shift->break_hours ?? 0), 2),
                'hours'    => round($hours, 2),
                'amount'   => $amount,
                'location' => $shift->location ?: '',
            ];

            $events[$eid]['hours']  += $hours;
            $events[$eid]['amount'] += $amount;
            $grandHours  += $hours;
            $grandAmount += $amount;
        }

        foreach ($events as $eid => $e) {
            $events[$eid]['hours']  = round($e['hours'], 2);
            $events[$eid]['amount'] = round($e['amount'], 2);
        }

        $paidCount   = collect($events)->filter(fn ($e) => $e['payment']['paid_on'] !== '')->count();
        $unpaidCount = count($events) - $paidCount;

        $totals = [
            'hours'  => round($grandHours, 2),
            'amount' => round($grandAmount, 2),
            'paid'   => $paidCount,
            'unpaid' => $unpaidCount,
        ];

        if ($group === 'event') {
            $groups = collect(array_values($events))
                ->sortBy('event_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn ($e) => [
                    'key'    => 'event-' . $e['event_id'],
                    'title'  => $e['event_name'],
                    'sub'    => $e['client_name'],
                    'hours'  => $e['hours'],
                    'amount' => $e['amount'],
                    'events' => [$e],
                ])
                ->values();

            return ['groups' => $groups, 'totals' => $totals];
        }

        // Client-wise: client header, expanding to its events.
        $byClient = [];

        foreach ($events as $e) {
            $cid = $e['client_id'];

            $byClient[$cid] ??= [
                'key'    => 'client-' . $cid,
                'title'  => $e['client_name'],
                'sub'    => null,
                'hours'  => 0.0,
                'amount' => 0.0,
                'events' => [],
            ];

            $byClient[$cid]['hours']  += $e['hours'];
            $byClient[$cid]['amount'] += $e['amount'];
            $byClient[$cid]['events'][] = $e;
        }

        $groups = collect(array_values($byClient))
            ->map(function ($g) {
                $g['hours']  = round($g['hours'], 2);
                $g['amount'] = round($g['amount'], 2);
                $g['sub']    = count($g['events']) . ' ' . Str::plural('event', count($g['events']));
                return $g;
            })
            ->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return ['groups' => $groups, 'totals' => $totals];
    }

    /** Net payable hours for a shift (worked minus break, overnight-safe). */
    private function shiftNetHours(EventShift $shift): float
    {
        if (!$shift->date || !$shift->start_time || !$shift->end_time) {
            return 0.0;
        }

        $start = strtotime((string) $shift->date . ' ' . $shift->start_time);
        $end   = strtotime((string) $shift->date . ' ' . $shift->end_time);

        if ($end <= $start) {
            $end = strtotime('+1 day', $end);
        }

        $hours = ($end - $start) / 3600;
        $hours -= (float) ($shift->break_hours ?? 0);

        return max(0.0, round($hours, 2));
    }

    /**
     * Same rule as the event pay sheet: draft OR issued -> paid, and a
     * draft jumping straight to paid also gets issued_at stamped.
     * voided untouched; a missing invoice is not an error.
     */
    private function markGuardInvoicePaid(int $eventId, int $guardId, string $paidOn): bool
    {
        $invoice = GuardInvoice::query()
            ->where('event_id', $eventId)
            ->where('guard_id', $guardId)
            ->lockForUpdate()
            ->first();

        if (!$invoice || !in_array($invoice->status, ['draft', 'issued'], true)) {
            return false;
        }

        $invoice->status  = 'paid';
        $invoice->paid_at = $paidOn;

        if ($invoice->issued_at === null) {
            $invoice->issued_at = $paidOn;
        }

        $invoice->save();

        return true;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function stringify($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    }
}
