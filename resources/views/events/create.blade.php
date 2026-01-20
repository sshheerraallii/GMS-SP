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

    <form action="{{ route('events.store') }}" method="POST" class="space-y-5">
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
        </div>

        {{-- CHARGE RATE --}}
        <div>
            <label class="block font-medium mb-1">Charge Rate / Hour</label>
            <input type="number" step="0.01" name="charge_rate"
                   value="{{ old('charge_rate') }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- INVOICE DATE --}}
        <div>
            <label class="block font-medium mb-1">Invoice Date</label>
            <input type="date" name="invoice_date"
                   value="{{ old('invoice_date') }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- PAY RATE --}}
        <div>
            <label class="block font-medium mb-1">Pay Rate / Hour</label>
            <input type="number" step="0.01" name="pay_rate"
                   value="{{ old('pay_rate') }}"
                   class="w-full border rounded px-3 py-2">
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
                    <input type="radio" name="shift_mode" value="same" checked>
                    <span>Same shift for all guards</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="shift_mode" value="different">
                    <span>Different shifts for each guard</span>
                </label>
            </div>
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
                               class="border rounded px-2 py-1 w-24"
                               value="0">
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

        init() {
            this.$watch('start_date', () => this.generateDays());
            this.$watch('end_date', () => this.generateDays());
        },

        generateDays() {
            this.days = [];
            if (!this.start_date || !this.end_date) return;

            let start = new Date(this.start_date);
            let end = new Date(this.end_date);

            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                this.days.push(d.toISOString().slice(0, 10));
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
