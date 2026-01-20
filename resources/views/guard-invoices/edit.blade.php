@extends('layouts.default')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-8">

    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Edit Guard Invoice</h1>
            <p class="text-sm text-gray-600 mt-1">{{ $guardInvoice->invoice_number }}</p>
            <p class="text-xs text-gray-500 mt-1">
                Event: {{ $guardInvoice->event?->event_name ?? $guardInvoice->event_name_snapshot ?? ('Event #'.$guardInvoice->event_id) }}
                • Guard ID: {{ $guardInvoice->guard_id }}
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('guard-invoices.show', $guardInvoice->id) }}"
               class="px-4 py-2 rounded bg-gray-600 text-white text-sm hover:bg-gray-700">
                Cancel
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('guard-invoices.update', $guardInvoice->id) }}">
        @csrf
        @method('PUT')

        <div class="bg-white border rounded-lg overflow-hidden">
            <div class="border-b p-4 font-semibold text-gray-900">
                Lines (Draft)
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3 w-16">#</th>
                            <th class="text-left px-4 py-3">Date</th>
                            <th class="text-left px-4 py-3">Description</th>
                            <th class="text-left px-4 py-3 w-44">Start</th>
                            <th class="text-left px-4 py-3 w-44">End</th>
                            <th class="text-right px-4 py-3 w-28">Hours</th>
                            <th class="text-right px-4 py-3 w-28">Rate</th>
                            <th class="text-right px-4 py-3 w-32">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($guardInvoice->lines as $line)
                            <tr class="border-t">
                                <td class="px-4 py-3">{{ $line->line_no }}</td>

                                <td class="px-4 py-3">
                                    <input type="date"
                                           name="lines[{{ $line->id }}][service_date]"
                                           value="{{ \Carbon\Carbon::parse($line->service_date)->format('Y-m-d') }}"
                                           class="border rounded px-2 py-1 text-sm w-full">
                                </td>

                                <td class="px-4 py-3">
                                    <input type="text"
                                           name="lines[{{ $line->id }}][description]"
                                           value="{{ $line->description }}"
                                           class="border rounded px-2 py-1 text-sm w-full"
                                           placeholder="Guard Services">
                                </td>

                                <td class="px-4 py-3">
                                    <input type="time"
                                           name="lines[{{ $line->id }}][shift_start]"
                                           value="{{ \Carbon\Carbon::parse($line->shift_start)->format('H:i') }}"
                                           class="border rounded px-2 py-1 text-sm w-full">
                                </td>

                                <td class="px-4 py-3">
                                    <input type="time"
                                           name="lines[{{ $line->id }}][shift_end]"
                                           value="{{ \Carbon\Carbon::parse($line->shift_end)->format('H:i') }}"
                                           class="border rounded px-2 py-1 text-sm w-full">
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <input type="number"
                                           step="0.01" min="0" max="24"
                                           name="lines[{{ $line->id }}][hours]"
                                           value="{{ number_format((float)$line->hours, 2, '.', '') }}"
                                           class="border rounded px-2 py-1 text-sm w-24 text-right">
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <input type="number"
                                           step="0.01" min="0"
                                           name="lines[{{ $line->id }}][rate]"
                                           value="{{ number_format((float)$line->rate, 2, '.', '') }}"
                                           class="border rounded px-2 py-1 text-sm w-24 text-right">
                                </td>

                                <td class="px-4 py-3 text-right text-gray-700">
                                    {{ number_format((float)$line->amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t bg-gray-50 p-4 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Amount column updates after save (server recalculates totals).
                </div>

                <button type="submit"
                        class="px-5 py-2 rounded bg-gray-900 text-white text-sm hover:bg-gray-800">
                    Save Draft
                </button>
            </div>
        </div>

        @if($errors->any())
            <div class="mt-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded text-sm">
                <div class="font-semibold mb-1">Fix these errors:</div>
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

    </form>

</div>
@endsection
