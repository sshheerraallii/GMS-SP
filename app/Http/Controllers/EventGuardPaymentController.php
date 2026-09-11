<?php

namespace App\Http\Controllers;

use App\Exports\EventGuardPaymentSheetExport;
use App\Models\Event;
use App\Models\EventGuardPayment;
use App\Models\GuardInvoice;
use App\Services\GuardPayments\BuildEventGuardPaymentRows;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * V3-P4: Event guard pay & payment sheet.
 *
 * Read access: any role that can view events (including Moderator — a
 * deliberate, recorded exception to view-guard-details, since this sheet
 * shows bank details by design).
 * Write access: Super Admin (gate bypass) + Accountant, via the
 * `edit-guard-payments` gate applied on the route.
 */
class EventGuardPaymentController extends Controller
{
    public function index(Event $event, BuildEventGuardPaymentRows $builder)
    {
        $rows = $builder->handle($event);

        return view('events.guard-payments.index', [
            'event'    => $event,
            'rows'     => $rows,
            'totals'   => [
                'hours'  => round($rows->sum('total_hours'), 2),
                'amount' => round($rows->sum('amount'), 2),
            ],
            'canEdit'  => auth()->user()->can('edit-guard-payments'),
        ]);
    }

    /**
     * Save the editable columns for every row on the sheet.
     *
     * One row per (event, guard) — updateOrCreate, so re-saving updates
     * rather than duplicating. Setting `paid_on` marks the matching guard
     * invoice paid (see markGuardInvoicePaid).
     */
    public function save(Request $request, Event $event)
    {
        $data = $request->validate([
            'rows'              => 'required|array',
            'rows.*.paid_by'    => 'nullable|string|max:255',
            'rows.*.paid_on'    => 'nullable|date',
            'rows.*.remarks'    => 'nullable|string',
        ]);

        $validGuardIds = $event->shifts()
            ->whereNotNull('guard_id')
            ->distinct()
            ->pluck('guard_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $markedPaid = 0;

        DB::transaction(function () use ($data, $event, $validGuardIds, &$markedPaid) {
            foreach ($data['rows'] as $guardId => $row) {
                $guardId = (int) $guardId;

                // Ignore anything not actually on this event.
                if (!in_array($guardId, $validGuardIds, true)) {
                    continue;
                }

                $paidBy  = isset($row['paid_by']) ? trim((string) $row['paid_by']) : null;
                $remarks = isset($row['remarks']) ? trim((string) $row['remarks']) : null;
                $paidOn  = $row['paid_on'] ?? null;

                EventGuardPayment::updateOrCreate(
                    ['event_id' => $event->id, 'guard_id' => $guardId],
                    [
                        'paid_by' => $paidBy !== '' ? $paidBy : null,
                        'paid_on' => $paidOn ?: null,
                        'remarks' => $remarks !== '' ? $remarks : null,
                    ]
                );

                if ($paidOn) {
                    $markedPaid += $this->markGuardInvoicePaid($event, $guardId, $paidOn);
                }
            }
        });

        $message = 'Payment details saved.';
        if ($markedPaid > 0) {
            $message .= ' ' . $markedPaid . ' guard ' . Str::plural('invoice', $markedPaid) . ' marked paid.';
        }

        return redirect()
            ->route('events.guardPayments.index', $event->id)
            ->with('success', $message);
    }

    /**
     * V3-P5: read-only JSON feed of events whose staff pay date is
     * TOMORROW and which are not yet fully paid.
     *
     * Same architecture as the existing shift reminders: polled
     * client-side, no cron, no new table. An event drops out of the feed
     * once every guard with shifts on it has a paid_on recorded.
     *
     * Available to anyone logged in (confirmed decision).
     */
    public function payDateReminders()
    {
        $tomorrow = now('Europe/London')->addDay()->toDateString();

        $events = Event::query()
            ->whereNotNull('staff_pay_date')
            ->whereDate('staff_pay_date', $tomorrow)
            ->get(['id', 'event_name', 'staff_pay_date']);

        if ($events->isEmpty()) {
            return response()->json(['reminders' => []]);
        }

        $eventIds = $events->pluck('id')->all();

        // Guards actually working each event (cancelled shifts excluded).
        $guardsByEvent = DB::table('event_shifts')
            ->whereIn('event_id', $eventIds)
            ->whereNull('cancelled_at')
            ->whereNotNull('guard_id')
            ->select('event_id', 'guard_id')
            ->distinct()
            ->get()
            ->groupBy('event_id');

        // Guards already marked paid on those events.
        $paidByEvent = DB::table('event_guard_payments')
            ->whereIn('event_id', $eventIds)
            ->whereNotNull('paid_on')
            ->select('event_id', 'guard_id')
            ->distinct()
            ->get()
            ->groupBy('event_id');

        $reminders = [];

        foreach ($events as $event) {
            $guardIds = ($guardsByEvent[$event->id] ?? collect())
                ->pluck('guard_id')->map(fn ($id) => (int) $id)->unique();

            // No guards on the event: nothing to pay, nothing to remind about.
            if ($guardIds->isEmpty()) {
                continue;
            }

            $paidIds = ($paidByEvent[$event->id] ?? collect())
                ->pluck('guard_id')->map(fn ($id) => (int) $id)->unique();

            $outstanding = $guardIds->diff($paidIds);

            if ($outstanding->isEmpty()) {
                continue; // fully paid — suppressed
            }

            $payDate = optional($event->staff_pay_date)->format('Y-m-d')
                ?? (string) $event->staff_pay_date;

            $reminders[] = [
                'key'          => 'paydate|' . $event->id . '|' . $payDate,
                'event_id'     => $event->id,
                'event_name'   => $event->event_name,
                'pay_date'     => $payDate,
                'guards_total' => $guardIds->count(),
                'guards_due'   => $outstanding->count(),
                'url'          => route('events.guardPayments.index', $event->id),
            ];
        }

        return response()->json(['reminders' => $reminders]);
    }

    public function export(Event $event)
    {
        $name = Str::slug($event->event_name ?: 'event') . '-guard-payments.xlsx';

        return Excel::download(new EventGuardPaymentSheetExport($event->id), $name);
    }

    /**
     * Mark the guard invoice for this (event, guard) as paid.
     *
     * Confirmed rule: draft OR issued -> paid. A draft that has never been
     * issued also gets `issued_at` stamped, so a paid invoice always has a
     * coherent issued-then-paid trail rather than a gap.
     * `voided` is left untouched. No guard invoice at all is not an error —
     * the payment is still recorded.
     *
     * NOTE: GuardInvoiceController@markPaid still requires status 'issued'.
     * This path is deliberately more permissive, per the V3-P4 decision.
     */
    private function markGuardInvoicePaid(Event $event, int $guardId, string $paidOn): int
    {
        $invoice = GuardInvoice::query()
            ->where('event_id', $event->id)
            ->where('guard_id', $guardId)
            ->lockForUpdate()
            ->first();

        if (!$invoice || !in_array($invoice->status, ['draft', 'issued'], true)) {
            return 0;
        }

        $invoice->status  = 'paid';
        $invoice->paid_at = $paidOn;

        if ($invoice->issued_at === null) {
            $invoice->issued_at = $paidOn;
        }

        $invoice->save();

        return 1;
    }
}
