@extends('layouts.default')


@section('content')
<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Event Details</h2>

    @if(session('success'))
        <div class="bg-green-200 text-green-800 p-2 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow-md rounded p-4 mb-4">
        <p><strong>Event Name:</strong> {{ $event->event_name }}</p>
        <p><strong>Address:</strong> {{ $event->address }}</p>
        <p><strong>Client:</strong> {{ $event->client->name ?? '-' }}</p>
        <p><strong>Charge Rate:</strong> {{ $event->charge_rate }}/hr</p>
        <p><strong>Invoice Date:</strong> {{ $event->invoice_date }}</p>
        <p><strong>Pay Rate:</strong> {{ $event->pay_rate }}/hr</p>
        <p><strong>Client Contact #:</strong> {{ $event->client_contact }}</p>
        <p><strong>Instructions:</strong> {{ $event->instructions ?? '-' }}</p>
        <p><strong>Assigned Guards:</strong>
            @if($event->guards->isNotEmpty())
                <ul class="list-disc pl-5">
                    @foreach($event->guards as $guard)
                        <li>{{ $guard->fullname }} ({{ $guard->cin }})</li>
                    @endforeach
                </ul>
            @else
                None
            @endif
        </p>
    </div>

    <a href="{{ route('events.edit', $event->id) }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit Event</a>
    <a href="{{ route('events.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Back to Events</a>
</div>
@endsection
