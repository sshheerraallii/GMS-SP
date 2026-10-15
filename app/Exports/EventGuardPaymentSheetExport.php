<?php

namespace App\Exports;

use App\Models\Event;
use App\Services\GuardPayments\BuildEventGuardPaymentRows;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * V3-P4: Excel export of the guard pay & payment sheet.
 *
 * Always the full sheet including bank details — no reduced variant
 * (confirmed decision). Rows come from the shared builder so the export
 * and the on-screen sheet can never disagree.
 */
class EventGuardPaymentSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected Event $event;

    public function __construct(int $eventId)
    {
        $this->event = Event::query()->findOrFail($eventId);
    }

    /** Expense is only shown to roles allowed to see it (Super Admin / Accountant). */
    private function showExpense(): bool
    {
        return (bool) auth()->user()?->can('edit-guard-payments');
    }

    public function collection(): Collection
    {
        return (new BuildEventGuardPaymentRows())->handle($this->event)
            ->map(function (array $r) {
                $row = [
                'Guard Name'       => $r['guard_name'],
                'Total Hours'      => number_format($r['total_hours'], 2, '.', ''),
                'Pay Rate'         => number_format($r['pay_rate'], 2, '.', '') . (($r['rate_varies'] ?? false) ? ' (avg)' : ''),
                'Amount'           => number_format($r['amount'], 2, '.', ''),
                'Sort Code'        => $r['sort_code'],
                'Account Number'   => $r['account_number'],
                'Beneficiary Name' => $r['beneficiary_name'],
                'Paid By'          => $r['paid_by'],
                'Paid From Account'=> $r['paid_from_account'],
                'Paid On'          => $r['paid_on'],
                'Remarks'          => $r['remarks'],
                ];

                if ($this->showExpense()) {
                    $row = array_slice($row, 0, 4, true)
                        + [
                            'Expense'       => number_format($r['expense'] ?? 0, 2, '.', ''),
                            'Total Payable' => number_format($r['amount'] + ($r['expense'] ?? 0), 2, '.', ''),
                        ]
                        + array_slice($row, 4, null, true);
                }

                return $row;
            });
    }

    public function headings(): array
    {
        $h = ['Guard Name', 'Total Hours', 'Pay Rate', 'Amount'];

        if ($this->showExpense()) {
            $h = array_merge($h, ['Expense', 'Total Payable']);
        }

        return array_merge($h, [
            'Sort Code',
            'Account Number',
            'Beneficiary Name',
            'Paid By',
            'Paid From Account',
            'Paid On',
            'Remarks',
        ]);
    }

    public function title(): string
    {
        return 'Guard Pay & Payments';
    }
}
