{{-- V4-P3: company-wide general expenses + Net Profit.
     General expenses belong to no event or client, so they only affect the
     Net Profit line here. Client rows and Received / Remaining / Credits
     are unchanged. Counted by date within the selected range. --}}
@php $net = round($profit - $g['total'], 2); @endphp
<div class="mb-10">
    <h2 class="mb-3 text-lg font-semibold text-gray-900">General Expenses &amp; Net Profit</h2>

    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500">Profit (range)</div>
            <div class="text-xl font-semibold text-gray-900">{{ number_format($profit, 2) }}</div>
            <div class="mt-0.5 text-[10px] text-gray-400">charge &minus; pay &minus; event expenses</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500">General Expenses (range)</div>
            <div class="text-xl font-semibold text-amber-700">{{ number_format($g['total'], 2) }}</div>
        </div>
        <div class="rounded-xl border-2 border-gray-800 bg-gray-50 p-4">
            <div class="text-xs text-gray-500">Net Profit (range)</div>
            <div class="text-xl font-bold {{ $net >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($net, 2) }}</div>
            <div class="mt-0.5 text-[10px] text-gray-500">Profit &minus; general expenses</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="mb-2 text-xs font-semibold text-gray-700">Add general expense</div>
            <form method="POST" action="{{ route('executive.generalExpenses.store') }}" class="grid grid-cols-2 gap-2">
                @csrf
                <input type="hidden" name="date_from" value="{{ $from }}">
                <input type="hidden" name="date_to" value="{{ $to }}">
                <input type="hidden" name="client_id" value="{{ $clientId }}">
                <input type="hidden" name="group" value="{{ $group ?? 'event' }}">

                <input type="text" name="label" placeholder="Label (e.g. Office rent)" required maxlength="255"
                       class="col-span-2 rounded border border-gray-300 px-2 py-1 text-xs">
                <input type="number" step="0.01" min="0" name="amount" placeholder="Amount" required
                       class="rounded border border-gray-300 px-2 py-1 text-xs">
                <input type="date" name="date" value="{{ $to }}" required
                       class="rounded border border-gray-300 px-2 py-1 text-xs">
                <input type="text" name="note" placeholder="Note (optional)"
                       class="col-span-2 rounded border border-gray-300 px-2 py-1 text-xs">

                <button class="col-span-2 rounded bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700">
                    Add expense
                </button>
            </form>
            <p class="mt-2 text-[11px] text-gray-400">
                The date decides which range the expense counts in.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="mb-2 text-xs font-semibold text-gray-700">General expenses in range</div>
            @forelse($g['entries'] as $entry)
                <div class="flex items-center justify-between gap-2 border-b border-gray-100 py-1 text-xs">
                    <div>
                        <span class="font-medium">{{ $entry->label }}</span>
                        <span class="text-gray-700">{{ number_format((float) $entry->amount, 2) }}</span>
                        <span class="text-gray-400">&middot; {{ optional($entry->date)->format('d/m/Y') }}</span>
                        @if($entry->note)<div class="text-gray-400">{{ $entry->note }}</div>@endif
                    </div>
                    <form method="POST" action="{{ route('executive.generalExpenses.destroy', $entry->id) }}">
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
                <p class="text-xs text-gray-400">No general expenses in this range.</p>
            @endforelse
        </div>
    </div>
</div>
