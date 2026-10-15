<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .meta { font-size: 9px; color: #555; margin-bottom: 10px; }
        .group { margin-bottom: 14px; }
        .group-head { background: #f0f0f0; padding: 5px 7px; font-weight: bold; font-size: 11px; }
        .event-head { padding: 4px 7px; font-size: 10px; background: #fafafa; border-bottom: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 3px 5px; }
        th { background: #f7f7f7; font-size: 9px; text-align: left; }
        .num { text-align: right; }
        .pay { font-size: 9px; padding: 4px 7px; background: #fbfbfb; border: 1px solid #eee; }
        .grand { margin-top: 12px; padding-top: 6px; border-top: 2px solid #333; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>

<h1>Guard Payment Report &mdash; {{ $guard->fullname }}</h1>
<div class="meta">
    Grouped {{ $group === 'client' ? 'client-wise' : 'event-wise' }}
    @if($dateFrom || $dateTo)
        &middot; {{ $dateFrom ?: 'start' }} to {{ $dateTo ?: 'today' }}
    @endif
    &middot; Generated {{ $generatedAt }}
</div>

@foreach($groups as $grp)
    <div class="group">
        <div class="group-head">
            {{ $grp['title'] }}
            @if($grp['sub']) &mdash; {{ $grp['sub'] }} @endif
            &mdash; {{ number_format($grp['hours'], 2) }} hrs / {{ number_format($grp['amount'], 2) }}
        </div>

        @foreach($grp['events'] as $ev)
            <div class="event-head">
                {{ $ev['event_name'] }} ({{ $ev['client_name'] }})
                &mdash; rate {{ !empty($ev['rate_varies']) ? 'varies' : number_format($ev['pay_rate'], 2) }}
                &middot; {{ number_format($ev['hours'], 2) }} hrs
                &middot; {{ number_format($ev['amount'], 2) }}
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th><th>Start</th><th>End</th>
                        <th class="num">Break</th><th class="num">Hours</th><th class="num">Amount</th>
                        @if($showExpense ?? false)<th class="num">Expense</th>@endif
                        <th>Site</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ev['rows'] as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['start'] }}</td>
                            <td>{{ $row['end'] }}</td>
                            <td class="num">{{ number_format($row['break'], 2) }}</td>
                            <td class="num">{{ number_format($row['hours'], 2) }}</td>
                            <td class="num">{{ number_format($row['amount'], 2) }}</td>
                            @if($showExpense ?? false)<td class="num">{{ number_format($row['expense'] ?? 0, 2) }}</td>@endif
                            <td>{{ $row['location'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pay">
                Paid by: {{ $ev['payment']['paid_by'] ?: '—' }}
                &middot; Account: {{ $ev['payment']['paid_from_account'] ?: '—' }}
                &middot; Paid on: {{ $ev['payment']['paid_on'] ?: '—' }}
                &middot; Remarks: {{ $ev['payment']['remarks'] ?: '—' }}
            </div>
        @endforeach
    </div>
@endforeach

<div class="grand">
    Total hours: {{ number_format($totals['hours'], 2) }}
    &middot; Total pay: {{ number_format($totals['amount'], 2) }}
    @if($showExpense ?? false)&middot; Total expense: {{ number_format($totals['expense'] ?? 0, 2) }}@endif
    &middot; Events paid/unpaid: {{ $totals['paid'] }}/{{ $totals['unpaid'] }}
</div>

</body>
</html>
