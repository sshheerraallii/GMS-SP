<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EventStaffPaymentSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected Event $event;

    public function __construct(int $eventId)
    {
        $this->event = Event::query()->findOrFail($eventId);
    }

    public function collection(): Collection
    {
        $rate = $this->event->pay_rate;

        $rows = EventShift::query()
            ->from('event_shifts as es')
            ->join('security_guards as sg', 'sg.id', '=', 'es.guard_id')
            ->where('es.event_id', $this->event->id)
            ->select([
                'es.guard_id',
                'sg.fullname',
                'sg.sort_code',
                'sg.account_number',
                DB::raw("
                    SUM(
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
                        )
                    ) AS total_hours
                "),
            ])
            ->groupBy('es.guard_id', 'sg.fullname', 'sg.sort_code', 'sg.account_number')
            ->orderBy('sg.fullname')
            ->get();

        return $rows->map(function ($row) use ($rate) {
            $totalHours = $row->total_hours !== null ? round((float) $row->total_hours, 2) : 0;
            $numericRate = is_null($rate) ? null : (float) $rate;
            $totalPay = is_null($numericRate) ? null : round($totalHours * $numericRate, 2);

            return [
                'Name'           => $row->fullname ?? '',
                'Total Hours'    => number_format($totalHours, 2, '.', ''),
                'Rate'           => is_null($numericRate) ? '' : number_format($numericRate, 2, '.', ''),
                'Total Pay'      => is_null($totalPay) ? '' : number_format($totalPay, 2, '.', ''),
                'Sort Code'      => $row->sort_code ?? '',
                'Account Number' => $row->account_number ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Total Hours',
            'Rate',
            'Total Pay',
            'Sort Code',
            'Account Number',
        ];
    }

    public function title(): string
    {
        return 'Staff Payment Sheet';
    }
}