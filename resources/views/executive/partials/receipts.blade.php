{{-- V3-P7: received / remaining profit + credits.
     Received is entered by hand (Super Admin only — the whole executive
     group is behind can:view-executive). Remaining is computed.
     Received and Remaining follow the date range; Credits are all-time
     up to the range end and deliberately ignore the range start. --}}
<div class="mb-10">
    <h2 class="mb-3 text-lg font-semibold text-gray-900">Profit Received &amp; Credits</h2>

    <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500">Profit (range)</div>
            <div class="text-xl font-semibold text-gray-900">{{ number_format($r['totals']['profit'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500">Received Profit (range)</div>
            <div class="text-xl font-semibold text-emerald-700">{{ number_format($r['totals']['received'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500">Remaining Profit (range)</div>
            <div class="text-xl font-semibold {{ $r['totals']['remaining'] >= 0 ? 'text-gray-900' : 'text-red-700' }}">
                {{ number_format($r['totals']['remaining'], 2) }}
            </div>
        </div>
        <div class="rounded-xl border-2 border-blue-300 bg-blue-50 p-4">
            <div class="text-xs text-gray-500">Credits (all-time)</div>
            <div class="text-xl font-bold {{ $r['totals']['credits'] >= 0 ? 'text-blue-800' : 'text-red-700' }}">
                {{ number_format($r['totals']['credits'], 2) }}
            </div>
            <div class="mt-0.5 text-[10px] text-gray-500">All profit minus all receipts, up to {{ $to }}</div>
        </div>
    </div>

    {{-- Client-wise breakdown --}}
    <div class="mb-4 overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold">Client</th>
                    <th class="px-3 py-2 text-right font-semibold">Profit (range)</th>
                    <th class="px-3 py-2 text-right font-semibold">Received (range)</th>
                    <th class="px-3 py-2 text-right font-semibold">Remaining (range)</th>
                    <th class="px-3 py-2 text-right font-semibold">Credits (all-time)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($r['clients'] as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $row['client_name'] }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row['profit'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-emerald-700">{{ number_format($row['received'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums {{ $row['remaining'] >= 0 ? '' : 'text-red-700' }}">{{ number_format($row['remaining'], 2) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-semibold {{ $row['credits'] >= 0 ? 'text-blue-800' : 'text-red-700' }}">{{ number_format($row['credits'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">Nothing to show for this range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Record a receipt --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="mb-2 text-xs font-semibold text-gray-700">Record profit received</div>
            <form method="POST" action="{{ route('executive.receipts.store') }}" class="grid grid-cols-2 gap-2">
                @csrf
                <input type="hidden" name="date_from" value="{{ $from }}">
                <input type="hidden" name="date_to" value="{{ $to }}">
                <input type="hidden" name="client_id" value="{{ $clientId }}">
                <input type="hidden" name="group" value="{{ $group ?? 'event' }}">

                <select name="receipt_client_id" required class="col-span-2 rounded border border-gray-300 px-2 py-1 text-xs">
                    <option value="">&mdash; select client &mdash;</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>

                <input type="number" step="0.01" min="0" name="amount" placeholder="Amount" required
                       class="rounded border border-gray-300 px-2 py-1 text-xs">
                <input type="date" name="received_on" value="{{ $to }}" required
                       class="rounded border border-gray-300 px-2 py-1 text-xs">
                <input type="text" name="note" placeholder="Note (optional)"
                       class="col-span-2 rounded border border-gray-300 px-2 py-1 text-xs">

                <button class="col-span-2 rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700">
                    Record receipt
                </button>
            </form>
            <p class="mt-2 text-[11px] text-gray-400">
                The date decides which range this receipt counts in. Credits always count every receipt up to the range end.
            </p>
        </div>

        {{-- Receipts in range --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="mb-2 text-xs font-semibold text-gray-700">Receipts in range</div>
            @forelse($r['entries'] as $entry)
                <div class="flex items-center justify-between gap-2 border-b border-gray-100 py-1 text-xs">
                    <div>
                        <span class="font-medium">{{ $entry->client?->name ?? '-' }}</span>
                        <span class="text-gray-700">{{ number_format((float) $entry->amount, 2) }}</span>
                        <span class="text-gray-400">&middot; {{ optional($entry->received_on)->format('d/m/Y') }}</span>
                        @if($entry->note)<div class="text-gray-400">{{ $entry->note }}</div>@endif
                    </div>
                    <form method="POST" action="{{ route('executive.receipts.destroy', $entry->id) }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="date_from" value="{{ $from }}">
                        <input type="hidden" name="date_to" value="{{ $to }}">
                        <input type="hidden" name="client_id" value="{{ $clientId }}">
                        <input type="hidden" name="group" value="{{ $group ?? 'event' }}">
                        <button class="text-red-500 hover:text-red-700">Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-xs text-gray-400">No receipts recorded in this range.</p>
            @endforelse
        </div>
    </div>
</div>
