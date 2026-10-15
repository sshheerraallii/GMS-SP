<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .meta { font-size: 9px; color: #555; margin-bottom: 6px; }
        .notice { font-size: 9px; color: #444; background: #f5f5f5; border: 1px solid #ddd; padding: 4px 7px; margin-bottom: 12px; }
        .event-head { padding: 4px 7px; font-size: 10px; font-weight: bold; background: #f0f0f0; }
        .event { margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 3px 5px; }
        th { background: #f7f7f7; font-size: 9px; text-align: left; }
        .num { text-align: right; }
    </style>
</head>
<body>

<h1>Shift Summary &mdash; {{ $guard->fullname }}</h1>
<div class="meta">
    @if($dateFrom || $dateTo)
        {{ $dateFrom ?: 'start' }} to {{ $dateTo ?: 'today' }} &middot;
    @endif
    Generated {{ $generatedAt }}
</div>
<div class="notice">Guard copy: shows shifts and hours only. It does not include any payment details.</div>

@forelse($events as $ev)
    <div class="event">
        <div class="event-head">{{ $ev['event_name'] }} ({{ $ev['client_name'] }})</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th><th>Start</th><th>End</th>
                    <th class="num">Break (h)</th><th class="num">Hours</th>
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
                        <td>{{ $row['location'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <p>No shifts found for the selected filters.</p>
@endforelse

</body>
</html>
