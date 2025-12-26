@extends('layouts.default')


@section('content')
<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Events List</h2>

    @if(session('success'))
        <div class="bg-green-200 text-green-800 p-2 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

   <a href="{{ route('events.create') }}" 
   style="display: inline-block; margin-bottom: 20px; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">
   Create New Event
</a>


    <table class="table-auto border-collapse border border-gray-300 w-full">
        <thead>
            <tr class="bg-gray-200">
                <th class="border border-gray-300 p-2">Event Name</th>
                <th class="border border-gray-300 p-2">Address</th>
                <th class="border border-gray-300 p-2">Client</th>
                <th class="border border-gray-300 p-2">Charge Rate</th>
                <th class="border border-gray-300 p-2">Invoice Date</th>
                <th class="border border-gray-300 p-2">Pay Rate</th>
                <th class="border border-gray-300 p-2">Guards Assigned</th>
                <th class="border border-gray-300 p-2">Client Contact</th>
                <th class="border border-gray-300 p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $event)
            <tr>
                <td class="border border-gray-300 p-2">{{ $event->event_name }}</td>
                <td class="border border-gray-300 p-2">{{ $event->address }}</td>
                <td class="border border-gray-300 p-2">{{ $event->client->name ?? '-' }}</td>
                <td class="border border-gray-300 p-2">{{ $event->charge_rate }}/hr</td>
                <td class="border border-gray-300 p-2">{{ $event->invoice_date }}</td>
                <td class="border border-gray-300 p-2">{{ $event->pay_rate }}/hr</td>
                <td class="border border-gray-300 p-2">
                    @foreach($event->guards as $guard)
                        {{ $guard->fullname }} ({{ $guard->cin }})<br>
                    @endforeach
                </td>
                <td class="border border-gray-300 p-2">{{ $event->client_contact }}</td>
                <td class="border border-gray-300 p-2">
                    <a href="{{ route('events.edit', $event->id) }}" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600 mb-1 inline-block">Edit</a>
                    <a href="{{ route('events.show', $event->id) }}" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 mb-1 inline-block">View</a>
                    <form action="{{ route('events.destroy', $event->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
