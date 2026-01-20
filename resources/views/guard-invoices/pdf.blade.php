<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Guard Invoice {{ $guardInvoice->invoice_number }}</title>
    <style>
        @page { margin: 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .row { display: flex; justify-content: space-between; }
        h1 { margin: 0; font-size: 18px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border-bottom: 1px solid #ddd; padding: 8px 6px; text-align: left; }
        th { background: #f6f6f6; }
        .right { text-align: right; }
        .totals { margin-top: 16px; width: 260px; margin-left: auto; }
        .totals td { border: none; padding: 4px 0; }
    </style>
</head>
<body>

<div class="row">
    <div>
        <h1>Guard Invoice</h1>
        <div class="muted">Invoice #: {{ $guardInvoice->invoice_number }}</div>
        <div class="muted">Status: {{ strtoupper($guardInvoice->status) }}</div>
        @if($guardInvoice->issued_at)
            <div class="muted">Issued: {{ \Carbon\Carbon::parse($guardInvoice->issued_at)->format('d M Y H:i') }}</div>
        @endif
    </div>
    <div class="right">
        <div><strong>Event</strong></div>
        <div>{{ $guardInvoice->event?->event_name ?? $guardInvoice->event_name_snapshot ?? ('Event #'.$guardInvoice->event_id) }}</div>
        <div class="muted">Event ID: {{ $guardInvoice->event_id }}</div>
        <div class="muted">Guard ID: {{ $guardInvoice->guard_id }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 40px;">#</th>
            <th style="width: 90px;">Date</th>
            <th>Description</th>
            <th style="width: 120px;">Shift</th>
            <th class="right" style="width: 70px;">Hours</th>
            <th class="right" style="width: 70px;">Rate</th>
            <th class="right" style="width: 80px;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($guardInvoice->lines as $line)
            <tr>
                <td>{{ $line->line_no }}</td>
                <td>{{ $line->service_date ? \Carbon\Carbon::parse($line->service_date)->format('d M Y') : '—' }}</td>
                <td>{{ $line->description }}</td>
                <td>{{ $line->shift_start }} - {{ $line->shift_end }}</td>
                <td class="right">{{ number_format((float)$line->hours, 2) }}</td>
                <td class="right">{{ number_format((float)$line->rate, 2) }}</td>
                <td class="right">{{ number_format((float)$line->amount, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td class="muted">Total Hours</td>
        <td class="right"><strong>{{ number_format((float)$guardInvoice->total_hours, 2) }}</strong></td>
    </tr>
    <tr>
        <td class="muted">Subtotal</td>
        <td class="right"><strong>{{ number_format((float)$guardInvoice->subtotal, 2) }}</strong></td>
    </tr>
    <tr>
        <td class="muted">Total</td>
        <td class="right"><strong>{{ number_format((float)$guardInvoice->total, 2) }}</strong></td>
    </tr>
</table>

</body>
</html>
