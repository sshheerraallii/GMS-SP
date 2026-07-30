<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; margin: 0; }
        .wrap { padding: 28px 32px; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        .muted { color: #6b7280; }
        .meta { margin-top: 4px; font-size: 11px; }
        .event { margin-top: 18px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; }
        .event-head { background: #f3f4f6; padding: 8px 10px; }
        .event-head .name { font-weight: bold; font-size: 13px; }
        .event-head .sub { font-size: 11px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 10px; text-align: left; }
        th { font-size: 10px; text-transform: uppercase; letter-spacing: .03em; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        td { border-bottom: 1px solid #f3f4f6; }
        .num { text-align: right; }
        .subtotal td { font-weight: bold; background: #fafafa; border-top: 1px solid #e5e7eb; }
        .grand { margin-top: 18px; border-top: 2px solid #111827; padding-top: 10px; }
        .grand-row { display: block; text-align: right; font-size: 14px; font-weight: bold; }
        .empty { margin-top: 24px; color: #6b7280; font-style: italic; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Guard Statement</h1>
    <div class="muted" style="font-size:13px;font-weight:bold;">{{ $guard->fullname }}</div>
    <div class="meta muted">
        Period:
        {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Start' }}
        &ndash;
        {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : 'End' }}
        &nbsp;|&nbsp; Generated {{ $generatedAt }} (UK)
    </div>

    @forelse($groups as $group)
        <div class="event">
            <div class="event-head">
                <div class="name">{{ $group['event_name'] }}</div>
                <div class="sub">
                    Client: {{ $group['client_name'] }} &nbsp;|&nbsp;
                    Pay rate: {{ number_format($group['pay_rate'], 2) }}/hr
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th class="num">Break</th>
                        <th class="num">Hours</th>
                        <th class="num">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group['rows'] as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['start'] }}</td>
                            <td>{{ $row['end'] }}</td>
                            <td class="num">{{ $row['break'] }}</td>
                            <td class="num">{{ $row['hours'] }}</td>
                            <td class="num">{{ $row['amount'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal">
                        <td colspan="4">Event subtotal</td>
                        <td class="num">{{ number_format($group['hours'], 2) }}</td>
                        <td class="num">{{ number_format($group['amount'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <p class="empty">No shifts found for this guard in the selected filters.</p>
    @endforelse

    <div class="grand">
        <span class="grand-row">Total hours: {{ $grandHours }}</span>
        <span class="grand-row">Total pay: {{ $grandAmount }}</span>
    </div>
</div>
</body>
</html>
