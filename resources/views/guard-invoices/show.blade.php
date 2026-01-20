@extends('layouts.default')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-8">

    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $guardInvoice->invoice_number }}</h1>

            <div class="text-sm text-gray-600 mt-2 space-y-1">
                <div>
                    Event:
                    <span class="font-medium text-gray-900">
                        {{ $guardInvoice->event?->event_name ?? $guardInvoice->event_name_snapshot ?? ('Event #'.$guardInvoice->event_id) }}
                    </span>
                    <span class="text-xs text-gray-500 ml-2">(ID: {{ $guardInvoice->event_id }})</span>
                </div>

                <div>
                    Guard:
                    <span class="font-medium text-gray-900">
                        {{ $guardInvoice->securityGuard?->fullname ?? $guardInvoice->securityGuard?->full_name ?? $guardInvoice->securityGuard?->name ?? ('Guard #'.$guardInvoice->guard_id) }}
                    </span>
                    <span class="text-xs text-gray-500 ml-2">(ID: {{ $guardInvoice->guard_id }})</span>
                </div>

                <div>
                    Pay Rate Snapshot:
                    <span class="font-medium text-gray-900">{{ number_format((float)$guardInvoice->pay_rate_snapshot, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="text-right">
            <div class="text-sm text-gray-600">Status</div>
            <div class="font-semibold text-gray-900">{{ strtoupper($guardInvoice->status) }}</div>

            {{-- ACTIONS --}}
            <div class="mt-4 flex flex-wrap gap-2 justify-end">
                @if($guardInvoice->status === 'draft')
                    <a href="{{ route('guard-invoices.edit', $guardInvoice->id) }}"
                       class="px-4 py-2 rounded bg-yellow-500 text-white text-sm hover:bg-yellow-600">
                        Edit Draft
                    </a>

                    <form method="POST" action="{{ route('guard-invoices.issue', $guardInvoice->id) }}">
                        @csrf
                        <button class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700" type="submit">
                            Issue + Store PDF
                        </button>
                    </form>
                @endif

                <a href="{{ route('guard-invoices.pdf', $guardInvoice->id) }}"
                   class="px-4 py-2 rounded bg-gray-900 text-white text-sm hover:bg-gray-800">
                    View PDF
                </a>

                <a href="{{ route('guard-invoices.download', $guardInvoice->id) }}"
                   class="px-4 py-2 rounded bg-gray-600 text-white text-sm hover:bg-gray-700">
                    Download
                </a>

                @if($guardInvoice->status === 'issued')
                    <form method="POST" action="{{ route('guard-invoices.mark-paid', $guardInvoice->id) }}">
                        @csrf
                        <button class="px-4 py-2 rounded bg-emerald-600 text-white text-sm hover:bg-emerald-700" type="submit">
                            Mark Paid
                        </button>
                    </form>
                @endif
            </div>

            <div class="mt-3">
                <a href="{{ route('guard-invoices.index', ['event_id' => $guardInvoice->event_id]) }}"
                   class="text-sm text-indigo-700 hover:underline">
                    ← Back to list
                </a>
            </div>
        </div>
    </div>

    {{-- Lines --}}
    <div class="bg-white border rounded-lg overflow-hidden mb-6">
        <div class="border-b p-4 font-semibold text-gray-900 flex items-center justify-between">
            <span>Lines</span>
            <span class="text-sm text-gray-600">{{ $guardInvoice->lines->count() }} line(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-4 py-3 w-16">#</th>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-left px-4 py-3">Description</th>
                        <th class="text-left px-4 py-3">Shift</th>
                        <th class="text-right px-4 py-3">Hours</th>
                        <th class="text-right px-4 py-3">Rate</th>
                        <th class="text-right px-4 py-3">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guardInvoice->lines as $line)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $line->line_no }}</td>
                            <td class="px-4 py-3">
                                {{ $line->service_date ? \Carbon\Carbon::parse($line->service_date)->format('d M Y') : '—' }}
                            </td>
                            <td class="px-4 py-3">{{ $line->description }}</td>
                            <td class="px-4 py-3">{{ $line->shift_start }} - {{ $line->shift_end }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float)$line->hours, 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float)$line->rate, 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float)$line->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr class="border-t">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No lines.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Totals --}}
        <div class="border-t bg-gray-50 p-4 flex justify-end gap-10 text-sm">
            <div class="text-right">
                <div class="text-gray-600">Total Hours</div>
                <div class="font-semibold text-gray-900">{{ number_format((float)$guardInvoice->total_hours, 2) }}</div>
            </div>
            <div class="text-right">
                <div class="text-gray-600">Subtotal</div>
                <div class="font-semibold text-gray-900">{{ number_format((float)$guardInvoice->subtotal, 2) }}</div>
            </div>
            <div class="text-right">
                <div class="text-gray-600">Total</div>
                <div class="font-semibold text-gray-900">{{ number_format((float)$guardInvoice->total, 2) }}</div>
            </div>
        </div>
    </div>

</div>
@endsection
