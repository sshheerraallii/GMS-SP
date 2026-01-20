@extends('layouts.default')

@section('content')
<div class="container mx-auto p-4 max-w-6xl">

    {{-- Header --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Event Details</h2>
            <p class="text-sm text-gray-600 mt-1">{{ $event->event_name }}</p>
            <div class="mt-2 inline-flex items-center gap-2">
                <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">
                    Shift Mode: {{ ucfirst($event->shift_mode ?? '-') }}
                </span>
                <span class="text-xs px-2 py-1 rounded {{ ($event->client_type ?? '') === 'VAT' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                    {{ ($event->client_type ?? '') === 'VAT' ? 'VAT' : 'Non VAT' }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('shifts.assign')
                <a href="{{ route('events.guards', $event->id) }}"
                   class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">
                    Edit Assignments
                </a>
            @endcan

            @can('manage-invoices')
                <form method="POST" action="{{ route('invoices.generateDraftForEvent', $event) }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                        Generate Draft Invoice
                    </button>
                </form>

                <form method="POST" action="{{ route('events.guard-invoices.generate', $event->id) }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                        Generate Guard Invoices
                    </button>
                </form>
            @endcan

            @can('events.edit')
                <a href="{{ route('events.edit', $event->id) }}"
                   class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                    Edit
                </a>
            @endcan

            <a href="{{ route('events.index') }}"
               class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Back
            </a>
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

            <div class="bg-gray-50 border rounded p-3">
                <div class="text-gray-500">Charge Rate</div>
                <div class="font-medium text-gray-900">
                    {{ $event->charge_rate !== null ? $event->charge_rate . ' /hr' : '-' }}
                </div>
            </div>

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

    {{-- Guard Invoices (per guard per event) --}}
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

    {{-- Assignments (Date -> Shifts -> Guard) --}}
    @php
        $shiftsByDate = $event->shifts
            ? $event->shifts
                ->sortBy(fn($s) => $s->date . ' ' . ($s->start_time ?? '00:00') . ' ' . ($s->shift_no ?? 0))
                ->groupBy('date')
            : collect();
    @endphp

    <div class="bg-white shadow rounded overflow-hidden">
        <div class="border-b p-4 font-semibold flex items-center justify-between">
            <span>Guard Assignments (By Date)</span>
            <span class="text-sm text-gray-600">
                {{ $event->shifts?->count() ?? 0 }} shift(s)
            </span>
        </div>

        <div class="p-4">
            @if(($event->shifts?->count() ?? 0) > 0)

                <div class="space-y-6">
                    @foreach($shiftsByDate as $date => $shifts)
                        <div class="border rounded overflow-hidden">
                            <div class="bg-gray-100 px-4 py-2 font-semibold flex items-center justify-between">
                                <span>
                                    {{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '—' }}
                                </span>
                                <span class="text-xs text-gray-600">
                                    {{ $shifts->count() }} shift(s)
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full border-t text-sm">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="border-b px-3 py-2 text-left w-20">Shift</th>
                                            <th class="border-b px-3 py-2 text-left">Guard</th>
                                            <th class="border-b px-3 py-2 text-left">License</th>
                                            <th class="border-b px-3 py-2 text-left w-32">Start</th>
                                            <th class="border-b px-3 py-2 text-left w-32">End</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($shifts as $shift)
                                            <tr class="hover:bg-gray-50">
                                                <td class="border-b px-3 py-2">
                                                    {{ $shift->shift_no ?? '-' }}
                                                </td>
                                                <td class="border-b px-3 py-2">
                                                    {{ $shift->securityGuard->fullname ?? '—' }}
                                                </td>
                                                <td class="border-b px-3 py-2">
                                                    {{ $shift->securityGuard->license_number ?? ($shift->securityGuard->cin ?? '—') }}
                                                </td>
                                                <td class="border-b px-3 py-2">
                                                    {{ $shift->start_time ?? '—' }}
                                                </td>
                                                <td class="border-b px-3 py-2">
                                                    {{ $shift->end_time ?? '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

            @else
                <div class="text-gray-500 italic text-sm">
                    No guard shifts saved for this event.
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
