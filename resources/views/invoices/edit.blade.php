@extends('layouts.default')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8"
     x-data="invoiceEditor(@js($invoice), @js($invoice->lines->values()), {{ $invoice->isDraft() ? 'true' : 'false' }})">

    {{-- Header --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-6">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900">Invoice</h1>

                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                    {{ $invoice->isDraft() ? 'bg-gray-900 text-white' : 'bg-amber-100 text-amber-900' }}">
                    {{ $invoice->isDraft() ? 'DRAFT' : strtoupper($invoice->status) }}
                </span>
            </div>

            <div class="text-sm text-gray-600">
                <span class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</span>
                <span class="mx-2 text-gray-300">•</span>
                <span class="text-gray-700">{{ $invoice->event_name_snapshot ?? '—' }}</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('invoices.show', $invoice) }}"
               class="px-4 py-2 rounded-lg border border-gray-300 text-gray-800 hover:bg-gray-50 text-sm font-medium">
                Back
            </a>

            @if($invoice->isDraft())
                <button type="submit" form="invoiceEditForm"
                        class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800 text-sm font-semibold">
                    Save Draft
                </button>

                <form method="POST" action="{{ route('invoices.issue', $invoice) }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-sm font-semibold">
                        Issue Invoice
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(!$invoice->isDraft())
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 text-sm">
            This invoice is <span class="font-semibold">issued</span> and is now read-only.
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 text-sm">
            <div class="font-semibold mb-1">Fix the following:</div>
            <ul class="list-disc ml-5 space-y-1">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="invoiceEditForm" method="POST" action="{{ route('invoices.update', $invoice) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Meta --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div class="font-semibold text-gray-900">Invoice Details</div>
                <div class="text-xs text-gray-500">
                    Total Lines: <span class="font-medium text-gray-900" x-text="lines.length"></span>
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-4 text-sm">
                <div class="lg:col-span-4 bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <div class="text-gray-500">Event</div>
                    <div class="font-semibold text-gray-900 mt-1">{{ $invoice->event_name_snapshot ?? '-' }}</div>
                    <div class="text-xs text-gray-500 mt-1">Event ID: {{ $invoice->event_id }}</div>
                </div>

                <div class="lg:col-span-4 bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <div class="text-gray-500">Supplier</div>
                    <div class="font-semibold text-gray-900 mt-1">{{ $invoice->supplier_name_snapshot ?? '—' }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        Client Type: {{ strtoupper($invoice->client_type_snapshot ?? '') === 'VAT' ? 'VAT' : 'NON VAT' }}
                    </div>
                </div>

                <div class="lg:col-span-4 bg-white border border-gray-200 rounded-xl p-4">
                    <label class="block text-gray-500 mb-2">VAT</label>
                    <select name="vat_mode"
                            class="w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900 text-sm"
                            x-model="vatMode"
                            @change="recalc()"
                            :disabled="!canEdit">
                        <option value="NON_VAT">NON VAT (0%)</option>
                        <option value="VAT">VAT (20%)</option>
                    </select>

                    <div class="mt-3 text-xs text-gray-500">
                        Charge Rate Snapshot:
                        <span class="font-semibold text-gray-900">{{ number_format((float)$invoice->charge_rate_snapshot, 2) }}</span>
                        /hr
                    </div>
                </div>

                <div class="lg:col-span-12 bg-white border border-gray-200 rounded-xl p-4">
                    <label class="block text-gray-500 mb-2">Payment Notes</label>
                    <textarea name="payment_notes"
                              class="w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900 text-sm"
                              rows="4"
                              :disabled="!canEdit">{{ old('payment_notes', $invoice->payment_notes) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Lines --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div class="font-semibold text-gray-900">Invoice Lines</div>

                <div class="flex items-center gap-2">
                    @if($invoice->isDraft())
                        <button type="button"
                                class="px-3 py-2 rounded-xl bg-gray-900 text-white hover:bg-gray-800 text-sm font-medium"
                                @click="addLine()">
                            + Add Line
                        </button>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left w-16">#</th>
                            <th class="px-4 py-3 text-left w-44">Date</th>
                            <th class="px-4 py-3 text-left min-w-[280px]">Detail</th>
                            <th class="px-4 py-3 text-left w-60">Shift</th>
                            <th class="px-4 py-3 text-left w-28">Qty</th>
                            <th class="px-4 py-3 text-left w-36">Line Hours</th>
                            <th class="px-4 py-3 text-left w-36">Rate</th>
                            <th class="px-4 py-3 text-left w-36">Amount</th>
                            <th class="px-4 py-3 text-right w-28"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        <template x-for="(line, idx) in lines" :key="line._key">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 align-top text-gray-700" x-text="idx + 1"></td>

                                <td class="px-4 py-3 align-top">
                                    <input type="date"
                                           class="w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                                           :name="`lines[${idx}][service_date]`"
                                           x-model="line.service_date"
                                           @input="recalc()"
                                           :disabled="!canEdit">
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <input type="text"
                                           class="w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                                           :name="`lines[${idx}][description]`"
                                           x-model="line.description"
                                           @input="recalc()"
                                           :disabled="!canEdit">
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center gap-2">
                                        <input type="time"
                                               class="w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                                               :name="`lines[${idx}][shift_start]`"
                                               x-model="line.shift_start"
                                               @input="recalc()"
                                               :disabled="!canEdit">

                                        <span class="text-gray-400">—</span>

                                        <input type="time"
                                               class="w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                                               :name="`lines[${idx}][shift_end]`"
                                               x-model="line.shift_end"
                                               @input="recalc()"
                                               :disabled="!canEdit">
                                    </div>

                                    <div class="text-xs text-gray-500 mt-2 flex items-center justify-between">
                                        <span>Hours: <span class="font-semibold text-gray-900" x-text="line.hours.toFixed(2)"></span></span>
                                        <span class="text-gray-400" x-show="line._overnight">Overnight</span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <input type="number" min="0" step="1"
                                           class="w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                                           :name="`lines[${idx}][quantity]`"
                                           x-model.number="line.quantity"
                                           @input="recalc()"
                                           :disabled="!canEdit">
                                </td>

                                <td class="px-4 py-3 align-top">
    <div class="font-semibold text-gray-900" x-text="(Number(line.quantity || 0) * Number(line.hours || 0)).toFixed(2)"></div>
    <div class="text-xs text-gray-500">qty × hours</div>
</td>


                                <td class="px-4 py-3 align-top">
                                    <input type="number" min="0" step="0.01"
                                           class="w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                                           :name="`lines[${idx}][rate]`"
                                           x-model.number="line.rate"
                                           @input="recalc()"
                                           :disabled="!canEdit">
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold text-gray-900" x-text="money(line.amount)"></div>
                                </td>

                                <td class="px-4 py-3 align-top text-right">
                                    @if($invoice->isDraft())
                                        <button type="button"
                                                class="text-red-600 hover:text-red-700 font-semibold"
                                                @click="removeLine(idx)"
                                                x-show="canEdit">
                                            Remove
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        </template>

                        <tr x-show="lines.length === 0">
                            <td colspan="9" class="px-6 py-10 text-center text-gray-500">
                                No lines yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="border-t border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm">
                        <div class="text-gray-600">Quick Summary</div>
                        <div class="mt-2 space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Hours</span>
                                <span class="font-semibold text-gray-900" x-text="totalHours.toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">VAT Mode</span>
                                <span class="font-semibold text-gray-900" x-text="vatMode"></span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-semibold text-gray-900" x-text="money(subtotal)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">VAT</span>
                                <span class="font-semibold text-gray-900" x-text="money(vatAmount)"></span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                                <span class="text-gray-900 font-semibold">Total</span>
                                <span class="text-gray-900 font-bold text-lg" x-text="money(total)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom save for long tables --}}
        @if($invoice->isDraft())
            <div class="flex items-center justify-end">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-gray-900 text-white hover:bg-gray-800 text-sm font-semibold">
                    Save Draft
                </button>
            </div>
        @endif
    </form>
</div>

<script>
function invoiceEditor(invoice, serverLines, canEdit) {
    const vatMode = (Number(invoice.vat_rate || 0) > 0) ? 'VAT' : 'NON_VAT';

    const lines = (serverLines || []).map((l, idx) => ({
        _key: (l.id ?? ('seed-' + idx)),

        // IMPORTANT FIX: input[type=date] needs YYYY-MM-DD only
        service_date: (l.service_date ? String(l.service_date).slice(0, 10) : ''),

        description: (l.description ?? 'Provision of Guards'),

        // input[type=time] needs HH:MM
        shift_start: (l.shift_start ? String(l.shift_start).slice(0, 5) : ''),
        shift_end:   (l.shift_end ? String(l.shift_end).slice(0, 5) : ''),

        quantity: Number(l.quantity ?? 0),
        rate: Number(l.rate ?? invoice.charge_rate_snapshot ?? 0),

        hours: Number(l.hours ?? 0),
        amount: Number(l.amount ?? 0),

        _overnight: false,
    }));

    return {
        canEdit: !!canEdit,
        vatMode,
        lines,

        subtotal: 0,
        vatAmount: 0,
        total: 0,
        totalHours: 0,

        init() { this.recalc(); },

        addLine() {
            if (!this.canEdit) return;

            this.lines.push({
                _key: 'new-' + crypto.randomUUID(),
                service_date: '',
                description: 'Provision of Guards',
                shift_start: '',
                shift_end: '',
                quantity: 0,
                rate: Number(invoice.charge_rate_snapshot ?? 0),
                hours: 0,
                amount: 0,
                _overnight: false,
            });

            this.recalc();
        },

        removeLine(idx) {
            if (!this.canEdit) return;

            this.lines.splice(idx, 1);
            this.recalc();
        },

        recalc() {
            let subtotal = 0;
            let totalHours = 0;

            for (const l of this.lines) {
                const result = this.hoursBetween(l.shift_start, l.shift_end);
                l.hours = result.hours;
                l._overnight = result.overnight;

                const qty = Number(l.quantity || 0);
                const rate = Number(l.rate || 0);

                l.amount = this.round2(qty * l.hours * rate);
                subtotal += l.amount;
                totalHours += (qty * l.hours);
            }

            this.subtotal = this.round2(subtotal);
            this.totalHours = this.round2(totalHours);

            const vatRate = (this.vatMode === 'VAT') ? 0.20 : 0.00;
            this.vatAmount = this.round2(this.subtotal * vatRate);
            this.total = this.round2(this.subtotal + this.vatAmount);
        },

        hoursBetween(start, end) {
            if (!start || !end) return { hours: 0, overnight: false };

            const s = this.toMinutes(start);
            let e = this.toMinutes(end);

            let overnight = false;
            if (e < s) {
                e += 1440;
                overnight = true;
            }

            const mins = Math.max(0, e - s);
            return { hours: this.round2(mins / 60), overnight };
        },

        toMinutes(t) {
            const [h, m] = String(t).split(':');
            return (Number(h || 0) * 60) + Number(m || 0);
        },

        round2(n) {
            return Math.round((Number(n) + Number.EPSILON) * 100) / 100;
        },

        money(n) {
            const x = Number(n || 0);
            return x.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    }
}
</script>
@endsection
