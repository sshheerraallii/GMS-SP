@extends('layouts.default')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-10">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Invoice {{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-gray-500">
                Event: {{ $invoice->event_name_snapshot ?? optional($invoice->event)->event_name ?? '—' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($invoice->status === 'draft')
                <a href="{{ route('invoices.edit', $invoice) }}"
                   class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                    Edit Draft
                </a>
            @endif

            <a href="{{ route('invoices.pdf', $invoice) }}"
               class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                View PDF
            </a>

            <a href="{{ route('invoices.download', $invoice) }}"
               class="px-4 py-2 rounded-lg border border-gray-300 text-gray-800 text-sm font-semibold hover:bg-gray-50">
                Download
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-gray-500">Status</div>
                <div class="font-semibold text-gray-900">{{ strtoupper($invoice->status) }}</div>
            </div>
            <div>
                <div class="text-gray-500">Total</div>
                <div class="font-semibold text-gray-900">{{ number_format((float)$invoice->total, 2) }}</div>
            </div>
        </div>

        <div class="mt-6 border-t pt-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Lines</h2>

            @php
                // Expense lines are stored with sentinel shift 00:00:00 - 00:00:00 (no DB change required)
                $isExpenseLine = function ($line) {
                    $s = (string)($line->shift_start ?? '');
                    $e = (string)($line->shift_end ?? '');
                    return str_starts_with($s, '00:00') && str_starts_with($e, '00:00');
                };
            @endphp

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-y">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-2 font-medium">#</th>
                            <th class="px-4 py-2 font-medium">Date</th>
                            <th class="px-4 py-2 font-medium">Detail</th>
                            <th class="px-4 py-2 font-medium">Type</th>
                            <th class="px-4 py-2 font-medium">Shift</th>
                            <th class="px-4 py-2 font-medium text-right">Qty</th>
                            <th class="px-4 py-2 font-medium text-right">Hours</th>
                            <th class="px-4 py-2 font-medium text-right">Rate</th>
                            <th class="px-4 py-2 font-medium text-right">Amount</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @forelse($invoice->lines as $line)
                            @php $isExpense = $isExpenseLine($line); @endphp

                            <tr class="text-gray-900">
                                <td class="px-4 py-2 whitespace-nowrap">{{ $line->line_no }}</td>

                                <td class="px-4 py-2 whitespace-nowrap">
                                    {{ optional($line->service_date)->format('d M Y') ?? '—' }}
                                </td>

                                <td class="px-4 py-2">
                                    <div class="font-medium text-gray-900">{{ $line->description }}</div>
                                    @if($isExpense)
                                        <div class="text-xs text-gray-500">Extra expense</div>
                                    @endif
                                </td>

                                <td class="px-4 py-2 whitespace-nowrap">
                                    @if($isExpense)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            EXPENSE
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            GUARD
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                                    @if($isExpense)
                                        <span class="text-gray-400">—</span>
                                    @else
                                        {{ $line->shift_start ? substr($line->shift_start, 0, 5) : '—' }}
                                        -
                                        {{ $line->shift_end ? substr($line->shift_end, 0, 5) : '—' }}
                                    @endif
                                </td>

                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    {{ (int) $line->quantity }}
                                </td>

                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    @if($isExpense)
                                        <span class="text-gray-400">—</span>
                                    @else
                                        {{ number_format((float)($line->hours ?? 0), 2) }}
                                    @endif
                                </td>

                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    {{ number_format((float)$line->rate, 2) }}
                                </td>

                                <td class="px-4 py-2 whitespace-nowrap text-right font-medium">
                                    {{ number_format((float)$line->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">No lines.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end">
                <div class="w-full max-w-sm text-sm space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Hours</span>
                        <span class="font-medium">{{ number_format((float)$invoice->total_hours, 2) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium">{{ number_format((float)$invoice->subtotal, 2) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">VAT</span>
                        <span class="font-medium">{{ number_format((float)$invoice->vat_amount, 2) }}</span>
                    </div>

                    <div class="flex justify-between text-base border-t pt-2">
                        <span class="font-semibold">Total</span>
                        <span class="font-semibold">{{ number_format((float)$invoice->total, 2) }}</span>
                    </div>
                </div>
            </div>

            @if($invoice->payment_notes)
                <div class="mt-6 border-t pt-6">
                    <div class="text-sm font-semibold text-gray-900 mb-2">Payment Notes</div>
                    <div class="text-sm text-gray-700 whitespace-pre-line">{{ $invoice->payment_notes }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
