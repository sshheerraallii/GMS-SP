@extends('layouts.default')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-10">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Invoices</h1>
            <p class="text-sm text-gray-500">Client invoices generated from event shifts</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search by Event name or Client name..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200"
            />
        </div>

        <div class="flex items-center gap-3">
            <select name="per_page" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach([10, 20, 50, 100] as $n)
                    <option value="{{ $n }}" @selected((int)request('per_page', 20) === $n)>
                        {{ $n }}/page
                    </option>
                @endforeach
            </select>

            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">
                Search
            </button>

            @if(request('q') || request('per_page'))
                <a href="{{ url()->current() }}" class="text-sm text-gray-600 hover:text-gray-900 hover:underline">
                    Clear
                </a>
            @endif
        </div>
    </form>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-left text-gray-600">
                        <th class="px-5 py-3 font-medium">Invoice #</th>
                        <th class="px-5 py-3 font-medium">Event</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Total</th>
                        <th class="px-5 py-3 font-medium">Issued</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-semibold text-gray-900">
                                {{ $invoice->invoice_number }}
                            </td>

                            <td class="px-5 py-3 text-gray-700">
                                {{ $invoice->event_name_snapshot ?? optional($invoice->event)->event_name ?? '—' }}
                            </td>

                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                    {{ $invoice->status === 'draft' ? 'bg-yellow-50 text-yellow-800 border border-yellow-200' : '' }}
                                    {{ $invoice->status === 'issued' ? 'bg-blue-50 text-blue-800 border border-blue-200' : '' }}
                                    {{ $invoice->status === 'paid' ? 'bg-green-50 text-green-800 border border-green-200' : '' }}
                                    {{ $invoice->status === 'void' ? 'bg-gray-100 text-gray-700 border border-gray-200' : '' }}
                                ">
                                    {{ strtoupper($invoice->status) }}
                                </span>
                            </td>

                            <td class="px-5 py-3 text-gray-900 font-medium">
                                {{ number_format((float)$invoice->total, 2) }}
                            </td>

                            <td class="px-5 py-3 text-gray-600">
                                {{ optional($invoice->issued_at)->format('d M Y H:i') ?? '—' }}
                            </td>

                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                                No invoices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-4 border-t bg-white">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-600">
                    Showing
                    <span class="font-medium">{{ $invoices->firstItem() ?? 0 }}</span>
                    to
                    <span class="font-medium">{{ $invoices->lastItem() ?? 0 }}</span>
                    of
                    <span class="font-medium">{{ $invoices->total() }}</span>
                    results
                    <span class="ml-2 text-gray-500">
                        (Page {{ $invoices->currentPage() }} of {{ $invoices->lastPage() }})
                    </span>
                </div>

                <div>
                    {{ $invoices->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
