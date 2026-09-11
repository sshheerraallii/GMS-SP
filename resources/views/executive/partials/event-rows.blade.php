{{-- V3-P6: one event's row + its Manage panel.
     Shared by the event-wise and client-wise views so the two render
     identical markup. $rowShow / $panelShow are optional Alpine
     expressions used when the rows are nested inside a client group. --}}
@php
    $rowShow   = $rowShow   ?? null;
    $panelShow = $panelShow ?? ('open === ' . $ev['id']);
@endphp
                    <tr class="hover:bg-gray-50" @if($rowShow) x-show="{{ $rowShow }}" x-cloak @endif>
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
                    <tr x-show="{{ $panelShow }}" x-cloak>
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
