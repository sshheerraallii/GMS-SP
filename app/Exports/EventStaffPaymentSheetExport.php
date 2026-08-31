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
    protected bool $reduced;

    public function __construct(int $eventId, bool $reduced = false)
    {
        $this->event = Event::query()->findOrFail($eventId);
        $this->reduced = $reduced;
    }

    public function collection(): Collection
    {
        $rows = EventShift::query()
            ->from('event_shifts as es')
            ->join('security_guards as sg', 'sg.id', '=', 'es.guard_id')
            ->where('es.event_id', $this->event->id)
            ->whereNull('es.cancelled_at')
            ->select([
                'es.guard_id',
                'sg.fullname',
                'sg.sort_code',
                'sg.account_number',
                'sg.category',
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
            ->groupBy('es.guard_id', 'sg.fullname', 'sg.sort_code', 'sg.account_number', 'sg.category')
            ->orderBy('sg.fullname')
            ->get();

        return $rows->map(function ($row) {
            $totalHours = $row->total_hours !== null ? round((float) $row->total_hours, 2) : 0;

            /*
             * V3-P3: rate resolves per guard from their category
             * (SIA / Steward), falling back to the event's base pay_rate.
             * Resolution always yields a number, so the previous
             * "rate not set" null path no longer arises here.
             */
            $numericRate = $this->event->rateFor($row->category, 'pay');
            $totalPay = round($totalHours * $numericRate, 2);

            $out = [
                'Name'        => $row->fullname ?? '',
                'Total Hours' => number_format($totalHours, 2, '.', ''),
                'Rate'        => number_format($numericRate, 2, '.', ''),
                'Total Pay'   => number_format($totalPay, 2, '.', ''),
            ];

            if ($this->reduced) {
                return $out;
            }

            $out['Sort Code'] = $row->sort_code ?? '';
            $out['Account Number'] = $row->account_number ?? '';

            return $out;
        });
    }

    public function headings(): array
    {
        $base = [
            'Name',
            'Total Hours',
            'Rate',
            'Total Pay',
        ];

        return $this->reduced ? $base : array_merge($base, ['Sort Code', 'Account Number']);
    }

    public function title(): string
    {
        return $this->reduced ? 'Payment (No Bank)' : 'Staff Payment Sheet';
    }
}