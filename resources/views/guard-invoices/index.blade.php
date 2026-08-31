@extends('layouts.default')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">

    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Guard Invoices</h1>
            <p class="text-sm text-gray-600 mt-1">Payables per guard per event</p>
        </div>

        <a href="{{ url()->previous() }}"
           class="px-4 py-2 rounded bg-gray-600 text-white text-sm hover:bg-gray-700">
            Back
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-200 text-green-800 p-3 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search / Filters (minimal) --}}
    <form method="GET" class="bg-white border rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="text-xs text-gray-600">Search</label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    class="w-full mt-1 border rounded px-3 py-2 text-sm"
                    placeholder="Event name, Guard name, Invoice #"
                >
            </div>

            <div>
                <label class="text-xs text-gray-600">Status</label>
                <select name="status" class="w-full mt-1 border rounded px-3 py-2 text-sm">
                    <option value="">All</option>
                    @foreach(['draft','issued','paid','voided'] as $s)
                        <option value="{{ $s }}" {{ request('status')===$s ? 'selected' : '' }}>
                            {{ strtoupper($s) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-600">Per page</label>
                <select name="per_page" class="w-full mt-1 border rounded px-3 py-2 text-sm">
                    @foreach([10, 20, 50, 100] as $n)
                        <option value="{{ $n }}" @selected((int)request('per_page', 20) === $n)>
                            {{ $n }}/page
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex gap-2">
            <button class="px-4 py-2 rounded bg-gray-900 text-white text-sm hover:bg-gray-800" type="submit">
                Apply
            </button>

            <a href="{{ route('guard-invoices.index') }}"
               class="px-4 py-2 rounded bg-gray-100 text-gray-800 text-sm hover:bg-gray-200">
                Reset
            </a>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white border rounded-lg">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-3">Invoice #</th>
                    <th class="text-left px-4 py-3">Event</th>
                    <th class="text-left px-4 py-3">Guard</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-right px-4 py-3">Hours</th>
                    <th class="text-right px-4 py-3">Total</th>
                    <th class="text-right px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guardInvoices as $gi)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $gi->invoice_number }}</td>

                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $gi->event?->event_name ?? ('Event #'.$gi->event_id) }}</div>
                            <div class="text-xs text-gray-500">Event ID: {{ $gi->event_id }}</div>
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">
                                {{ $gi->securityGuard?->fullname ?? $gi->securityGuard?->full_name ?? $gi->securityGuard?->name ?? ('Guard #'.$gi->guard_id) }}
                            </div>
                            <div class="text-xs text-gray-500">Guard ID: {{ $gi->guard_id }}</div>
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs
                                {{ $gi->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $gi->status === 'issued' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $gi->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $gi->status === 'voided' ? 'bg-gray-200 text-gray-800' : '' }}
                            ">
                                {{ strtoupper($gi->status) }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-right">{{ number_format((float)$gi->total_hours, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float)$gi->total, 2) }}</td>

                        <td class="px-4 py-3 text-right">
                            <a class="text-indigo-700 hover:underline"
                               href="{{ route('guard-invoices.show', $gi->id) }}">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="border-t">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            No guard invoices found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-gray-600">
            Showing
            <span class="font-medium">{{ $guardInvoices->firstItem() ?? 0 }}</span>
            to
            <span class="font-medium">{{ $guardInvoices->lastItem() ?? 0 }}</span>
            of
            <span class="font-medium">{{ $guardInvoices->total() }}</span>
            results
            <span class="ml-2 text-gray-500">
                (Page {{ $guardInvoices->currentPage() }} of {{ $guardInvoices->lastPage() }})
            </span>
        </div>

        <div>
            {{ $guardInvoices->onEachSide(1)->links() }}
        </div>
    </div>

</div>
@endsection
