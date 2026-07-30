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
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white" x-data="{ open: null }">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold">Event</th>
                    <th class="px-3 py-2 text-left font-semibold">Client</th>
                    <th class="px-3 py-2 text-right font-semibold">Hours</th>
                    <th class="px-3 py-2 text-right font-semibold">Charge</th>
                    <th class="px-3 py-2 text-right font-semibold">Pay</th>
                    <th class="px-3 py-2 text-right font-semibold">Expenses</th>
                    <th class="px-3 py-2 text-right font-semibold">Profit</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($d['events'] as $ev)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $ev['event_name'] }}</td>
                        <td class="px-3 py-2">{{ $ev['client_name'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($ev['hours'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            {{ number_format($ev['charge'], 2) }}
                            @if($ev['charge_rate'] === null)
                                <span class="ml-1 text-[10px] text-amber-600">rate not set</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($ev['pay'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($ev['expense_total'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-semibold {{ $ev['profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($ev['profit'], 2) }}</td>
                        <td class="px-3 py-2 text-right">
                            <button type="button"
                                    class="text-xs font-medium text-gray-500 hover:text-gray-900"
                                    @click="open === {{ $ev['id'] }} ? open = null : open = {{ $ev['id'] }}"
                                    x-text="open === {{ $ev['id'] }} ? 'Close' : 'Manage'"></button>
                        </td>
                    </tr>
                    <tr x-show="open === {{ $ev['id'] }}" x-cloak>
                        <td colspan="8" class="bg-gray-50 px-3 py-3">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {{-- Charge rate --}}
                                <div>
                                    <div class="mb-1 text-xs font-semibold text-gray-700">Charge rate (per hour)</div>
                                    <form method="POST" action="{{ route('executive.chargeRate', $ev['id']) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="date_from" value="{{ $from }}">
                                        <input type="hidden" name="date_to" value="{{ $to }}">
                                        <input type="hidden" name="client_id" value="{{ $clientId }}">
                                        <input type="number" step="0.01" min="0" name="charge_rate" value="{{ $ev['charge_rate'] }}"
                                               placeholder="Not set" class="w-32 rounded border border-gray-300 px-2 py-1">
                                        <button class="rounded bg-gray-900 px-3 py-1 text-xs font-semibold text-white hover:bg-black">Save</button>
                                    </form>
                                    <p class="mt-1 text-xs text-gray-400">Writes to the event's charge rate (affects invoicing).</p>
                                </div>

                                {{-- Expenses --}}
                                <div>
                                    <div class="mb-1 text-xs font-semibold text-gray-700">Expenses (in range)</div>
                                    @forelse($ev['expenses'] as $exp)
                                        <div class="flex items-center justify-between gap-2 border-b border-gray-100 py-1 text-xs">
                                            <div>
                                                <span class="font-medium">{{ $exp->label }}</span>
                                                <span class="text-gray-500">{{ number_format((float) $exp->amount, 2) }}</span>
                                                @if($exp->date)<span class="text-gray-400">&middot; {{ $exp->date->format('d/m/Y') }}</span>@endif
                                                @if($exp->note)<div class="text-gray-400">{{ $exp->note }}</div>@endif
                                            </div>
                                            <form method="POST" action="{{ route('executive.expenses.destroy', $exp->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="date_from" value="{{ $from }}">
                                                <input type="hidden" name="date_to" value="{{ $to }}">
                                                <input type="hidden" name="client_id" value="{{ $clientId }}">
                                                <button class="text-red-500 hover:text-red-700">Remove</button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="text-xs text-gray-400">No expenses in range.</p>
                                    @endforelse

                                    <form method="POST" action="{{ route('executive.expenses.store', $ev['id']) }}" class="mt-2 grid grid-cols-2 gap-2">
                                        @csrf
                                        <input type="hidden" name="date_from" value="{{ $from }}">
                                        <input type="hidden" name="date_to" value="{{ $to }}">
                                        <input type="hidden" name="client_id" value="{{ $clientId }}">
                                        <input type="text" name="label" placeholder="Label" required class="rounded border border-gray-300 px-2 py-1 text-xs">
                                        <input type="number" step="0.01" min="0" name="amount" placeholder="Amount" required class="rounded border border-gray-300 px-2 py-1 text-xs">
                                        <input type="date" name="date" class="rounded border border-gray-300 px-2 py-1 text-xs">
                                        <input type="text" name="note" placeholder="Note (optional)" class="rounded border border-gray-300 px-2 py-1 text-xs">
                                        <button class="col-span-2 rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700">Add expense</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">No events with shifts in this range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
