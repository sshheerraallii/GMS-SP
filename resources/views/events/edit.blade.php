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

        {{-- CHARGE RATE --}}
        <div>
            <label class="block font-medium mb-1">Charge Rate / Hour</label>
            <input type="number" step="0.01"
                   name="charge_rate"
                   value="{{ old('charge_rate', $event->charge_rate) }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- INVOICE DATE --}}
        <div>
            <label class="block font-medium mb-1">Invoice Date</label>
            <input type="date"
                   name="invoice_date"
                   value="{{ old('invoice_date', $event->invoice_date) }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- PAY RATE --}}
        <div>
            <label class="block font-medium mb-1">Pay Rate / Hour</label>
            <input type="number" step="0.01"
                   name="pay_rate"
                   value="{{ old('pay_rate', $event->pay_rate) }}"
                   class="w-full border rounded px-3 py-2">
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
</div>

<script>
function eventEditForm(eventData, initialDailyGuards, initialShiftMode) {
    return {
        start_date: '',
        end_date: '',
        shift_mode: 'same',

        days: [],
        dailyGuardsByDate: {},

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
    }
}
</script>
@endsection
