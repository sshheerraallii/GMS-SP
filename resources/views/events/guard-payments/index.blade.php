@extends('layouts.default')

@section('title', 'Guard Pay & Payments')

@section('content')
<div class="max-w-7xl mx-auto mt-6 space-y-4">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Guard Pay &amp; Payments</h1>
            <p class="text-sm text-gray-500">{{ $event->event_name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('events.guardPayments.export', $event->id) }}"
               class="rounded bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Download Excel
            </a>
            <a href="{{ route('events.show', $event->id) }}"
               class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Back to Event
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('events.guardPayments.save', $event->id) }}">
        @csrf

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Guard Name</th>
                        <th class="px-3 py-2 text-right font-semibold">Total Hours</th>
                        <th class="px-3 py-2 text-right font-semibold">Pay Rate</th>
                        <th class="px-3 py-2 text-right font-semibold">Amount</th>
                        <th class="px-3 py-2 text-left font-semibold">Sort Code</th>
                        <th class="px-3 py-2 text-left font-semibold">Account Number</th>
                        <th class="px-3 py-2 text-left font-semibold">Beneficiary Name</th>
                        <th class="px-3 py-2 text-left font-semibold">Paid By</th>
                        <th class="px-3 py-2 text-left font-semibold">Paid On</th>
                        <th class="px-3 py-2 text-left font-semibold">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-medium text-gray-900">
                                {{ $row['guard_name'] }}
                                @if($row['category'])
                                    <span class="ml-1 text-[10px] uppercase text-gray-400">{{ $row['category'] }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row['total_hours'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row['pay_rate'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums font-semibold">{{ number_format($row['amount'], 2) }}</td>
                            <td class="px-3 py-2">{{ $row['sort_code'] }}</td>
                            <td class="px-3 py-2">{{ $row['account_number'] }}</td>
                            <td class="px-3 py-2">{{ $row['beneficiary_name'] }}</td>

                            @if($canEdit)
                                <td class="px-3 py-2">
                                    <input type="text" name="rows[{{ $row['guard_id'] }}][paid_by]"
                                           value="{{ $row['paid_by'] }}"
                                           class="w-32 rounded border border-gray-300 px-2 py-1">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="date" name="rows[{{ $row['guard_id'] }}][paid_on]"
                                           value="{{ $row['paid_on'] }}"
                                           class="rounded border border-gray-300 px-2 py-1">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="rows[{{ $row['guard_id'] }}][remarks]"
                                           value="{{ $row['remarks'] }}"
                                           class="w-48 rounded border border-gray-300 px-2 py-1">
                                </td>
                            @else
                                <td class="px-3 py-2">{{ $row['paid_by'] }}</td>
                                <td class="px-3 py-2">{{ $row['paid_on'] }}</td>
                                <td class="px-3 py-2">{{ $row['remarks'] }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-3 py-6 text-center text-gray-500">
                                No guards with shifts on this event.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                    <tfoot class="bg-gray-50 font-semibold text-gray-800">
                        <tr>
                            <td class="px-3 py-2 text-right">Totals</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totals['hours'], 2) }}</td>
                            <td></td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totals['amount'], 2) }}</td>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($canEdit)
            <div class="mt-3 flex items-center gap-3">
                <button class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                    Save Payment Details
                </button>
                <p class="text-xs text-gray-500">
                    Setting a "Paid On" date marks that guard's invoice for this event as paid.
                </p>
            </div>
        @endif
    </form>
</div>
@endsection
