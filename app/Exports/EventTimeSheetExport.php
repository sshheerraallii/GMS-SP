<?php

namespace App\Exports;

use App\Models\EventShift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EventTimeSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(private int $eventId, private bool $reduced = false) {}

    public function headings(): array
    {
        $base = [
            'Sr',
            'Date',
            'First Names',
            'Last Name',
            'Site Location',
            'Start Time',
            'End Time',
            'Break',
            'Total Hours',
        ];

        if ($this->reduced) {
            return $base;
        }

        return array_merge($base, [
            'Role',
            'SIA',
            'Expiry',
            'DOB',
            ' Address',
            'NI Number',
            'Sharecode',
            'Email',
            'Contact',
            'Sort Code',
            'Account Number',
            'Beneficiary',
        ]);
    }

    public function collection(): Collection
    {
        $rows = EventShift::query()
            ->from('event_shifts as es')
            ->join('security_guards as sg', 'sg.id', '=', 'es.guard_id')
            ->where('es.event_id', $this->eventId)
            ->whereNull('es.cancelled_at')
            ->select([
                'es.date',
                'es.start_time',
                'es.end_time',
                'es.break_hours',
                'es.location',
                'sg.fullname',
                'sg.category',
                'sg.license_number',
                'sg.license_exp_date',
                'sg.dob',
                'sg.adresse',
                'sg.ni_number',
                'sg.rtw_share_code',
                'sg.email_address',
                'sg.phone_number',
                'sg.sort_code',
                'sg.account_number',
                'sg.beneficiary_name',
                DB::raw("
                    GREATEST(
                        (
                            TIME_TO_SEC(
                                TIMEDIFF(
                                    CASE
                                        WHEN es.end_time >= es.start_time THEN es.end_time
                                        ELSE ADDTIME(es.end_time, '24:00:00')
                                    END,
                                    es.start_time
                                )
                            ) / 3600
                        ) - COALESCE(es.break_hours, 0),
                        0
                    ) AS total_hours
                "),
            ])
            ->orderBy('es.date')
            ->orderBy('es.start_time')
            ->orderBy('sg.fullname')
            ->get();

        return $rows->values()->map(function ($row, $idx) {
            $full = trim((string) $row->fullname);

            $parts = preg_split('/\s+/', $full);
            $last = '';
            $first = $full;

            if (is_array($parts) && count($parts) >= 2) {
                $last = array_pop($parts);
                $first = implode(' ', $parts);
            }

            $base = [
                $idx + 1,
                $row->date ? Carbon::parse($row->date)->format('Y-m-d') : '',
                $first,
                $last,
                $row->location ?? '',
                $row->start_time ? substr((string) $row->start_time, 0, 5) : '',
                $row->end_time ? substr((string) $row->end_time, 0, 5) : '',
                number_format((float) ($row->break_hours ?? 0), 2, '.', ''),
                number_format((float) ($row->total_hours ?? 0), 2, '.', ''),
            ];

            if ($this->reduced) {
                return $base;
            }

            return array_merge($base, [
                $row->category ?? '',
                $row->license_number ?? '',
                $row->license_exp_date ? Carbon::parse($row->license_exp_date)->format('Y-m-d') : '',
                $row->dob ? Carbon::parse($row->dob)->format('Y-m-d') : '',
                $row->adresse ?? '',
                $row->ni_number ?? '',
                $row->rtw_share_code ?? '',
                $row->email_address ?? '',
                $row->phone_number ?? '',
                $row->sort_code ?? '',
                $row->account_number ?? '',
                $row->beneficiary_name ?? '',
            ]);
        });
    }

    public function title(): string
    {
        return $this->reduced ? 'Time Sheet (No Details)' : 'Time Sheet';
    }
}