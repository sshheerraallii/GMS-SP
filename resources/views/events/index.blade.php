@extends('layouts.default')

@section('content')
<div class="container mx-auto p-4">

    <div class="flex flex-col gap-3 md:flex-row md:justify-between md:items-center mb-4">
        <h2 class="text-2xl font-bold">Events List</h2>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
            {{-- Rows per page --}}
            <form method="GET" action="{{ route('events.index') }}" class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Rows</label>
                <select name="per_page"
                        onchange="this.form.submit()"
                        class="border rounded px-2 py-2 text-sm">
                    @foreach([10,20,30] as $n)
                        <option value="{{ $n }}" {{ (int)request('per_page', 10) === $n ? 'selected' : '' }}>
                            {{ $n }}
                        </option>
                    @endforeach
                </select>
            </form>

            @can('events.create')
                <a href="{{ route('events.create') }}"
                   class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    + Create New Event
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 2500)"
            x-show="show"
            x-transition
            class="fixed top-5 right-5 z-50 bg-emerald-600 text-white px-4 py-3 rounded shadow-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full border-collapse border border-gray-300 text-sm">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border p-2 text-left">Event</th>
                    <th class="border p-2 text-left">Address</th>
                    <th class="border p-2 text-left">Client</th>
                    <th class="border p-2 text-center">Type</th>
                    <th class="border p-2 text-center">Charge</th>
                    <th class="border p-2 text-center">Invoice Date</th>
                    <th class="border p-2 text-center">Pay Rate</th>
                    <th class="border p-2 text-left">Guards</th>
                    <th class="border p-2 text-left">Client Contact</th>
                    <th class="border p-2 text-center">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($events as $event)
                    <tr class="hover:bg-gray-50">

                        <td class="border p-2 font-medium">
                            {{ $event->event_name }}
                        </td>

                        <td class="border p-2">
                            {{ $event->address }}
                        </td>

                        <td class="border p-2">
                            {{ $event->client->name ?? '-' }}
                        </td>

                        <td class="border p-2 text-center:
                        ">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border">
                                {{ $event->client_type === 'VAT' ? 'VAT' : 'Non VAT' }}
                            </span>
                        </td>

                        <td class="border p-2 text-center">
                            {{ $event->charge_rate }}/hr
                        </td>

                        <td class="border p-2 text-center">
                            {{ $event->invoice_date }}
                        </td>

                        <td class="border p-2 text-center">
                            {{ $event->pay_rate }}/hr
                        </td>

                        {{-- Guards column --}}
                        <td class="border p-2 align-top">
                            @if($event->guards->count())
                                <div class="font-medium">
                                    {{ $event->guards->count() }}
                                    Guard{{ $event->guards->count() > 1 ? 's' : '' }}
                                </div>

                                <div class="text-gray-600 text-xs">
                                    {{ $event->guards->take(2)->pluck('fullname')->join(', ') }}
                                    @if($event->guards->count() > 2)
                                        …
                                    @endif
                                </div>

                                <a href="{{ route('events.show', $event->id) }}"
                                   class="text-blue-600 underline text-xs">
                                    View all
                                </a>
                            @else
                                <span class="text-gray-400 italic">None</span>
                            @endif
                        </td>

                        <td class="border p-2">
                            {{ $event->client_contact }}
                        </td>

                        <td class="border p-2 text-center space-y-1">
                            @can('events.edit')
                                <a href="{{ route('events.edit', $event->id) }}"
                                   class="block bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">
                                    Edit
                                </a>
                            @endcan

                            @can('events.view')
                                <a href="{{ route('events.show', $event->id) }}"
                                   class="block bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                                    View
                                </a>
                            @endcan

                            @can('events.delete')
                                <form action="{{ route('events.destroy', $event->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Delete this event?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="w-full bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                                        Delete
                                    </button>
                                </form>
                            @endcan
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center p-4 text-gray-500">
                            No events found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div class="text-sm text-gray-600">
            Showing
            <span class="font-medium">{{ $events->firstItem() ?? 0 }}</span>
            to
            <span class="font-medium">{{ $events->lastItem() ?? 0 }}</span>
            of
            <span class="font-medium">{{ $events->total() }}</span>
            results
        </div>

        <div class="bg-white rounded px-2 py-2">
            {{ $events->links() }}
        </div>
    </div>

</div>
@endsection
