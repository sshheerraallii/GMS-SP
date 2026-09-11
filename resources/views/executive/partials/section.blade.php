@php $d = $data; @endphp
<div class="mb-10">
    <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ $title }}</h2>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500">Total Hours</div>
            <div class="text-xl font-semibold text-gray-900">{{ number_format($d['totals']['hours'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500">Total Charge</div>
            <div class="text-xl font-semibold text-gray-900">{{ number_format($d['totals']['charge'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500">Total Pay</div>
            <div class="text-xl font-semibold text-gray-900">{{ number_format($d['totals']['pay'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500">Expenses</div>
            <div class="text-xl font-semibold text-gray-900">{{ number_format($d['totals']['expenses'], 2) }}</div>
        </div>
        <div class="rounded-xl border-2 p-4 {{ $d['totals']['profit'] >= 0 ? 'border-emerald-300 bg-emerald-50' : 'border-red-300 bg-red-50' }}">
            <div class="text-xs text-gray-500">Profit</div>
            <div class="text-xl font-bold {{ $d['totals']['profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($d['totals']['profit'], 2) }}</div>
        </div>
    </div>

    {{-- Per-event table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white" x-data="{ open: null, openClient: null }">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    @if(($group ?? 'event') === 'client')
                        <th class="px-3 py-2 text-left font-semibold" colspan="2">Client / Event</th>
                    @else
                        <th class="px-3 py-2 text-left font-semibold">Event</th>
                        <th class="px-3 py-2 text-left font-semibold">Client</th>
                    @endif
                    <th class="px-3 py-2 text-right font-semibold">Hours</th>
                    <th class="px-3 py-2 text-right font-semibold">Charge</th>
                    <th class="px-3 py-2 text-right font-semibold">Pay</th>
                    <th class="px-3 py-2 text-right font-semibold">Expenses</th>
                    <th class="px-3 py-2 text-right font-semibold">Profit</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @if(($group ?? 'event') === 'client')
                    @forelse($d['clients'] as $cl)
                        <tr class="bg-gray-50/60 hover:bg-gray-100">
                            <td class="px-3 py-2 font-semibold text-gray-900" colspan="2">
                                {{ $cl['client_name'] }}
                                <span class="ml-1 text-xs font-normal text-gray-500">({{ count($cl['events']) }} {{ \Illuminate\Support\Str::plural('event', count($cl['events'])) }})</span>
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($cl['hours'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($cl['charge'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($cl['pay'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($cl['expense_total'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums font-semibold {{ $cl['profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($cl['profit'], 2) }}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button"
                                        class="text-xs font-medium text-gray-500 hover:text-gray-900"
                                        @click="openClient === {{ (int) ($cl['client_id'] ?? 0) }} ? openClient = null : openClient = {{ (int) ($cl['client_id'] ?? 0) }}"
                                        x-text="openClient === {{ (int) ($cl['client_id'] ?? 0) }} ? 'Hide events' : 'Show events'"></button>
                            </td>
                        </tr>

                        @foreach($cl['events'] as $ev)
                            @include('executive.partials.event-rows', [
                                'ev'        => $ev,
                                'rowShow'   => 'openClient === ' . (int) ($cl['client_id'] ?? 0),
                                'panelShow' => 'openClient === ' . (int) ($cl['client_id'] ?? 0) . ' && open === ' . $ev['id'],
                            ])
                        @endforeach
                    @empty
                        <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">No events with shifts in this range.</td></tr>
                    @endforelse
                @else
                    @forelse($d['events'] as $ev)
                        @include('executive.partials.event-rows', ['ev' => $ev])
                    @empty
                        <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">No events with shifts in this range.</td></tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</div>
