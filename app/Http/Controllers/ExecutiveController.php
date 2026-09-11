<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventExpense;
use App\Models\EventShift;
use App\Models\ProfitReceipt;
use App\Models\SecurityGuard;
use Illuminate\Http\Request;

class ExecutiveController extends Controller
{
    /** Far enough back to cover every shift ever recorded (credits are all-time). */
    private const ALL_TIME_START = '2000-01-01';

    /**
     * Executive homepage (Super Admin only). System-wide profit for a
     * date range, expandable per event, plus a single-client section.
     */
    public function index(Request $request)
    {
        $from = $request->input('date_from') ?: now('Europe/London')->startOfMonth()->toDateString();
        $to   = $request->input('date_to')   ?: now('Europe/London')->endOfMonth()->toDateString();
        $clientId = $request->input('client_id') ? (int) $request->input('client_id') : null;

        // V3-P6: event-wise (default) or client-wise grouping.
        $group = $request->input('group') === 'client' ? 'client' : 'event';

        return view('executive.index', [
            'group'    => $group,
            'from'     => $from,
            'to'       => $to,
            'clientId' => $clientId,
            'clients'  => Client::query()->orderBy('name')->get(['id', 'name']),
            'system'   => $this->computeForEvents($from, $to, null),
            'receipts' => $this->computeReceipts($from, $to),
            'client'   => $clientId ? $this->computeForEvents($from, $to, $clientId) : null,
        ]);
    }

    /**
     * Inline charge-rate write-back (Super Admin only, via the gate).
     * Writes to events.charge_rate — the same field the event edit form
     * uses — so it flows into invoicing.
     */
    public function setChargeRate(Request $request, Event $event)
    {
        $data = $request->validate([
            'charge_rate' => 'nullable|numeric|min:0',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date',
            'client_id'   => 'nullable|integer',
        ]);

        $event->update(['charge_rate' => $data['charge_rate'] ?? null]);

        return $this->backToDashboard($data, 'Charge rate updated.');
    }

    public function storeExpense(Request $request, Event $event)
    {
        $data = $request->validate([
            'label'     => 'required|string|max:255',
            'amount'    => 'required|numeric|min:0',
            'note'      => 'nullable|string|max:1000',
            'date'      => 'nullable|date',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'client_id' => 'nullable|integer',
        ]);

        $event->expenses()->create([
            'label'  => $data['label'],
            'amount' => $data['amount'],
            'note'   => $data['note'] ?? null,
            'date'   => $data['date'] ?? null,
        ]);

        return $this->backToDashboard($data, 'Expense added.');
    }

    public function deleteExpense(Request $request, EventExpense $eventExpense)
    {
        $data = $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'client_id' => 'nullable|integer',
        ]);

        $eventExpense->delete();

        return $this->backToDashboard($data, 'Expense removed.');
    }

    /**
     * V3-P7: record profit received from a client. Super Admin only — the
     * whole executive route group sits behind can:view-executive.
     */
    public function storeReceipt(Request $request)
    {
        $data = $request->validate([
            'receipt_client_id' => 'required|exists:clients,id',
            'amount'            => 'required|numeric|min:0',
            'received_on'       => 'required|date',
            'note'              => 'nullable|string|max:1000',
            'date_from'         => 'nullable|date',
            'date_to'           => 'nullable|date',
            'client_id'         => 'nullable|integer',
            'group'             => 'nullable|string',
        ]);

        ProfitReceipt::create([
            'client_id'   => $data['receipt_client_id'],
            'amount'      => $data['amount'],
            'received_on' => $data['received_on'],
            'note'        => $data['note'] ?? null,
            'created_by'  => auth()->id(),
        ]);

        return $this->backToDashboard($data, 'Receipt recorded.');
    }

    public function deleteReceipt(Request $request, ProfitReceipt $profitReceipt)
    {
        $data = $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'client_id' => 'nullable|integer',
            'group'     => 'nullable|string',
        ]);

        $profitReceipt->delete();

        return $this->backToDashboard($data, 'Receipt removed.');
    }

    /**
     * V3-P7 — confirmed formulas:
     *
     *   Profit (range)     charge - pay - expenses for shifts in range (existing calc)
     *   Received (range)   SUM(profit_receipts.amount) where received_on is in range
     *   Remaining (range)  Profit (range) - Received (range)
     *   Credits            ALL profit up to the range END
     *                      minus ALL receipts up to the range END
     *
     * Received and Remaining are date-bound. Credits deliberately IGNORE the
     * range start — they are the accumulated shortfall carried over from
     * every prior period, so they can exceed the range's own figures.
     */
    private function computeReceipts(string $from, string $to): array
    {
        $rangeProfit   = $this->profitByClient($this->computeForEvents($from, $to, null));
        $allTimeProfit = $this->profitByClient($this->computeForEvents(self::ALL_TIME_START, $to, null));

        $receivedRange = ProfitReceipt::query()
            ->whereBetween('received_on', [$from, $to])
            ->selectRaw('client_id, SUM(amount) as total')
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $receivedAll = ProfitReceipt::query()
            ->whereDate('received_on', '<=', $to)
            ->selectRaw('client_id, SUM(amount) as total')
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $names = Client::query()->pluck('name', 'id');

        $clientIds = collect(array_keys($rangeProfit))
            ->merge(array_keys($allTimeProfit))
            ->merge($receivedRange->keys())
            ->merge($receivedAll->keys())
            ->map(fn ($id) => (int) $id)
            ->unique();

        $rows = [];
        $tProfit = $tReceived = $tRemaining = $tCredits = 0.0;

        foreach ($clientIds as $cid) {
            $profit    = (float) ($rangeProfit[$cid] ?? 0);
            $received  = (float) ($receivedRange[$cid] ?? 0);
            $remaining = $profit - $received;
            $credits   = (float) ($allTimeProfit[$cid] ?? 0) - (float) ($receivedAll[$cid] ?? 0);

            if ($profit == 0.0 && $received == 0.0 && $credits == 0.0) {
                continue;
            }

            $rows[] = [
                'client_id'   => $cid,
                'client_name' => $names[$cid] ?? '-',
                'profit'      => round($profit, 2),
                'received'    => round($received, 2),
                'remaining'   => round($remaining, 2),
                'credits'     => round($credits, 2),
            ];

            $tProfit    += $profit;
            $tReceived  += $received;
            $tRemaining += $remaining;
            $tCredits   += $credits;
        }

        usort($rows, fn ($a, $b) => strcasecmp((string) $a['client_name'], (string) $b['client_name']));

        return [
            'clients' => $rows,
            'entries' => ProfitReceipt::query()
                ->with('client:id,name')
                ->whereBetween('received_on', [$from, $to])
                ->orderByDesc('received_on')
                ->get(),
            'totals'  => [
                'profit'    => round($tProfit, 2),
                'received'  => round($tReceived, 2),
                'remaining' => round($tRemaining, 2),
                'credits'   => round($tCredits, 2),
            ],
        ];
    }

    /** Reduce a computeForEvents() result to profit keyed by client id. */
    private function profitByClient(array $data): array
    {
        $out = [];

        foreach ($data['clients'] as $group) {
            $out[(int) ($group['client_id'] ?? 0)] = $group['profit'];
        }

        return $out;
    }

    /**
     * Redirect back to the dashboard preserving the active filters.
     */
    private function backToDashboard(array $data, string $message)
    {
        return redirect()->route('executive.index', array_filter([
            'date_from' => $data['date_from'] ?? null,
            'date_to'   => $data['date_to'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'group'     => $data['group'] ?? null,
        ]))->with('success', $message);
    }

    /**
     * Compute per-event and total figures for a date range, optionally
     * filtered to one client. Cancelled shifts are excluded (count as 0).
     */
    private function computeForEvents(string $from, string $to, ?int $clientId): array
    {
        $eventsQuery = Event::query()->with(['client', 'expenses']);
        if ($clientId) {
            $eventsQuery->where('client_id', $clientId);
        }
        $events = $eventsQuery->orderBy('event_name')->get();

        /*
         * V3-P3: guard categories drive rate resolution. Loaded once for
         * the whole page. withTrashed() because a soft-deleted guard's
         * historic shifts still carry hours that must be valued at that
         * guard's category rate.
         */
        $guardCategories = SecurityGuard::withTrashed()->pluck('category', 'id');

        $rows = [];
        $tHours = 0.0;
        $tPay = 0.0;
        $tCharge = 0.0;
        $tExp = 0.0;

        foreach ($events as $event) {
            $shifts = EventShift::query()
                ->where('event_id', $event->id)
                ->whereNull('cancelled_at')
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->whereBetween('date', [$from, $to])
                ->get();

            if ($shifts->isEmpty()) {
                continue;
            }

            /*
             * V3-P3: pay and charge are accumulated PER SHIFT at that
             * guard's resolved rate. The previous `total_hours * rate`
             * shortcut is invalid once SIA and Steward rates differ.
             * With no category rates set every shift resolves to the base
             * rate, so the total is arithmetically identical to before.
             */
            $hours  = 0.0;
            $pay    = 0.0;
            $charge = 0.0;

            foreach ($shifts as $shift) {
                $shiftHours = $this->shiftNetHours($shift);
                $category   = $guardCategories[$shift->guard_id] ?? null;

                $hours  += $shiftHours;
                $pay    += $shiftHours * $event->rateFor($category, 'pay');
                $charge += $shiftHours * $event->rateFor($category, 'charge');
            }

            $payRate = (float) ($event->pay_rate ?? 0);
            $pay     = round($pay, 2);
            $charge  = round($charge, 2);

            // Expenses dated within the range, plus any without a date.
            $expenses = $event->expenses
                ->filter(function ($e) use ($from, $to) {
                    if (!$e->date) {
                        return true;
                    }
                    $d = $e->date->toDateString();
                    return $d >= $from && $d <= $to;
                })
                ->sortBy('date')
                ->values();

            $expTotal = round((float) $expenses->sum('amount'), 2);
            $profit   = round($charge - $pay - $expTotal, 2);

            $rows[] = [
                'id'            => $event->id,
                'event_name'    => $event->event_name,
                'client_id'     => $event->client_id,
                'client_name'   => $event->client?->name ?? '—',
                'hours'         => round($hours, 2),
                'pay_rate'      => $payRate,
                'charge_rate'   => $event->charge_rate, // nullable → "not set"
                'pay'           => $pay,
                'charge'        => $charge,
                'expenses'      => $expenses,
                'expense_total' => $expTotal,
                'profit'        => $profit,
            ];

            $tHours  += $hours;
            $tPay    += $pay;
            $tCharge += $charge;
            $tExp    += $expTotal;
        }

        /*
         * V3-P6: client-wise grouping layered OVER the per-event rows.
         * The per-event computation above is untouched — a client row is
         * only ever the sum of its event rows, so the two views can never
         * disagree. Rates are deliberately not aggregated: they do not add
         * up meaningfully across events.
         */
        $clientGroups = [];

        foreach ($rows as $row) {
            $key = $row['client_id'] ?? 0;

            $clientGroups[$key] ??= [
                'client_id'     => $row['client_id'],
                'client_name'   => $row['client_name'],
                'hours'         => 0.0,
                'pay'           => 0.0,
                'charge'        => 0.0,
                'expense_total' => 0.0,
                'profit'        => 0.0,
                'events'        => [],
            ];

            $clientGroups[$key]['hours']         += $row['hours'];
            $clientGroups[$key]['pay']           += $row['pay'];
            $clientGroups[$key]['charge']        += $row['charge'];
            $clientGroups[$key]['expense_total'] += $row['expense_total'];
            $clientGroups[$key]['profit']        += $row['profit'];
            $clientGroups[$key]['events'][]       = $row;
        }

        foreach ($clientGroups as $key => $group) {
            foreach (['hours', 'pay', 'charge', 'expense_total', 'profit'] as $f) {
                $clientGroups[$key][$f] = round($group[$f], 2);
            }
        }

        usort($clientGroups, fn ($a, $b) => strcasecmp((string) $a['client_name'], (string) $b['client_name']));

        return [
            'events'  => $rows,
            'clients' => $clientGroups,
            'totals' => [
                'hours'    => round($tHours, 2),
                'pay'      => round($tPay, 2),
                'charge'   => round($tCharge, 2),
                'expenses' => round($tExp, 2),
                'profit'   => round($tCharge - $tPay - $tExp, 2),
            ],
        ];
    }

    /**
     * Net payable/chargeable hours for a shift (worked minus break,
     * overnight-safe). Mirrors the reports logic.
     */
    private function shiftNetHours(EventShift $shift): float
    {
        if (!$shift->date || !$shift->start_time || !$shift->end_time) {
            return 0.0;
        }

        $start = strtotime((string) $shift->start_time);
        $end   = strtotime((string) $shift->end_time);

        if ($end < $start) {
            $end += 86400;
        }

        $workedHours = ($end - $start) / 3600;
        $breakHours  = (float) ($shift->break_hours ?? 0);

        return round(max($workedHours - max(0, $breakHours), 0), 2);
    }
}
