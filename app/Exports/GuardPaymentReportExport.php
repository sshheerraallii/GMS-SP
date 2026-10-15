<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel export of the Guard Payment Report. Flattened one row per shift,
 * carrying the group and payment columns, so it can be filtered and
 * pivoted in Excel. Always the FULL filtered set, never one page.
 */
class GuardPaymentReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected Collection $groups,
        protected string $group = 'client'
    ) {
    }

    public function collection(): Collection
    {
        $out = collect();

        foreach ($this->groups as $group) {
            foreach ($group['events'] as $event) {
                foreach ($event['rows'] as $row) {
                    $out->push([
                        'Client'            => $event['client_name'],
                        'Event'             => $event['event_name'],
                        'Date'              => $row['date'],
                        'Start'             => $row['start'],
                        'End'               => $row['end'],
                        'Break'             => number_format((float) $row['break'], 2, '.', ''),
                        'Hours'             => number_format((float) $row['hours'], 2, '.', ''),
                        'Pay Rate'          => number_format((float) ($row['pay_rate'] ?? $event['pay_rate']), 2, '.', ''),
                        'Amount'            => number_format((float) $row['amount'], 2, '.', ''),
                        'Expense'           => number_format((float) ($row['expense'] ?? 0), 2, '.', ''),
                        'Site'              => $row['location'],
                        'Paid By'           => $event['payment']['paid_by'],
                        'Paid From Account' => $event['payment']['paid_from_account'],
                        'Paid On'           => $event['payment']['paid_on'],
                        'Remarks'           => $event['payment']['remarks'],
                    ]);
                }
            }
        }

        // V5-P3: expense column only for roles allowed to see it.
        if (!auth()->user()?->can('edit-guard-payments')) {
            $out = $out->map(function ($r) {
                unset($r['Expense']);
                return $r;
            });
        }

        return $out;
    }

    public function headings(): array
    {
        $h = ['Client', 'Event', 'Date', 'Start', 'End', 'Break', 'Hours', 'Pay Rate', 'Amount'];

        if (auth()->user()?->can('edit-guard-payments')) {
            $h[] = 'Expense';
        }

        return array_merge($h, ['Site', 'Paid By', 'Paid From Account', 'Paid On', 'Remarks']);
    }

    public function title(): string
    {
        return 'Guard Payments';
    }
}
