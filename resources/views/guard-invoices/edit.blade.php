@extends('layouts.default')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-8"
     x-data="guardInvoiceEditor(@js($guardInvoice), @js($guardInvoice->lines->values()))">

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

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 text-sm">
            <div class="font-semibold mb-1">Fix these errors:</div>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('guard-invoices.update', $guardInvoice->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white border rounded-lg overflow-hidden">
            <div class="border-b p-4 flex items-center justify-between">
                <div class="font-semibold text-gray-900">
                    Lines (Draft)
                </div>

                <div class="text-sm text-gray-500">
                    Total Lines: <span class="font-medium text-gray-900" x-text="lines.length"></span>
                </div>
            </div>

            <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-amber-900">
                Hours are the final editable invoice values. Shift start/end are reference fields and do not auto-recalculate totals until you edit Hours.
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
                            <th class="text-right px-4 py-3 w-32">Hours</th>
                            <th class="text-right px-4 py-3 w-28">Rate</th>
                            <th class="text-right px-4 py-3 w-32">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, idx) in lines" :key="line._key">
                            <tr class="border-t">
                                <td class="px-4 py-3" x-text="line.line_no"></td>

                                <td class="px-4 py-3">
                                    <input type="date"
                                           class="border rounded px-2 py-1 text-sm w-full"
                                           :name="`lines[${line.id}][service_date]`"
                                           x-model="line.service_date">
                                </td>

                                <td class="px-4 py-3">
                                    <input type="text"
                                           class="border rounded px-2 py-1 text-sm w-full"
                                           :name="`lines[${line.id}][description]`"
                                           x-model="line.description"
                                           placeholder="Guard Services">
                                </td>

                                <td class="px-4 py-3">
                                    <input type="time"
                                           class="border rounded px-2 py-1 text-sm w-full"
                                           :name="`lines[${line.id}][shift_start]`"
                                           x-model="line.shift_start">
                                </td>

                                <td class="px-4 py-3">
                                    <input type="time"
                                           class="border rounded px-2 py-1 text-sm w-full"
                                           :name="`lines[${line.id}][shift_end]`"
                                           x-model="line.shift_end">
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <input type="number"
                                           step="0.01" min="0" max="24"
                                           class="border rounded px-2 py-1 text-sm w-24 text-right"
                                           :name="`lines[${line.id}][hours]`"
                                           x-model.number="line.hours"
                                           @input="recalc()">
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <input type="number"
                                           step="0.01" min="0"
                                           class="border rounded px-2 py-1 text-sm w-24 text-right"
                                           :name="`lines[${line.id}][rate]`"
                                           x-model.number="line.rate"
                                           @input="recalc()">
                                </td>

                                <td class="px-4 py-3 text-right text-gray-700 font-semibold"
                                    x-text="money(line.amount)">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="border-t bg-gray-50 p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-lg border bg-white p-4 text-sm">
                        <div class="text-gray-600">Quick Summary</div>
                        <div class="mt-2 space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Hours</span>
                                <span class="font-semibold text-gray-900" x-text="money(totalHours)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-semibold text-gray-900" x-text="money(subtotal)"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-end justify-end">
                        <button type="submit"
                                class="px-5 py-2 rounded bg-gray-900 text-white text-sm hover:bg-gray-800">
                            Save Draft
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function guardInvoiceEditor(guardInvoice, serverLines) {
    const lines = (serverLines || []).map((line, idx) => ({
        _key: line.id ?? ('seed-' + idx),
        id: line.id,
        line_no: line.line_no ?? (idx + 1),
        service_date: line.service_date ? String(line.service_date).slice(0, 10) : '',
        description: line.description ?? 'Guard Services',
        shift_start: line.shift_start ? String(line.shift_start).slice(0, 5) : '',
        shift_end: line.shift_end ? String(line.shift_end).slice(0, 5) : '',
        hours: Number(line.hours ?? 0),
        rate: Number(line.rate ?? 0),
        amount: Number(line.amount ?? 0),
    }));

    return {
        lines,
        totalHours: 0,
        subtotal: 0,

        init() {
            this.recalc();
        },

        recalc() {
            let totalHours = 0;
            let subtotal = 0;

            for (const line of this.lines) {
                line.hours = this.round2(Math.max(0, Number(line.hours || 0)));
                line.rate = this.round2(Math.max(0, Number(line.rate || 0)));
                line.amount = this.round2(line.hours * line.rate);

                totalHours += line.hours;
                subtotal += line.amount;
            }

            this.totalHours = this.round2(totalHours);
            this.subtotal = this.round2(subtotal);
        },

        round2(n) {
            return Math.round((Number(n) + Number.EPSILON) * 100) / 100;
        },

        money(n) {
            const x = Number(n || 0);
            return x.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
}
</script>
@endsection