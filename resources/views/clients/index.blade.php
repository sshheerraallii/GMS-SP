@extends('layouts.default')

@section('content')
<div class="max-w-6xl mx-auto mt-10 p-6 bg-white rounded-lg shadow-md">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Clients List</h2>
        <a href="{{ route('clients.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition">Add New Client</a>
    </div>

    <!-- Flash Message -->
    @if(session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <!-- Clients Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Name</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Email Address</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Contact Number</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Payment Terms</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Type</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach($clients as $client)
                <tr>
                    <td class="px-4 py-2 text-gray-800">{{ $client->name }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $client->email_address }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $client->contact_number }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $client->payment_terms }}</td>
                    <td class="px-4 py-2 text-gray-800">{{ $client->type ?? '-' }}</td>
                    <td class="px-4 py-2 space-x-2">

                        <a href="{{ route('clients.edit', $client->id) }}"
                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm transition">Edit</a>

                        <a href="{{ route('clients.show', $client->id) }}"
                           class="bg-teal-500 hover:bg-teal-600 text-white px-3 py-1 rounded-md text-sm transition">View</a>

                        <form action="{{ route('clients.destroy', $client->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition"
                                    onclick="return confirm('Are you sure you want to delete this client?');">
                                Delete
                            </button>
                        </form>

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
