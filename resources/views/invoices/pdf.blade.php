<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>

    <style>
        @page { margin: 40px 40px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        h1, h2, h3 { margin: 0; padding: 0; }

        .header { width: 100%; margin-bottom: 30px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }

        .company { font-size: 14px; font-weight: bold; }

        .invoice-meta { text-align: right; font-size: 12px; }

        .section { margin-bottom: 25px; }
        .section-title { font-weight: bold; margin-bottom: 6px; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 0; }

        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .lines-table th,
        .lines-table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 11px;
        }

        .lines-table th {
            background: #f2f2f2;
            text-align: left;
        }

        .text-right { text-align: right; }

        .totals-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }

        .totals-table td { padding: 6px; font-size: 12px; }
        .totals-table .label { text-align: right; width: 85%; }
        .totals-table .value { text-align: right; width: 15%; }

        .footer { margin-top: 40px; font-size: 11px; }
    </style>
</head>

<body>

    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="company">
                        {{ $invoice->supplier_name_snapshot ?? '—' }}
                    </div>
                </td>
                <td class="invoice-meta">
                    <strong>Invoice</strong><br>
                    Invoice #: {{ $invoice->invoice_number }}<br>
                    Issued: {{ optional($invoice->issued_at)->format('d M Y') ?? '—' }}
                </td>
            </tr>
        </table>
    </div>

    {{-- Event / Client Info --}}
    <div class="section">
        <div class="section-title">Event Details</div>
        <table class="info-table">
            <tr>
                <td><strong>Event:</strong></td>
                <td>{{ $invoice->event_name_snapshot ?? '—' }}</td>
            </tr>
            <tr>
                <td><strong>Client Type:</strong></td>
                <td>{{ strtoupper($invoice->client_type_snapshot ?? 'NON VAT') }}</td>
            </tr>
        </table>
    </div>

    {{-- Invoice Lines --}}
    <div class="section">
        <table class="lines-table">
            <thead>
                <tr>
                    <th style="width:5%;">#</th>
                    <th style="width:12%;">Date</th>
                    <th>Description</th>
                    <th style="width:15%;">Shift</th>
                    <th style="width:8%;" class="text-right">Qty</th>
                    <th style="width:10%;" class="text-right">Total Hours</th>
                    <th style="width:12%;" class="text-right">Rate</th>
                    <th style="width:15%;" class="text-right">Amount</th>
                </tr>
            </thead>

            <tbody>
                @foreach($invoice->lines as $line)
                    <tr>
                        <td>{{ $line->line_no }}</td>

                        <td>
                            {{ optional($line->service_date)->format('d M Y') ?? '—' }}
                        </td>

                        <td>{{ $line->description }}</td>

                        <td>
                            {{ $line->shift_start ? substr($line->shift_start, 0, 5) : '—' }}
                            -
                            {{ $line->shift_end ? substr($line->shift_end, 0, 5) : '—' }}
                        </td>

                        <td class="text-right">{{ (int) $line->quantity }}</td>

                        <td class="text-right">
    {{ number_format(((float)($line->hours ?? 0)) * ((int)($line->quantity ?? 0)), 2) }}
</td>


                        <td class="text-right">{{ number_format((float)$line->rate, 2) }}</td>

                        <td class="text-right">{{ number_format((float)$line->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td class="label">Total Hours</td>
            <td class="value">{{ number_format((float)$invoice->total_hours, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ number_format((float)$invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">
                VAT {{ (float)$invoice->vat_rate > 0 ? '(20%)' : '(0%)' }}
            </td>
            <td class="value">{{ number_format((float)$invoice->vat_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label"><strong>Total</strong></td>
            <td class="value"><strong>{{ number_format((float)$invoice->total, 2) }}</strong></td>
        </tr>
    </table>

    {{-- Payment Notes --}}
    @if($invoice->payment_notes)
        <div class="footer">
            <strong>Notes:</strong><br>
            {!! nl2br(e($invoice->payment_notes)) !!}
        </div>
    @endif

</body>
</html>
