@extends('layouts.default')

@section('content')
<div class="container mx-auto p-4 max-w-6xl"
     x-data="eventShiftInlineEditor()">

    {{-- Header --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-gray-900">Event Details</h2>
                <p class="mt-1 text-sm text-gray-600 break-words">{{ $event->event_name }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                        Shift Mode: {{ ucfirst($event->shift_mode ?? '-') }}
                    </span>

                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ ($event->client_type ?? '') === 'VAT' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                        {{ ($event->client_type ?? '') === 'VAT' ? 'VAT' : 'Non VAT' }}
                    </span>
                </div>
            </div>

            <div class="w-full lg:w-auto lg:max-w-[560px]">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                    <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Actions
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @can('shifts.assign')
                            <a href="{{ route('events.guards', $event->id) }}"
                               class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                                Edit Assignments
                            </a>
                        @endcan

                        @can('manage-invoices')
                            <form method="POST" action="{{ route('invoices.generateDraftForEvent', $event) }}" class="inline-block">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    Generate Draft Invoice
                                </button>
                            </form>

                            <a href="{{ route('events.staffSheet', $event->id) }}"
                               class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                Download Staff Sheet
                            </a>

                            <a href="{{ route('events.staffPaymentSheet', $event->id) }}"
                               class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                Download Staff Payment Sheet
                            </a>

                            <a href="{{ route('events.staffPaymentSheetReduced', $event->id) }}"
                               class="inline-flex items-center justify-center rounded-lg border border-blue-600 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition duration-150 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                Payment Sheet (No Bank Details)
                            </a>
                            
                            
                            <a href="{{ route('events.timeSheet', $event->id) }}"
   class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
    Download Time Sheet
</a>

                            <a href="{{ route('events.timeSheetReduced', $event->id) }}"
   class="inline-flex items-center justify-center rounded-lg border border-blue-600 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition duration-150 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-300">
    Time Sheet (No Personal Details)
</a>
                            
                            

                            <form method="POST" action="{{ route('events.guard-invoices.generate', $event->id) }}" class="inline-block">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                    Generate Guard Invoices
                                </button>
                            </form>
                        @endcan

                        @can('events.edit')
                            <a href="{{ route('events.edit', $event->id) }}"
                               class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition duration-150 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                Edit
                            </a>
                        @endcan

                        <a href="{{ route('events.index') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition duration-150 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-200 text-green-800 p-3 mb-6 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Event Summary --}}
    <div class="bg-white shadow rounded mb-6 overflow-hidden">
        <div class="border-b p-4 font-semibold text-lg">
            Event Summary
        </div>

        <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">Event Name</div>
                <div class="font-medium text-gray-900">{{ $event->event_name ?? '-' }}</div>
            </div>

            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">Client</div>
                <div class="font-medium text-gray-900">{{ $event->client->name ?? '-' }}</div>
            </div>

            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">Supplier</div>
                <div class="font-medium text-gray-900">{{ $event->supplier->name ?? '-' }}</div>
            </div>

            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">Start Date</div>
                <div class="font-medium text-gray-900">
                    {{ $event->start_date ? \Carbon\Carbon::parse($event->start_date)->format('d M Y') : '-' }}
                </div>
            </div>

            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">End Date</div>
                <div class="font-medium text-gray-900">
                    {{ $event->end_date ? \Carbon\Carbon::parse($event->end_date)->format('d M Y') : '-' }}
                </div>
            </div>

            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">Invoice Date</div>
                <div class="font-medium text-gray-900">
                    {{ $event->invoice_date ? \Carbon\Carbon::parse($event->invoice_date)->format('d M Y') : '-' }}
                </div>
            </div>

            <div class="bg-gray-50 border rounded p-3 lg:col-span-2">
                <div class="text-gray-500">Address</div>
                <div class="font-medium text-gray-900 break-words">{{ $event->address ?? '-' }}</div>
            </div>

            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">Client Contact</div>
                <div class="font-medium text-gray-900">{{ $event->client_contact ?? '-' }}</div>
            </div>

            @can('view-charge-rate')
            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">Charge Rate</div>
                <div class="font-medium text-gray-900">
                    {{ $event->charge_rate !== null ? $event->charge_rate . ' /hr' : 'Not set' }}
                </div>
            </div>
            @endcan

            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">Pay Rate</div>
                <div class="font-medium text-gray-900">
                    {{ $event->pay_rate !== null ? $event->pay_rate . ' /hr' : '-' }}
                </div>
            </div>

            <div class="bg-gray-50 border rounded p-3 lg:col-span-2">
                <div class="text-gray-500">Payment Terms</div>
                <div class="font-medium text-gray-900">{{ $event->payment_terms ?? '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Instructions --}}
    <div class="bg-white shadow rounded mb-6 overflow-hidden">
        <div class="border-b p-4 font-semibold">
            Instructions
        </div>

        <div class="p-4 text-sm whitespace-pre-line text-gray-800">
            {{ $event->instructions ?: 'No instructions provided.' }}
        </div>
    </div>

    {{-- Guard Invoices --}}
    @php
        $guardInvoices = $event->guardInvoices()
            ->with('securityGuard')
            ->orderByDesc('id')
            ->get();
    @endphp

    <div class="bg-white shadow rounded mb-6 overflow-hidden">
        <div class="border-b p-4 font-semibold flex items-center justify-between">
            <div>
                <div class="text-lg">Guard Invoices</div>
                <div class="text-xs text-gray-500 mt-1">One invoice per guard for this event</div>
            </div>

            <a href="{{ route('guard-invoices.index', ['event_id' => $event->id]) }}"
               class="text-sm text-indigo-700 hover:underline">
                View all
            </a>
        </div>

        <div class="p-4">
            @if($guardInvoices->isEmpty())
                <div class="bg-gray-50 border rounded-lg p-4 text-sm text-gray-600">
                    No guard invoices yet.
                </div>
            @else
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-left px-4 py-3">Guard</th>
                                <th class="text-left px-4 py-3">Invoice #</th>
                                <th class="text-left px-4 py-3">Status</th>
                                <th class="text-right px-4 py-3">Hours</th>
                                <th class="text-right px-4 py-3">Total</th>
                                <th class="text-right px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($guardInvoices as $gi)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        {{ $gi->securityGuard?->fullname ?? $gi->securityGuard?->full_name ?? $gi->securityGuard?->name ?? ('Guard #'.$gi->guard_id) }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $gi->invoice_number }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs
                                            {{ $gi->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $gi->status === 'issued' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $gi->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $gi->status === 'voided' ? 'bg-gray-200 text-gray-800' : '' }}
                                        ">
                                            {{ strtoupper($gi->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float)$gi->total_hours, 2) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float)$gi->total, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('guard-invoices.show', $gi->id) }}"
                                           class="text-indigo-700 hover:underline">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Assignments / Validation (By Date) --}}
    <div class="bg-white shadow rounded overflow-hidden">
        <div class="border-b p-4 font-semibold flex items-center justify-between">
            <span>Guard Assignments (By Date)</span>
            <span class="text-sm text-gray-600">
                {{ collect($dailyGuards)->sum() }} required slot(s)
            </span>
        </div>

        <div class="p-4">
            @if(!empty($dailyGuards))
                <div class="space-y-6">
                    @foreach($dailyGuards as $date => $requiredCount)
                        @php
                            $rows = $assignmentRowsByDate[$date] ?? [];
                            $issues = $assignmentIssuesByDate[$date] ?? [];
                            $hasIssues = !empty($issues);
                        @endphp

                        <div class="border rounded overflow-hidden">
                            <div class="px-4 py-3 flex items-center justify-between {{ $hasIssues ? 'bg-red-50' : 'bg-gray-100' }}">
                                <div>
                                    <div class="font-semibold">
                                        {{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '—' }}
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1">
                                        Required guards: {{ $requiredCount }}
                                    </div>
                                </div>

                                @if((int) $requiredCount > 0)
                                    @if($hasIssues)
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                            Incomplete
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                            Complete
                                        </span>
                                    @endif
                                @endif
                            </div>

                            @if((int) $requiredCount === 0)
                                <div class="p-4 text-sm text-gray-500">
                                    No guards required for this date.
                                </div>
                            @else
                                @if($hasIssues)
                                    <div class="border-t border-b bg-red-50 px-4 py-3">
                                        <div class="text-sm font-semibold text-red-800 mb-2">
                                            Missing items
                                        </div>
                                        <ul class="list-disc pl-5 text-sm text-red-700 space-y-1">
                                            @foreach($issues as $issue)
                                                <li>{{ $issue }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-white">
                                            <tr>
                                                <th class="border-b px-3 py-2 text-left w-20">Slot</th>
                                                <th class="border-b px-3 py-2 text-left">Guard</th>
                                                <th class="border-b px-3 py-2 text-left">License</th>
                                                <th class="border-b px-3 py-2 text-left w-28">Start</th>
                                                <th class="border-b px-3 py-2 text-left w-28">End</th>
                                                <th class="border-b px-3 py-2 text-left w-28">Break</th>
                                                <th class="border-b px-3 py-2 text-left">Location</th>
                                                <th class="border-b px-3 py-2 text-left">Status</th>
                                                <th class="border-b px-3 py-2 text-right w-24">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rows as $row)
                                                <tr class="{{ $row['is_cancelled'] ? 'bg-red-100 hover:bg-red-100' : ($row['is_red'] ? 'bg-red-50 hover:bg-red-50' : 'hover:bg-gray-50') }}">
                                                    <td class="border-b px-3 py-2">{{ $row['slot_no'] }}</td>
                                                    <td class="border-b px-3 py-2">{{ $row['guard'] }}</td>
                                                    <td class="border-b px-3 py-2">{{ $row['license'] }}</td>
                                                    <td class="border-b px-3 py-2">{{ $row['start'] }}</td>
                                                    <td class="border-b px-3 py-2">{{ $row['end'] }}</td>
                                                    <td class="border-b px-3 py-2">{{ $row['break'] }}h</td>
                                                    <td class="border-b px-3 py-2">{{ $row['location'] }}</td>
                                                    
                                                    
                                                    <td class="border-b px-3 py-2">
                                                        @if($row['is_cancelled'])
                                                            <span class="inline-flex items-center rounded-full bg-red-200 px-2 py-1 text-[11px] font-semibold text-red-800">
                                                                Cancelled
                                                            </span>
                                                        @elseif($row['is_red'])
                                                            <div class="flex flex-wrap gap-1">
                                                                @foreach($row['issues'] as $issue)
                                                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-[11px] font-medium text-red-700">
                                                                        {{ $issue }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-[11px] font-medium text-green-700">
                                                                Complete
                                                            </span>
                                                        @endif
                                                    </td>
                                                    
                                                    <td class="border-b px-3 py-2 text-right">
    @can('shifts.assign')
        <div class="flex items-center justify-end gap-2">
            @if($row['guard_id'])
                <form method="POST" action="{{ route('events.toggleCancelShift', $event->id) }}" class="inline-block">
                    @csrf
                    <input type="hidden" name="date" value="{{ $row['date'] }}">
                    <input type="hidden" name="guard_id" value="{{ $row['guard_id'] }}">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow {{ $row['is_cancelled'] ? 'bg-gray-600 hover:bg-gray-700' : 'bg-red-600 hover:bg-red-700' }}">
                        {{ $row['is_cancelled'] ? 'Un-cancel' : 'Cancel' }}
                    </button>
                </form>
            @endif
            @if($row['is_red'])
                <button type="button"
                        class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700"
                        @click='openEditor(@json($row))'>
                    Edit
                </button>
            @elseif(!$row['guard_id'])
                <span class="text-xs text-gray-400">—</span>
            @endif
        </div>
    @endcan
</td>
                                                    
                                                    
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-gray-500 italic text-sm">
                    No guard requirements saved for this event.
                </div>
            @endif
        </div>
    </div>

@can('shifts.assign')
    <div x-cloak
         x-show="modalOpen"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @keydown.escape.window="closeEditor()">

        <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl"
             @click.outside="closeEditor()">

            <div class="border-b px-5 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Edit Missing Assignment</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Updates only this one slot.
                    </p>
                </div>

                <button type="button"
                        class="rounded-lg px-2 py-1 text-gray-500 hover:bg-gray-100"
                        @click="closeEditor()">
                    ✕
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Date</label>
                        <input type="text"
                               class="w-full rounded border-gray-300 bg-gray-100"
                               x-model="form.date"
                               readonly>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Slot</label>
                        <input type="text"
                               class="w-full rounded border-gray-300 bg-gray-100"
                               x-model="form.slot_no"
                               readonly>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Guard</label>
                    <select class="w-full rounded border-gray-300"
                            x-model="form.guard_id">
                        <option value="">Select guard</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">
                                {{ $guard->fullname }}{{ $guard->license_number ? ' — '.$guard->license_number : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Start Time</label>
                        <input type="time"
                               class="w-full rounded border-gray-300"
                               x-model="form.shift1_start">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">End Time</label>
                        <input type="time"
                               class="w-full rounded border-gray-300"
                               x-model="form.shift1_end">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Break Hours</label>
                    <select class="w-full rounded border-gray-300"
                            x-model="form.break_hours">
                        <option value="0">0</option>
                        <option value="0.25">0.25</option>
                        <option value="0.5">0.5</option>
                        <option value="0.75">0.75</option>
                        <option value="1">1</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Location</label>
                    <input type="text"
                           class="w-full rounded border-gray-300"
                           x-model="form.shift1_location"
                           placeholder="Enter location">
                </div>

                <template x-if="errorMessage">
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                         x-text="errorMessage"></div>
                </template>
            </div>

            <div class="border-t px-5 py-4 flex items-center justify-end gap-2">
                <button type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        @click="closeEditor()">
                    Cancel
                </button>

                <button type="button"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                        :disabled="saving"
                        @click="saveEditor()">
                    <span x-show="!saving">Save Row</span>
                    <span x-show="saving">Saving...</span>
                </button>
            </div>
        </div>
    </div>
@endcan



</div>


<script>
    function eventShiftInlineEditor() {
        return {
            modalOpen: false,
            saving: false,
            errorMessage: '',
            form: {
                date: '',
                slot_no: '',
                guard_id: '',
                shift1_start: '',
                shift1_end: '',
                break_hours: '0',
                shift1_location: '',
            },

            openEditor(row) {
                this.errorMessage = '';

                this.form = {
                    date: row.date || '',
                    slot_no: row.slot_no || '',
                    guard_id: row.guard_id ? String(row.guard_id) : '',
                    shift1_start: row.start_value || '',
                    shift1_end: row.end_value || '',
                    break_hours: row.break_value || '0',
                    shift1_location: row.location_value || '',
                };

                this.modalOpen = true;
            },

            closeEditor() {
                if (this.saving) return;
                this.modalOpen = false;
                this.errorMessage = '';
            },

            async saveEditor() {
                this.saving = true;
                this.errorMessage = '';

                try {
                    const response = await fetch(@json(route('events.saveGuardSlot', $event->id)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify(this.form),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const errors = data.errors || {};
                        const firstError = Object.values(errors).flat()[0];

                        throw new Error(firstError || data.message || 'Save failed.');
                    }

                    window.location.reload();
                } catch (error) {
                    this.errorMessage = error.message || 'Save failed.';
                } finally {
                    this.saving = false;
                }
            },
        };
    }
</script>
@endsection