@extends('layouts.default')

@section('content')
<div class="container mx-auto p-6" x-data="eventForm()">

    <h2 class="text-2xl font-bold mb-6">Create New Event</h2>

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

   <form action="{{ route('events.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf

        {{-- EVENT NAME --}}
        <div>
            <label class="block font-medium mb-1">Event Name</label>
            <input type="text" name="event_name" value="{{ old('event_name') }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- ADDRESS --}}
        <div>
            <label class="block font-medium mb-1">Address</label>
            <input type="text" name="address" value="{{ old('address') }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- CLIENT --}}
        <div>
            <label class="block font-medium mb-1">Client</label>
            <select name="client_id" class="w-full border rounded px-3 py-2">
                <option value="">Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}"
                        {{ old('client_id') == $client->id ? 'selected' : '' }}>
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
                <option value="VAT" {{ old('client_type') === 'VAT' ? 'selected' : '' }}>VAT</option>
                <option value="NON_VAT" {{ old('client_type') === 'NON_VAT' ? 'selected' : '' }}>Non VAT</option>
            </select>
        </div>

    

        {{-- SUPPLIER --}}
        <div>
            <label class="block font-medium mb-1">Supplier</label>
            <select name="supplier_id" class="w-full border rounded px-3 py-2">
                <option value="">Select Supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}"
                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
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
                        {{ old('payment_terms') == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        
        
        
                {{-- IMPORT XLSX --}}
        <div>
            <label class="block font-medium mb-1">Import Excel Template (.xlsx)</label>
            <input type="file"
                   name="import_file"
                   x-ref="importFile"
                   @change="previewImport($event)"
                   accept=".xlsx,.xls"
                   class="w-full border rounded px-3 py-2 bg-white">

            <p class="text-sm text-gray-500 mt-1">
                Optional. If provided, Start Date, End Date, and Guards Required Per Day will be auto-filled from the Excel.
               Date, Start Time, End Time, Break Hours, and Location are used. Other columns are ignored.
            </p>

            <template x-if="importMessage">
                <p class="text-sm mt-2"
                   :class="importError ? 'text-red-600' : 'text-green-600'"
                   x-text="importMessage"></p>
            </template>
        </div>
        
        
        

        {{-- START & END DATE --}}
        <div class="flex gap-4">
            <div class="w-1/2">
                <label class="block font-medium mb-1">Start Date</label>
                <input type="date" name="start_date" x-model="start_date"
                       class="w-full border rounded px-3 py-2">
            </div>
            <div class="w-1/2">
                <label class="block font-medium mb-1">End Date</label>
                <input type="date" name="end_date" x-model="end_date"
                       class="w-full border rounded px-3 py-2">
            </div>
            
                    <p class="text-sm text-gray-500">
            If Excel is uploaded, these dates should match the imported file range.
        </p>
            
        </div>

        @can('view-charge-rate')
        {{-- CHARGE RATE --}}
        <div>
            <label class="block font-medium mb-1">Charge Rate / Hour</label>
            <input type="number" step="0.01" name="charge_rate"
                   value="{{ old('charge_rate') }}"
                   class="w-full border rounded px-3 py-2">
        </div>
        @endcan

        {{-- INVOICE DATE --}}
        <div>
            <label class="block font-medium mb-1">Invoice Date</label>
            <input type="date" name="invoice_date"
                   value="{{ old('invoice_date') }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- STAFF PAY DATE (V3-P5) --}}
        <div>
            <label class="block font-medium mb-1">Staff Pay Date</label>
            <input type="date" name="staff_pay_date"
                   value="{{ old('staff_pay_date') }}"
                   class="w-full border rounded px-3 py-2">
            <p class="mt-1 text-xs text-gray-400">
                A reminder pops up the day before, and stops once every guard on this event is marked paid.
            </p>
        </div>

        {{-- PAY RATE --}}
        <div>
            <label class="block font-medium mb-1">Pay Rate / Hour</label>
            <input type="number" step="0.01" name="pay_rate"
                   value="{{ old('pay_rate') }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- CATEGORY RATES (V3-P3) — optional per-category overrides.
             Leave blank to use the base rates above. --}}
        <div x-data="{ open: {{ (old('pay_rate_sia') !== null || old('pay_rate_steward') !== null || old('charge_rate_sia') !== null || old('charge_rate_steward') !== null) ? 'true' : 'false' }} }">
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
                           value="{{ old('pay_rate_sia') }}" placeholder="Uses base pay rate"
                           class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Pay Rate / Hour &mdash; Steward</label>
                    <input type="number" step="0.01" min="0" name="pay_rate_steward"
                           value="{{ old('pay_rate_steward') }}" placeholder="Uses base pay rate"
                           class="w-full border rounded px-3 py-2">
                </div>
                @can('view-charge-rate')
                <div>
                    <label class="block font-medium mb-1">Charge Rate / Hour &mdash; SIA</label>
                    <input type="number" step="0.01" min="0" name="charge_rate_sia"
                           value="{{ old('charge_rate_sia') }}" placeholder="Uses base charge rate"
                           class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Charge Rate / Hour &mdash; Steward</label>
                    <input type="number" step="0.01" min="0" name="charge_rate_steward"
                           value="{{ old('charge_rate_steward') }}" placeholder="Uses base charge rate"
                           class="w-full border rounded px-3 py-2">
                </div>
                @endcan
            </div>
        </div>

        {{-- CLIENT CONTACT --}}
        <div>
            <label class="block font-medium mb-1">Client Contact</label>
            <input type="text" name="client_contact"
                   value="{{ old('client_contact') }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- INSTRUCTIONS --}}
        <div>
            <label class="block font-medium mb-1">Instructions</label>
            <textarea name="instructions" rows="4"
                      class="w-full border rounded px-3 py-2">{{ old('instructions') }}</textarea>
        </div>

                {{-- SHIFT MODE --}}
        <div>
            <label class="block font-medium mb-2">Shift Type</label>
            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input type="radio" name="shift_mode" value="same" x-model="shift_mode">
                    <span>Same shift for all guards</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="shift_mode" value="different" x-model="shift_mode">
                    <span>Different shifts for each guard</span>
                </label>
            </div>

            <template x-if="shiftModeMessage">
                <p class="text-sm mt-2 text-blue-700" x-text="shiftModeMessage"></p>
            </template>
        </div>

        {{-- GUARDS PER DAY --}}
        <div>
            <label class="block font-medium mb-2">Guards Required Per Day</label>

            <div class="border rounded p-2 max-h-64 overflow-y-auto">
                <template x-for="day in days" :key="day">
                    <div class="flex items-center gap-4 border-b py-2">
                        <span class="w-32 font-medium" x-text="day"></span>

                       <input type="number" min="0"
       :name="'daily_guards['+day+']'"
       x-model="dailyGuardCounts[day]"
       class="border rounded px-2 py-1 w-24">
                    </div>
                </template>

                <template x-if="days.length === 0">
                    <div class="text-gray-400 text-sm">
                        Select start and end date to generate days
                    </div>
                </template>
            </div>
        </div>

    <div>
    <button type="submit"
        class="bg-emerald-600 text-white px-6 py-2 rounded">
    Next
</button>

    </div>

<script>
    
    
function eventForm() {
    return {
        start_date: '',
        end_date: '',
        guardModal: false,
        search: '',
        selectedGuards: [],
        days: [],
        dailyGuardCounts: {},
        importMessage: '',
        importError: false,
         shift_mode: 'same',
        shiftModeMessage: '',
        globalImportedStart: '',
        globalImportedEnd: '',

        init() {
            this.$watch('start_date', () => this.generateDays());
            this.$watch('end_date', () => this.generateDays());
        },

        generateDays() {
            const previousCounts = { ...this.dailyGuardCounts };

            this.days = [];
            this.dailyGuardCounts = {};

            if (!this.start_date || !this.end_date) return;

            let start = new Date(this.start_date + 'T00:00:00');
            let end = new Date(this.end_date + 'T00:00:00');

            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const dateKey = `${year}-${month}-${day}`;

    this.days.push(dateKey);
    this.dailyGuardCounts[dateKey] = previousCounts[dateKey] ?? 0;
}
        },

        async previewImport(event) {
            const file = event.target.files?.[0];
            if (!file) return;

            this.importMessage = 'Reading Excel...';
            this.importError = false;

            const formData = new FormData();
            formData.append('import_file', file);

            try {
                const response = await fetch('{{ route('events.importPreview') }}', {
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

                this.start_date = data.start_date || '';
                this.end_date = data.end_date || '';

                this.generateDays();

                const importedCounts = data.daily_guards || {};
                for (const day in this.dailyGuardCounts) {
                    this.dailyGuardCounts[day] = importedCounts[day] ?? 0;
                }
                
                
                                this.shift_mode = data.shift_mode || 'same';
                this.globalImportedStart = data.global_start || '';
                this.globalImportedEnd = data.global_end || '';

                this.shiftModeMessage = this.shift_mode === 'same'
                    ? `Excel detected same shift for all rows (${this.globalImportedStart || '--'} → ${this.globalImportedEnd || '--'}).`
                    : 'Excel detected different shifts across rows.';
                    
                    

                this.importMessage = `Excel loaded successfully. ${data.row_count} row(s) detected.`;
                this.importError = false;
            } catch (error) {
                console.error(error);
                this.importMessage = error.message || 'Failed to read Excel file.';
                this.importError = true;
            }
        },

        toggleGuard(id, name, license) {
            const i = this.selectedGuards.findIndex(g => g.id === id);
            i === -1
                ? this.selectedGuards.push({id, name, license})
                : this.selectedGuards.splice(i, 1);
        },

        isSelected(id) {
            return this.selectedGuards.some(g => g.id === id);
        },

        matches(name, license) {
            if (!this.search) return true;
            const s = this.search.toLowerCase();
            return name.toLowerCase().includes(s) ||
                   license.toLowerCase().includes(s);
        }
    }
}
</script>




@endsection
