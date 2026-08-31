@extends('layouts.default')

@section('title', 'Detailed Event Report')

@section('content')
@php
    // Build Alpine state: default all days OPEN (true)
    $openDays = [];
    foreach ($days as $date => $data) { $openDays[$date] = true; }

    // Charge figures are visible to Super Admin + Accountant only.
    // When hidden, the table collapses cleanly from 4 cols to 2.
    $showCharge    = auth()->user()->can('view-charge-rate');
    $chargeColspan = $showCharge ? 4 : 2;
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Detailed Event Report</h1>
            <p class="text-sm text-gray-500">{{ $event->event_name }}</p>
        </div>

        @if($showCharge)
        <div class="text-right">
            <div class="text-xs text-gray-500">Charge Rate</div>
            <div class="text-base font-semibold text-gray-900">
                {{ $event->charge_rate !== null ? number_format($event->charge_rate, 2) : 'Not set' }}
            </div>
        </div>
        @endif
    </div>

    <!-- Master Table Container -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-sm table-fixed">
            <!-- Column system (never drifts) -->
            <colgroup>
                <col class="{{ $showCharge ? 'w-6/12' : 'w-9/12' }}">
                <col class="{{ $showCharge ? 'w-2/12' : 'w-3/12' }}">
                @if($showCharge)
                <col class="w-2/12">
                <col class="w-2/12">
                @endif
            </colgroup>

            <thead class="bg-gray-50 border-b border-gray-200 text-gray-700">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold">Description</th>
                    <th class="px-5 py-3 text-center font-semibold">Hours</th>
                    @if($showCharge)
                    <th class="px-5 py-3 text-center font-semibold">Rate</th>
                    <th class="px-5 py-3 text-center font-semibold">Total</th>
                    @endif
                </tr>
            </thead>

            <tbody
                x-data='{ openDays: @json($openDays) }'
                class="divide-y divide-gray-100"
            >

                @forelse($days as $date => $data)
                    <!-- Day Header Row (clickable) -->
                    <tr
                        class="bg-white hover:bg-gray-50 cursor-pointer select-none"
                        @click="openDays['{{ $date }}'] = !openDays['{{ $date }}']"
                    >
                        <td colspan="{{ $chargeColspan }}" class="px-5 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gray-900 text-white text-xs font-semibold">
                                        {{ \Carbon\Carbon::parse($date)->format('d') }}
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-900">
                                            {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Click to {{ $openDays[$date] ? 'collapse' : 'expand' }} guards
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500">Day Hours</div>
                                        <div class="font-semibold text-gray-900">
                                            {{ number_format($data['total_hours'], 2) }}
                                        </div>
                                    </div>

                                    @if($showCharge)
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500">Day Total</div>
                                        <div class="font-semibold text-gray-900">
                                            {{ number_format($data['total_charge'], 2) }}
                                        </div>
                                    </div>
                                    @endif

                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 text-gray-700"
                                        x-text="openDays['{{ $date }}'] ? '−' : '+'"
                                    ></span>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Guard Rows (toggle as a block) -->
                    @foreach($data['rows'] as $row)
                        <tr x-show="openDays['{{ $date }}']" x-cloak class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-900">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $row['guard_name'] }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center tabular-nums text-gray-900">
                                {{ number_format($row['hours'], 2) }}
                            </td>
                            @if($showCharge)
                            <td class="px-5 py-3 text-center tabular-nums text-gray-700">
                                {{ number_format((float) $event->charge_rate, 2) }}
                            </td>
                            <td class="px-5 py-3 text-center tabular-nums font-semibold text-gray-900">
                                {{ number_format($row['charge'], 2) }}
                            </td>
                            @endif
                        </tr>
                    @endforeach

                    <!-- Day Subtotal Row (always visible, perfectly aligned) -->
                    <tr class="bg-gray-50 border-t border-gray-200">
                        <td class="px-5 py-3 text-right font-semibold text-gray-900">
                            Day Total
                        </td>
                        <td class="px-5 py-3 text-center tabular-nums font-semibold text-gray-900">
                            {{ number_format($data['total_hours'], 2) }}
                        </td>
                        @if($showCharge)
                        <td class="px-5 py-3"></td>
                        <td class="px-5 py-3 text-center tabular-nums font-semibold text-gray-900">
                            {{ number_format($data['total_charge'], 2) }}
                        </td>
                        @endif
                    </tr>

                @empty
                    <tr>
                        <td colspan="{{ $chargeColspan }}" class="px-5 py-10 text-center text-gray-500">
                            No shift data found for this event.
                        </td>
                    </tr>
                @endforelse

                <!-- Event Total Row (authoritative, aligned, premium) -->
                <tr class="bg-gray-900">
                    <td class="px-5 py-4 text-right font-semibold text-white">
                        Event Total
                    </td>
                    <td class="px-5 py-4 text-center tabular-nums font-semibold text-white">
                        {{ number_format($eventTotalHours, 2) }}
                    </td>
                    @if($showCharge)
                    <td class="px-5 py-4 text-center tabular-nums text-gray-200">
                        {{ number_format((float) $event->charge_rate, 2) }}
                    </td>
                    <td class="px-5 py-4 text-center tabular-nums font-bold text-white">
                        {{ number_format($eventTotalCharge, 2) }}
                    </td>
                    @endif
                </tr>

            </tbody>
        </table>
    </div>

    <!-- Back -->
    <div>
        <a href="{{ route('reports.events.show', $event->id) }}"
           class="text-emerald-600 hover:underline text-sm">
            ← Back to Event Summary
        </a>
    </div>

</div>
@endsection
