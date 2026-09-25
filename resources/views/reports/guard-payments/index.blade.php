@extends('layouts.default')

@section('title', 'Guard Payment Report')

@section('content')
<div class="max-w-7xl mx-auto mt-6 space-y-4">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Guard Payment Report</h1>
            <p class="text-sm text-gray-500">One guard across every client and event, with payment details.</p>
        </div>
        <a href="{{ route('reports.home') }}"
           class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Back to Reports
        </a>
    </div>

    @if(session('success'))
        <div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('reports.guardPayments.index') }}"
          class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs text-gray-600">Guard</label>
                <select name="guard_id" required class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
                    <option value="">&mdash; select guard &mdash;</option>
                    @foreach($guards as $g)
                        <option value="{{ $g->id }}" {{ (string) ($filters['guard_id'] ?? '') === (string) $g->id ? 'selected' : '' }}>
                            {{ $g->fullname }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs text-gray-600">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-xs text-gray-600">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-xs text-gray-600">Group by</label>
                <select name="group" class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
                    <option value="client" {{ $group === 'client' ? 'selected' : '' }}>Client-wise</option>
                    <option value="event"  {{ $group === 'event'  ? 'selected' : '' }}>Event-wise</option>
                </select>
            </div>

            <div class="flex items-end">
                <button class="w-full rounded bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-black">
                    Run report
                </button>
            </div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs text-gray-600">Clients <span class="text-gray-400">(leave empty for all)</span></label>
                <select name="client_ids[]" multiple size="4" class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ in_array($c->id, (array) ($filters['client_ids'] ?? [])) ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-600">Events <span class="text-gray-400">(leave empty for all)</span></label>
                <select name="event_ids[]" multiple size="4" class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
                    @foreach($events as $e)
                        <option value="{{ $e->id }}" {{ in_array($e->id, (array) ($filters['event_ids'] ?? [])) ? 'selected' : '' }}>
                            {{ $e->event_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @if(!$guard)
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
            Select a guard and run the report.
        </div>
    @else
        {{-- SUMMARY + EXPORTS --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4">
            <div class="flex flex-wrap items-center gap-6">
                <div>
                    <div class="text-xs text-gray-500">Guard</div>
                    <div class="font-semibold text-gray-900">{{ $guard->fullname }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Total hours</div>
                    <div class="font-semibold tabular-nums">{{ number_format($totals['hours'], 2) }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Total pay</div>
                    <div class="font-semibold tabular-nums">{{ number_format($totals['amount'], 2) }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Events paid / unpaid</div>
                    <div class="font-semibold tabular-nums">{{ $totals['paid'] }} / {{ $totals['unpaid'] }}</div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('reports.guardPayments.pdf', request()->query()) }}"
                   class="rounded border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    Export PDF
                </a>
                <a href="{{ route('reports.guardPayments.excel', request()->query()) }}"
                   class="rounded bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                    Export Excel
                </a>
            </div>
        </div>
        <p class="text-xs text-gray-400">
            Totals cover the whole filtered range. Exports include every group, not just this page.
        </p>

        {{-- GROUPS --}}
        <div x-data="{ open: null, history: null, entries: [], loading: false }" class="space-y-3">
            @forelse($groups as $grp)
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <button type="button"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-gray-50"
                            @click="open === '{{ $grp['key'] }}' ? open = null : open = '{{ $grp['key'] }}'">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $grp['title'] }}</div>
                            @if($grp['sub'])
                                <div class="text-xs text-gray-500">{{ $grp['sub'] }}</div>
                            @endif
                        </div>
                        <div class="flex items-center gap-6 text-sm">
                            <div class="text-right">
                                <div class="text-[10px] uppercase text-gray-400">Hours</div>
                                <div class="tabular-nums">{{ number_format($grp['hours'], 2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] uppercase text-gray-400">Pay</div>
                                <div class="font-semibold tabular-nums">{{ number_format($grp['amount'], 2) }}</div>
                            </div>
                            <span class="text-gray-400" x-text="open === '{{ $grp['key'] }}' ? '&#9650;' : '&#9660;'"></span>
                        </div>
                    </button>

                    <div x-show="open === '{{ $grp['key'] }}'" x-cloak class="border-t border-gray-100">
                        @foreach($grp['events'] as $ev)
                            <div class="border-b border-gray-100 p-4 last:border-b-0">
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ $ev['event_name'] }}
                                        <span class="ml-1 text-xs font-normal text-gray-500">{{ $ev['client_name'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-gray-600">
                                        <span>Rate <span class="font-semibold tabular-nums">{{ number_format($ev['pay_rate'], 2) }}</span></span>
                                        <span>Hours <span class="font-semibold tabular-nums">{{ number_format($ev['hours'], 2) }}</span></span>
                                        <span>Pay <span class="font-semibold tabular-nums">{{ number_format($ev['amount'], 2) }}</span></span>
                                        @if($ev['invoice_status'])
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 uppercase tracking-wide">{{ $ev['invoice_status'] }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- shifts --}}
                                <div class="overflow-x-auto rounded border border-gray-100">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-gray-50 text-gray-500">
                                            <tr>
                                                <th class="px-2 py-1 text-left">Date</th>
                                                <th class="px-2 py-1 text-left">Start</th>
                                                <th class="px-2 py-1 text-left">End</th>
                                                <th class="px-2 py-1 text-right">Break</th>
                                                <th class="px-2 py-1 text-right">Hours</th>
                                                <th class="px-2 py-1 text-right">Amount</th>
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
                                                    <td class="px-2 py-1 text-right tabular-nums">{{ number_format($row['amount'], 2) }}</td>
                                                    <td class="px-2 py-1 text-gray-500">{{ $row['location'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- payment notes --}}
                                <div class="mt-3 rounded border border-gray-200 bg-gray-50 p-3">
                                    <div class="mb-2 flex items-center justify-between">
                                        <div class="text-xs font-semibold text-gray-700">Payment</div>
                                        @if($canHistory)
                                            <button type="button"
                                                    class="text-[11px] font-medium text-gray-500 underline hover:text-gray-900"
                                                    @click="
                                                        if (history === '{{ $grp['key'] }}-{{ $ev['event_id'] }}') { history = null; return; }
                                                        history = '{{ $grp['key'] }}-{{ $ev['event_id'] }}'; loading = true; entries = [];
                                                        fetch('{{ route('reports.guardPayments.history') }}?event_id={{ $ev['event_id'] }}&guard_id={{ $guard->id }}', { headers: { 'Accept': 'application/json' } })
                                                            .then(r => r.json()).then(d => { entries = d.entries || []; loading = false; })
                                                            .catch(() => { loading = false; });
                                                    ">
                                                Edit history
                                            </button>
                                        @endif
                                    </div>

                                    @if($canEdit)
                                        <form method="POST" action="{{ route('reports.guardPayments.save') }}"
                                              class="grid grid-cols-1 gap-2 md:grid-cols-5">
                                            @csrf
                                            <input type="hidden" name="event_id" value="{{ $ev['event_id'] }}">
                                            <input type="hidden" name="guard_id" value="{{ $guard->id }}">

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
                                                <button class="w-full rounded bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-black">
                                                    Save
                                                </button>
                                            </div>
                                        </form>
                                        <p class="mt-1 text-[10px] text-gray-400">
                                            Setting a paid date marks this guard's invoice for the event as paid.
                                        </p>
                                    @else
                                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-700 md:grid-cols-4">
                                            <div><span class="text-gray-400">Paid by:</span> {{ $ev['payment']['paid_by'] ?: '—' }}</div>
                                            <div><span class="text-gray-400">Account:</span> {{ $ev['payment']['paid_from_account'] ?: '—' }}</div>
                                            <div><span class="text-gray-400">Paid on:</span> {{ $ev['payment']['paid_on'] ?: '—' }}</div>
                                            <div><span class="text-gray-400">Remarks:</span> {{ $ev['payment']['remarks'] ?: '—' }}</div>
                                        </div>
                                    @endif

                                    @if($canHistory)
                                        <div x-show="history === '{{ $grp['key'] }}-{{ $ev['event_id'] }}'" x-cloak
                                             class="mt-3 border-t border-gray-200 pt-2">
                                            <template x-if="loading">
                                                <div class="text-[11px] text-gray-400">Loading history&hellip;</div>
                                            </template>
                                            <template x-if="!loading && entries.length === 0">
                                                <div class="text-[11px] text-gray-400">No edits recorded.</div>
                                            </template>
                                            <template x-for="entry in entries">
                                                <div class="border-b border-gray-100 py-1 text-[11px] last:border-b-0">
                                                    <div class="text-gray-600">
                                                        <span class="font-semibold" x-text="entry.action"></span>
                                                        by <span x-text="entry.user"></span>
                                                        <span class="text-gray-400" x-text="entry.at"></span>
                                                    </div>
                                                    <template x-for="c in entry.changes">
                                                        <div class="ml-2 text-gray-500">
                                                            <span x-text="c.field"></span>:
                                                            <span class="line-through" x-text="c.from"></span>
                                                            &rarr;
                                                            <span class="font-medium text-gray-800" x-text="c.to"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                    No shifts found for this guard with these filters.
                </div>
            @endforelse

            <div>{{ $groups->links() }}</div>
        </div>
    @endif
</div>
@endsection
