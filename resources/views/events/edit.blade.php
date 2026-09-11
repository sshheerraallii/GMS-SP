@extends('layouts.default')

@section('content')
<div class="container mx-auto p-6"
     x-data="eventEditForm(@js($event), @js(old('daily_guards', $event->daily_guards ?? [])), @js(old('shift_mode', $event->shift_mode ?? 'same')))"
     x-init="init()">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Edit Event</h2>

        <div class="flex gap-2">
            <a href="{{ route('events.index') }}"
               class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Back
            </a>
        </div>
    </div>

    {{-- ERRORS --}}
    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-3 mb-4 rounded">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('events.update', $event) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        {{-- EVENT NAME --}}
        <div>
            <label class="block font-medium mb-1">Event Name</label>
            <input type="text"
                   name="event_name"
                   value="{{ old('event_name', $event->event_name) }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- ADDRESS --}}
        <div>
            <label class="block font-medium mb-1">Address</label>
            <input type="text"
                   name="address"
                   value="{{ old('address', $event->address) }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- CLIENT --}}
        <div>
            <label class="block font-medium mb-1">Client</label>
            <select name="client_id" class="w-full border rounded px-3 py-2">
                <option value="">Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}"
                        {{ old('client_id', $event->client_id) == $client->id ? 'selected' : '' }}>
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
        </div>

                {{-- CLIENT TYPE (VAT / NON-VAT) --}}
        <div>
            <label class="block font-medium mb-1">Client Type</label>
            <select name="client_type" class="w-full border rounded px-3 py-2">
                <option value="">Select Type</option>
                <option value="VAT" {{ old('client_type', $event->client_type) === 'VAT' ? 'selected' : '' }}>VAT</option>
                <option value="NON_VAT" {{ old('client_type', $event->client_type) === 'NON_VAT' ? 'selected' : '' }}>Non VAT</option>
            </select>
        </div>


        {{-- SUPPLIER --}}
        <div>
            <label class="block font-medium mb-1">Supplier</label>
            <select name="supplier_id" class="w-full border rounded px-3 py-2">
                <option value="">Select Supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}"
                        {{ old('supplier_id', $event->supplier_id) == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- PAYMENT TERMS --}}
        <div>
            <label class="block font-medium mb-1">Payment Terms</label>
            <select name="payment_terms" class="w-full border rounded px-3 py-2">
                <option value="">Select Payment Term</option>
                @foreach([
                    'weekly'   => 'Weekly',
                    'biweekly' => '2 Weeks',
                    'monthly'  => 'Monthly',
                    'days_55'  => '55 Days',
                    'weeks_5'  => '5 Weeks'
                ] as $value => $label)
                    <option value="{{ $value }}"
                        {{ old('payment_terms', $event->payment_terms) == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- START & END DATE --}}
        <div class="flex gap-4">
            <div class="w-1/2">
                <label class="block font-medium mb-1">Start Date</label>
                <input type="date"
                       name="start_date"
                       x-model="start_date"
                       class="w-full border rounded px-3 py-2">
            </div>
            <div class="w-1/2">
                <label class="block font-medium mb-1">End Date</label>
                <input type="date"
                       name="end_date"
                       x-model="end_date"
                       class="w-full border rounded px-3 py-2">
            </div>
        </div>

        @can('view-charge-rate')
        {{-- CHARGE RATE --}}
        <div>
            <label class="block font-medium mb-1">Charge Rate / Hour</label>
            <input type="number" step="0.01"
                   name="charge_rate"
                   value="{{ old('charge_rate', $event->charge_rate) }}"
                   class="w-full border rounded px-3 py-2">
        </div>
        @endcan

        {{-- INVOICE DATE --}}
        <div>
            <label class="block font-medium mb-1">Invoice Date</label>
            <input type="date"
                   name="invoice_date"
                   value="{{ old('invoice_date', $event->invoice_date) }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- STAFF PAY DATE (V3-P5) --}}
        <div>
            <label class="block font-medium mb-1">Staff Pay Date</label>
            <input type="date"
                   name="staff_pay_date"
                   value="{{ old('staff_pay_date', optional($event->staff_pay_date)->format('Y-m-d') ?? $event->staff_pay_date) }}"
                   class="w-full border rounded px-3 py-2">
            <p class="mt-1 text-xs text-gray-400">
                A reminder pops up the day before, and stops once every guard on this event is marked paid.
            </p>
        </div>

        {{-- PAY RATE --}}
        <div>
            <label class="block font-medium mb-1">Pay Rate / Hour</label>
            <input type="number" step="0.01"
                   name="pay_rate"
                   value="{{ old('pay_rate', $event->pay_rate) }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- CATEGORY RATES (V3-P3) — optional per-category overrides.
             Leave blank to use the base rates above. --}}
        <div x-data="{ open: {{ (($event->pay_rate_sia !== null || $event->pay_rate_steward !== null || $event->charge_rate_sia !== null || $event->charge_rate_steward !== null)) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                    class="flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900">
                <span x-text="open ? '−' : '+'"></span>
                Category rates (SIA / Steward) &mdash; optional
            </button>
            <p class="mt-1 text-xs text-gray-400">
                Set these only when SIA and Steward are paid or charged differently.
                Anything left blank falls back to the rates above.
            </p>

            <div x-show="open" x-cloak class="mt-3 grid grid-cols-1 gap-4 rounded border border-gray-200 bg-gray-50 p-3 sm:grid-cols-2">
                <div>
                    <label class="block font-medium mb-1">Pay Rate / Hour &mdash; SIA</label>
                    <input type="number" step="0.01" min="0" name="pay_rate_sia"
                           value="{{ old('pay_rate_sia', $event->pay_rate_sia) }}" placeholder="Uses base pay rate"
                           class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Pay Rate / Hour &mdash; Steward</label>
                    <input type="number" step="0.01" min="0" name="pay_rate_steward"
                           value="{{ old('pay_rate_steward', $event->pay_rate_steward) }}" placeholder="Uses base pay rate"
                           class="w-full border rounded px-3 py-2">
                </div>
                @can('view-charge-rate')
                <div>
                    <label class="block font-medium mb-1">Charge Rate / Hour &mdash; SIA</label>
                    <input type="number" step="0.01" min="0" name="charge_rate_sia"
                           value="{{ old('charge_rate_sia', $event->charge_rate_sia) }}" placeholder="Uses base charge rate"
                           class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Charge Rate / Hour &mdash; Steward</label>
                    <input type="number" step="0.01" min="0" name="charge_rate_steward"
                           value="{{ old('charge_rate_steward', $event->charge_rate_steward) }}" placeholder="Uses base charge rate"
                           class="w-full border rounded px-3 py-2">
                </div>
                @endcan
            </div>
        </div>

        {{-- CLIENT CONTACT --}}
        <div>
            <label class="block font-medium mb-1">Client Contact</label>
            <input type="text"
                   name="client_contact"
                   value="{{ old('client_contact', $event->client_contact) }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- INSTRUCTIONS --}}
        <div>
            <label class="block font-medium mb-1">Instructions</label>
            <textarea name="instructions" rows="4"
                      class="w-full border rounded px-3 py-2">{{ old('instructions', $event->instructions) }}</textarea>
        </div>

        {{-- SHIFT MODE --}}
        <div>
            <label class="block font-medium mb-2">Shift Type</label>

            {{-- IMPORTANT: actual field that gets submitted --}}
            <input type="hidden" name="shift_mode" :value="shift_mode">

            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input type="radio" value="same" x-model="shift_mode">
                    <span>Same shift for all guards</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" value="different" x-model="shift_mode">
                    <span>Different shifts for each guard</span>
                </label>
            </div>

            <p class="text-xs text-gray-500 mt-2">
                Guard/time assignments are edited on Page 2. If you change dates or daily guard counts here,
                review Page 2 afterwards.
            </p>
        </div>

        {{-- GUARDS REQUIRED PER DAY --}}
        <div>
            <label class="block font-medium mb-2">Guards Required Per Day</label>

            <div class="border rounded p-2 max-h-64 overflow-y-auto">
                <template x-for="day in days" :key="day">
                    <div class="flex items-center gap-4 border-b py-2">
                        <span class="w-32 font-medium" x-text="day"></span>

                        <input type="number"
                               min="0"
                               :name="'daily_guards['+day+']'"
                               class="border rounded px-2 py-1 w-24"
                               x-model.number="dailyGuardsByDate[day]">
                    </div>
                </template>

                <template x-if="days.length === 0">
                    <div class="text-gray-400 text-sm">
                        Select start and end date to generate days
                    </div>
                </template>
            </div>
        </div>

        {{-- ONE BUTTON ONLY: Save Page 1 then go to Page 2 --}}
        <div class="flex gap-3">
            @can('events.edit')
                <button type="submit"
                        name="redirect_to"
                        value="guards"
                        class="bg-blue-600 text-white px-6 py-2 rounded">
                    Save & Edit Assignments
                </button>
            @endcan

            <a href="{{ route('events.index') }}"
               class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700">
                Cancel
            </a>

            @cannot('events.edit')
                <span class="text-sm text-gray-500 self-center">
                    You don’t have permission to edit events.
                </span>
            @endcannot
        </div>

       </form>

    @can('events.edit')
        <div class="mt-8 border rounded-lg p-5 bg-gray-50">
            <h3 class="text-lg font-semibold mb-2">Import Additional Days</h3>

            <p class="text-sm text-gray-600 mb-4">
                Upload Excel to add more days to this event. Existing dates can be skipped, overwritten, or merged.
                Default action for duplicates is Skip.
            </p>

            <form action="{{ route('events.importAdditionalDays', $event) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-4">
                @csrf

                <div>
                    <label class="block font-medium mb-1">Excel File</label>
                    <input type="file"
                           name="import_file"
                           x-ref="additionalImportFile"
                           @change="previewAdditionalDaysImport($event)"
                           accept=".xlsx,.xls"
                           class="w-full border rounded px-3 py-2 bg-white">
                </div>

                <div>
                    <label class="block font-medium mb-1">Global Duplicate Action</label>
                    <select name="global_action"
                            x-model="additionalGlobalAction"
                            @change="applyGlobalAdditionalAction()"
                            class="w-full border rounded px-3 py-2 bg-white">
                        <option value="skip">Skip duplicate dates</option>
                        <option value="overwrite">Overwrite duplicate dates</option>
                        <option value="merge">Merge duplicate dates</option>
                    </select>
                </div>

                <template x-if="additionalImportMessage">
                    <p class="text-sm"
                       :class="additionalImportError ? 'text-red-600' : 'text-green-700'"
                       x-text="additionalImportMessage"></p>
                </template>

                <template x-if="additionalImportDates.length > 0">
                    <div class="border rounded bg-white overflow-hidden">
                        <div class="grid grid-cols-4 gap-2 bg-gray-100 px-3 py-2 text-sm font-semibold">
                            <div>Date</div>
                            <div>Rows</div>
                            <div>Status</div>
                            <div>Action</div>
                        </div>

                        <template x-for="item in additionalImportDates" :key="item.date">
                            <div class="grid grid-cols-4 gap-2 px-3 py-2 border-t text-sm items-center">
                                <div x-text="item.date"></div>

                                <div x-text="item.row_count"></div>

                                <div>
                                    <span x-show="item.is_duplicate"
                                          class="text-red-700 font-medium">
                                        Duplicate
                                    </span>

                                    <span x-show="!item.is_duplicate"
                                          class="text-green-700 font-medium">
                                        New Date
                                    </span>
                                </div>

                                <div>
                                    <template x-if="item.is_duplicate">
                                        <select :name="'date_actions[' + item.date + ']'"
                                                x-model="item.action"
                                                class="w-full border rounded px-2 py-1 bg-white">
                                            <option value="skip">Skip</option>
                                            <option value="overwrite">Overwrite</option>
                                            <option value="merge">Merge</option>
                                        </select>
                                    </template>

                                    <template x-if="!item.is_duplicate">
                                        <span class="text-gray-500">Will import</span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <button type="submit"
                        class="bg-emerald-600 text-white px-6 py-2 rounded hover:bg-emerald-700">
                    Import Additional Days
                </button>
            </form>
        </div>
    @endcan
</div>

<script>
function eventEditForm(eventData, initialDailyGuards, initialShiftMode) {
    return {
        start_date: '',
        end_date: '',
        shift_mode: 'same',

        days: [],
        dailyGuardsByDate: {},
        additionalImportMessage: '',
additionalImportError: false,
additionalImportDates: [],
additionalGlobalAction: 'skip',


        init() {
            this.start_date = (eventData.start_date || '').toString().slice(0, 10);
            this.end_date   = (eventData.end_date || '').toString().slice(0, 10);

            this.shift_mode = (initialShiftMode || eventData.shift_mode || 'same');

            this.dailyGuardsByDate = initialDailyGuards || {};

            this.generateDays(true);

            this.$watch('start_date', () => this.generateDays(true));
            this.$watch('end_date', () => this.generateDays(true));
        },

        generateDays(keepExisting=true) {
            this.days = [];

            if (!this.start_date || !this.end_date) return;

            const start = new Date(this.start_date);
            const end   = new Date(this.end_date);

            if (isNaN(start.getTime()) || isNaN(end.getTime())) return;
            if (start > end) return;

            const nextMap = {};

            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                const day = d.toISOString().slice(0, 10);
                this.days.push(day);

                if (keepExisting && this.dailyGuardsByDate[day] !== undefined && this.dailyGuardsByDate[day] !== null) {
                    nextMap[day] = Number(this.dailyGuardsByDate[day]) || 0;
                } else {
                    nextMap[day] = 0;
                }
            }

            this.dailyGuardsByDate = nextMap;
        },
        
        async previewAdditionalDaysImport(event) {
    const file = event.target.files?.[0];

    this.additionalImportMessage = '';
    this.additionalImportError = false;
    this.additionalImportDates = [];
    this.additionalGlobalAction = 'skip';

    if (!file) return;

    this.additionalImportMessage = 'Reading Excel...';

    const formData = new FormData();
    formData.append('import_file', file);

    try {
        const response = await fetch('{{ route('events.additionalDaysPreview', $event) }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Import preview failed.');
        }

        this.additionalImportDates = data.dates || [];
        this.additionalImportMessage = `Excel checked. ${data.row_count} row(s) found. Review duplicate actions before importing.`;
        this.additionalImportError = false;
    } catch (error) {
        console.error(error);
        this.additionalImportMessage = error.message || 'Failed to read Excel file.';
        this.additionalImportError = true;
    }
},

applyGlobalAdditionalAction() {
    this.additionalImportDates = this.additionalImportDates.map((item) => {
        if (item.is_duplicate) {
            item.action = this.additionalGlobalAction || 'skip';
        }

        return item;
    });
},
        
        
        
        
    }
}
</script>
@endsection
