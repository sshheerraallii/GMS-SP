<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Support\Collection;
use App\Services\GuardPayments\ShiftPayCalculator;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EventStaffPaymentSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected Event $event;
    protected bool $reduced;

    public function __construct(int $eventId, bool $reduced = false)
    {
        $this->event = Event::query()->findOrFail($eventId);
        $this->reduced = $reduced;
    }

    /** Expense is only shown to roles allowed to see it (Super Admin / Accountant). */
    private function showExpense(): bool
    {
        return (bool) auth()->user()?->can('edit-guard-payments');
    }

    public function collection(): Collection
    {
        // V5-P3: per-shift pay-rate overrides + expense (see ShiftPayCalculator).
        $shifts = EventShift::query()
            ->where('event_id', $this->event->id)
            ->whereNull('cancelled_at')
            ->whereNotNull('guard_id')
            ->get()
            ->groupBy('guard_id');

        $guards = \App\Models\SecurityGuard::withTrashed()
            ->whereIn('id', $shifts->keys()->all())
            ->orderBy('fullname')
            ->get();

        return $guards->map(function ($guard) use ($shifts) {
            $calc = ShiftPayCalculator::summarise($this->event, $guard->category, $shifts->get($guard->id, collect()));

            $out = [
                'Name'        => $guard->fullname ?? '',
                'Total Hours' => number_format($calc['hours'], 2, '.', ''),
                'Rate'        => number_format($calc['rate'], 2, '.', '') . ($calc['rate_varies'] ? ' (avg)' : ''),
                'Total Pay'   => number_format($calc['amount'], 2, '.', ''),
            ];

            if ($this->showExpense()) {
                $out['Expense']       = number_format($calc['expense'], 2, '.', '');
                $out['Total Payable'] = number_format($calc['amount'] + $calc['expense'], 2, '.', '');
            }

            if ($this->reduced) {
                return $out;
            }

            $out['Sort Code'] = $guard->sort_code ?? '';
            $out['Account Number'] = $guard->account_number ?? '';

            return $out;
        })->values();
    }

    public function headings(): array
    {
        $base = ['Name', 'Total Hours', 'Rate', 'Total Pay'];

        if ($this->showExpense()) {
            $base = array_merge($base, ['Expense', 'Total Payable']);
        }

        return $this->reduced ? $base : array_merge($base, ['Sort Code', 'Account Number']);
    }

    public function title(): string
    {
        return $this->reduced ? 'Payment (No Bank)' : 'Staff Payment Sheet';
    }
}