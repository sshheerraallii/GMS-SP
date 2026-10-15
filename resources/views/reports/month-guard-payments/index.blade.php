@extends('layouts.default')

@section('title', 'Month Wise Guard Payment Report')

@section('content')
@php $cols = $showContact ? 7 : 6; @endphp
<div class="max-w-7xl mx-auto mt-6 space-y-4"
     x-data="{ open: null, q: '' }">

    <div>
        <h1 class="text-xl font-semibold text-gray-900">Month Wise Guard Payment Report</h1>
        <p class="text-sm text-gray-500">
            Every guard who worked in the date range, A to Z. Click a guard to see events and shifts.
            Hours, amount and expense count only shifts inside the range. Cancelled shifts are excluded.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('reports.monthGuardPayments.index') }}"
          class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">Date from</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" required
                   class="rounded border border-gray-300 px-2 py-1.5 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">Date to</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" required
                   class="rounded border border-gray-300 px-2 py-1.5 text-sm">
        </div>
        <button class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Show</button>
        <div class="ml-auto">
            <label class="mb-1 block text-xs font-medium text-gray-600">Find guard on this page</label>
            <input type="text" x-model="q" placeholder="Name…" class="rounded border border-gray-300 px-2 py-1.5 text-sm">
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold">Guard</th>
                    @if($showContact)<th class="px-3 py-2 text-left font-semibold">Contact #</th>@endif
                    <th class="px-3 py-2 text-right font-semibold">Total Hours</th>
                    <th class="px-3 py-2 text-right font-semibold">Amount</th>
                    <th class="px-3 py-2 text-right font-semibold">Expense</th>
                    <th class="px-3 py-2 text-left font-semibold">Paid On</th>
                    <th class="px-3 py-2 text-left font-semibold">Paid From</th>
                </tr>
            </thead>

            @forelse($rows as $r)
                <tbody class="border-t border-gray-100"
                       x-show="q === '' || {{ json_encode(mb_strtolower($r['name'])) }}.includes(q.toLowerCase())">
                    <tr class="cursor-pointer hover:bg-gray-50"
                        @click="open = (open === {{ $r['guard_id'] }}) ? null : {{ $r['guard_id'] }}">
                        <td class="px-3 py-2 font-medium text-gray-900">
                            <span class="mr-1 inline-block w-3 text-gray-400" x-text="open === {{ $r['guard_id'] }} ? '▾' : '▸'"></span>{{ $r['name'] }}
                            <span class="ml-1 text-xs font-normal text-gray-400">{{ count($r['events']) }} {{ \Illuminate\Support\Str::plural('event', count($r['events'])) }}</span>
                        </td>
                        @if($showContact)<td class="px-3 py-2">{{ $r['phone'] ?: '—' }}</td>@endif
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($r['hours'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-semibold">{{ number_format($r['amount'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($r['expense'], 2) }}</td>
                        <td class="px-3 py-2">{{ $r['paid_on'] ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $r['paid_from'] ?: '—' }}</td>
                    </tr>

                    <tr x-show="open === {{ $r['guard_id'] }}" x-cloak>
                        <td colspan="{{ $cols }}" class="bg-gray-50 px-3 py-3">
                            <div class="space-y-4">
                                @foreach($r['events'] as $ev)
                                    <div class="rounded-lg border border-gray-200 bg-white p-3">
                                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                            <div class="text-sm font-semibold text-gray-800">
                                                {{ $ev['event_name'] }}
                                                <span class="ml-1 text-xs font-normal text-gray-500">{{ $ev['client_name'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-3 text-xs text-gray-600">
                                                <span>Hours <span class="font-semibold tabular-nums">{{ number_format($ev['hours'], 2) }}</span></span>
                                                <span>Amount <span class="font-semibold tabular-nums">{{ number_format($ev['amount'], 2) }}</span></span>
                                                <span>Expense <span class="font-semibold tabular-nums">{{ number_format($ev['expense'], 2) }}</span></span>
                                            </div>
                                        </div>

                                        <div class="overflow-x-auto rounded border border-gray-100">
                                            <table class="min-w-full text-xs">
                                                <thead class="bg-gray-50 text-gray-500">
                                                    <tr>
                                                        <th class="px-2 py-1 text-left">Date</th>
                                                        <th class="px-2 py-1 text-left">Start</th>
                                                        <th class="px-2 py-1 text-left">End</th>
                                                        <th class="px-2 py-1 text-right">Break</th>
                                                        <th class="px-2 py-1 text-right">Hours</th>
                                                        <th class="px-2 py-1 text-right">Rate</th>
                                                        <th class="px-2 py-1 text-right">Amount</th>
                                                        <th class="px-2 py-1 text-right">Expense</th>
                                                        <th class="px-2 py-1 text-left">Site</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach($ev['rows'] as $row)
                                                        <tr>
                                                            <td class="px-2 py-1">{{ $row['date'] }}</td>
                                                            <td class="px-2 py-1">{{ $row['start'] }}</td>
                                                            <td class="px-2 py-1">{{ $row['end'] }}</td>
                                                            <td class="px-2 py-1 text-right tabular-nums">{{ number_format($row['break'], 2) }}</td>
                                                            <td class="px-2 py-1 text-right tabular-nums">{{ number_format($row['hours'], 2) }}</td>
                                                            <td class="px-2 py-1 text-right tabular-nums">{{ number_format($row['rate'], 2) }}</td>
                                                            <td class="px-2 py-1 text-right tabular-nums">{{ number_format($row['amount'], 2) }}</td>
                                                            <td class="px-2 py-1 text-right tabular-nums">{{ number_format($row['expense'], 2) }}</td>
                                                            <td class="px-2 py-1 text-gray-500">{{ $row['location'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-3 rounded border border-gray-200 bg-gray-50 p-3">
                                            <div class="mb-2 text-xs font-semibold text-gray-700">Payment</div>
                                            @if($canEdit)
                                                <form method="POST" action="{{ route('reports.guardPayments.save') }}"
                                                      class="grid grid-cols-1 gap-2 md:grid-cols-5">
                                                    @csrf
                                                    <input type="hidden" name="event_id" value="{{ $ev['event_id'] }}">
                                                    <input type="hidden" name="guard_id" value="{{ $r['guard_id'] }}">
                                                    <div>
                                                        <label class="mb-0.5 block text-[10px] uppercase text-gray-400">Paid by</label>
                                                        <input type="text" name="paid_by" value="{{ $ev['payment']['paid_by'] }}"
                                                               class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                                    </div>
                                                    <div>
                                                        <label class="mb-0.5 block text-[10px] uppercase text-gray-400">Paid from account</label>
                                                        <input type="text" name="paid_from_account" value="{{ $ev['payment']['paid_from_account'] }}"
                                                               class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                                    </div>
                                                    <div>
                                                        <label class="mb-0.5 block text-[10px] uppercase text-gray-400">Paid on</label>
                                                        <input type="date" name="paid_on" value="{{ $ev['payment']['paid_on'] }}"
                                                               class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                                    </div>
                                                    <div>
                                                        <label class="mb-0.5 block text-[10px] uppercase text-gray-400">Remarks</label>
                                                        <input type="text" name="remarks" value="{{ $ev['payment']['remarks'] }}"
                                                               class="w-full rounded border border-gray-300 px-2 py-1 text-xs">
                                                    </div>
                                                    <div class="flex items-end">
                                                        <button class="w-full rounded bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-black">Save</button>
                                                    </div>
                                                </form>
                                                <p class="mt-1 text-[10px] text-gray-400">
                                                    Payment covers this guard's whole event, not just this date range. Setting a paid date marks the guard's invoice for the event as paid.
                                                </p>
                                            @else
                                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-700 md:grid-cols-4">
                                                    <div><span class="text-gray-400">Paid by:</span> {{ $ev['payment']['paid_by'] ?: '—' }}</div>
                                                    <div><span class="text-gray-400">Account:</span> {{ $ev['payment']['paid_from_account'] ?: '—' }}</div>
                                                    <div><span class="text-gray-400">Paid on:</span> {{ $ev['payment']['paid_on'] ? \Carbon\Carbon::parse($ev['payment']['paid_on'])->format('d/m/Y') : '—' }}</div>
                                                    <div><span class="text-gray-400">Remarks:</span> {{ $ev['payment']['remarks'] ?: '—' }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                </tbody>
            @empty
                <tbody>
                    <tr>
                        <td colspan="{{ $cols }}" class="px-3 py-6 text-center text-gray-500">No guards worked in this date range.</td>
                    </tr>
                </tbody>
            @endforelse

            @if(count($rows))
                <tfoot class="border-t-2 border-gray-300 bg-gray-50 font-semibold text-gray-800">
                    <tr>
                        <td class="px-3 py-2 text-right" colspan="{{ $showContact ? 2 : 1 }}">Totals ({{ count($rows) }} guards)</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totals['hours'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totals['amount'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totals['expense'], 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
