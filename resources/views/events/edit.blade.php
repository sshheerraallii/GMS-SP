@extends('layouts.default')


@section('content')
<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Edit Event</h2>

    @if(session('success'))
        <div class="bg-green-200 text-green-800 p-2 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-200 text-red-800 p-2 mb-4 rounded">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>- {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('events.update', $event->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="event_name" class="block font-medium">Event Name:</label>
            <input type="text" name="event_name" id="event_name" class="border rounded p-2 w-full" value="{{ old('event_name', $event->event_name) }}">
        </div>

        <div class="mb-4">
            <label for="address" class="block font-medium">Address:</label>
            <input type="text" name="address" id="address" class="border rounded p-2 w-full" value="{{ old('address', $event->address) }}">
        </div>

        <div class="mb-4">
            <label for="client_id" class="block font-medium">Client Name:</label>
            <select name="client_id" id="client_id" class="border rounded p-2 w-full">
                <option value="">Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ old('client_id', $event->client_id) == $client->id ? 'selected' : '' }}>
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="charge_rate" class="block font-medium">Charge Rate (Per Hour):</label>
            <input type="number" step="0.01" name="charge_rate" id="charge_rate" class="border rounded p-2 w-full" value="{{ old('charge_rate', $event->charge_rate) }}">
        </div>

        <div class="mb-4">
            <label for="invoice_date" class="block font-medium">Invoice Date:</label>
            <input type="date" name="invoice_date" id="invoice_date" class="border rounded p-2 w-full" value="{{ old('invoice_date', $event->invoice_date) }}">
        </div>

        <div class="mb-4">
            <label for="pay_rate" class="block font-medium">Pay Rate (Per Hour):</label>
            <input type="number" step="0.01" name="pay_rate" id="pay_rate" class="border rounded p-2 w-full" value="{{ old('pay_rate', $event->pay_rate) }}">
        </div>

        <div class="mb-4">
            <label for="guards" class="block font-medium">Assign Guards:</label>
            <select name="guards[]" id="guards" class="border rounded p-2 w-full" multiple>
                @foreach($guards as $guard)
                    <option value="{{ $guard->id }}" {{ (collect(old('guards', $event->guards->pluck('id')))->contains($guard->id)) ? 'selected' : '' }}>
                        {{ $guard->fullname }} ({{ $guard->cin }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="client_contact" class="block font-medium">Client Contact #:</label>
            <input type="text" name="client_contact" id="client_contact" class="border rounded p-2 w-full" value="{{ old('client_contact', $event->client_contact) }}">
        </div>

        <div class="mb-4">
            <label for="instructions" class="block font-medium">Instructions:</label>
            <textarea name="instructions" id="instructions" rows="4" class="border rounded p-2 w-full">{{ old('instructions', $event->instructions) }}</textarea>
        </div>

        <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Update Event</button>
    </form>
</div>
@endsection
