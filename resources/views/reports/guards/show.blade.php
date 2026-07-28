@extends('layouts.default')

@section('title', 'Guard Event Report')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Guard Event Report</h1>
            <p class="text-sm text-gray-500">
                Payroll-style report (internal)
            </p>
        </div>

        <div class="text-right">
            <div class="text-xs text-gray-500">Pay Rate (Event)</div>
            <div class="text-base font-semibold text-gray-900 tabular-nums">
                {{ number_format($payRate, 2) }}
            </div>
        </div>
    </div>

    <!-- Meta -->
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <div class="text-xs text-gray-500">Guard</div>
                <div class="font-semibold text-gray-900">{{ $guard->fullname }}</div>
            </div>

            <div>
                <div class="text-xs text-gray-500">Event</div>
                <div class="font-semibold text-gray-900">{{ $event->event_name }}</div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $event->start_date }} → {{ $event->end_date }}
                </div>
            </div>

            <div>
                <div class="text-xs text-gray-500">Totals</div>
                <div class="font-semibold text-gray-900 tabular-nums">
                    {{ number_format($totalHours, 2) }} hrs
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    Total Pay:
                    <span class="font-semibold text-gray-900 tabular-nums">
                        {{ number_format($totalPay, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-sm table-fixed">
            <colgroup>
                <col class="w-4/12">
                <col class="w-2/12">
                <col class="w-3/12">
                <col class="w-3/12">
            </colgroup>

            <thead class="bg-gray-50 border-b border-gray-200 text-gray-700">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold">Date</th>
                    <th class="px-5 py-3 text-center font-semibold">Hours</th>
                    <th class="px-5 py-3 text-center font-semibold">Pay Rate</th>
                    <th class="px-5 py-3 text-center font-semibold">Total Pay</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-900 font-medium">
                            {{ \Carbon\Carbon::parse($r['date'])->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3 text-center tabular-nums text-gray-900">
                            {{ number_format($r['hours'], 2) }}
                        </td>
                        <td class="px-5 py-3 text-center tabular-nums text-gray-700">
                            {{ number_format($r['pay_rate'], 2) }}
                        </td>
                        <td class="px-5 py-3 text-center tabular-nums font-semibold text-gray-900">
                            {{ number_format($r['total_pay'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-gray-500">
                            No shifts found for this guard in this event.
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <!-- Footer Totals (aligned, authoritative) -->
            <tfoot>
                <tr class="bg-gray-900 text-white">
                    <td class="px-5 py-4 text-right font-semibold">
                        Event Total
                    </td>
                    <td class="px-5 py-4 text-center tabular-nums font-semibold">
                        {{ number_format($totalHours, 2) }}
                    </td>
                    <td class="px-5 py-4 text-center tabular-nums text-gray-200">
                        {{ number_format($payRate, 2) }}
                    </td>
                    <td class="px-5 py-4 text-center tabular-nums font-bold">
                        {{ number_format($totalPay, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('reports.guards.index') }}"
           class="text-emerald-600 hover:underline text-sm">
            ← Back to Guard Reports
        </a>

        <a href="{{ route('reports.home') }}"
           class="text-gray-600 hover:underline text-sm">
            Reports Home
        </a>
    </div>

</div>
@endsection
