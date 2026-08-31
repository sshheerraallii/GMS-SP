<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventExpense;
use App\Models\EventShift;
use Illuminate\Http\Request;

class ExecutiveController extends Controller
{
    /**
     * Executive homepage (Super Admin only). System-wide profit for a
     * date range, expandable per event, plus a single-client section.
     */
    public function index(Request $request)
    {
        $from = $request->input('date_from') ?: now('Europe/London')->startOfMonth()->toDateString();
        $to   = $request->input('date_to')   ?: now('Europe/London')->endOfMonth()->toDateString();
        $clientId = $request->input('client_id') ? (int) $request->input('client_id') : null;

        return view('executive.index', [
            'from'     => $from,
            'to'       => $to,
            'clientId' => $clientId,
            'clients'  => Client::query()->orderBy('name')->get(['id', 'name']),
            'system'   => $this->computeForEvents($from, $to, null),
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
     * Redirect back to the dashboard preserving the active filters.
     */
    private function backToDashboard(array $data, string $message)
    {
        return redirect()->route('executive.index', array_filter([
            'date_from' => $data['date_from'] ?? null,
            'date_to'   => $data['date_to'] ?? null,
            'client_id' => $data['client_id'] ?? null,
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

            $hours = 0.0;
            foreach ($shifts as $shift) {
                $hours += $this->shiftNetHours($shift);
            }

            $payRate    = (float) ($event->pay_rate ?? 0);
            $chargeRate = (float) ($event->charge_rate ?? 0);
            $pay    = round($hours * $payRate, 2);
            $charge = round($hours * $chargeRate, 2);

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

        return [
            'events' => $rows,
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
