@extends('layouts.default')

@section('content')
<div class="max-w-3xl mx-auto mt-10 p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-semibold mb-6 text-gray-800">Edit Client</h2>

    <form action="{{ route('clients.update', $client->id) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <!-- Client Name -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Client Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ $client->name }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <!-- Email Address -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Email Address</label>
            <input type="email" name="email_address" value="{{ $client->email_address }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- Contact Number -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Contact Number</label>
            <input type="text" name="contact_number" value="{{ $client->contact_number }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- Payment Terms -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Payment Terms</label>
            <input type="text" name="payment_terms" value="{{ $client->payment_terms }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        @include('clients.partials.color-picker', ['current' => old('color', $client->color)])

        <!-- Buttons -->
        <div class="flex space-x-3">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-md transition">Update Client</button>
            <a href="{{ route('clients.index') }}"
               class="bg-gray-400 hover:bg-gray-500 text-white font-semibold px-5 py-2 rounded-md transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
