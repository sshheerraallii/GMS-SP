@extends('layouts.default')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-10">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Clients</h1>
            <p class="text-sm text-gray-500">
                Showing {{ $clients->total() }} total clients
            </p>
        </div>

        @can('clients.create')
            <a href="{{ route('clients.create') }}"
               class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition">
                + Add New Client
            </a>
        @endcan
    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Controls --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Show</label>

            <select name="per_page"
                    onchange="this.form.submit()"
                    class="border rounded px-2 py-2 text-sm bg-white">
                @foreach([10,20,30,40,50] as $size)
                    <option value="{{ $size }}" {{ request('per_page', 20) == $size ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                @endforeach
            </select>

            <span class="text-sm text-gray-600">per page</span>
        </form>

        <div class="text-sm text-gray-500">
            Page {{ $clients->currentPage() }} of {{ $clients->lastPage() }}
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100">
                    <tr class="text-left text-gray-700">
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Name</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Email Address</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Contact Number</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Payment Terms</th>
                        <th class="px-4 py-3 font-semibold text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($clients as $client)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                                {{ $client->name }}
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $client->email_address ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                {{ $client->contact_number ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $client->payment_terms ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('clients.show', $client->id) }}"
                                       class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded-md text-xs transition">
                                        View
                                    </a>

                                    @can('clients.edit')
                                        <a href="{{ route('clients.edit', $client->id) }}"
                                           class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-xs transition">
                                            Edit
                                        </a>
                                    @endcan

                                    @can('clients.delete')
                                        <form action="{{ route('clients.destroy', $client->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this client?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md text-xs transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No clients found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $clients->links() }}
    </div>

</div>
@endsection
