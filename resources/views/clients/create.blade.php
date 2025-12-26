@extends('layouts.default')

@section('content')
<div class="max-w-3xl mx-auto mt-10 p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-semibold mb-6 text-gray-800">Add New Client</h2>

    <form action="{{ route('clients.store') }}" method="POST" class="space-y-5">
        @csrf

        <!-- Client Name -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Client Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('name')
                <p class="text-red-600 mt-1 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Email Address</label>
            <input type="email" name="email_address" value="{{ old('email_address') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('email_address')
                <p class="text-red-600 mt-1 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Contact Number -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Contact Number</label>
            <input type="text" name="contact_number" value="{{ old('contact_number') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('contact_number')
                <p class="text-red-600 mt-1 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Payment Terms -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Payment Terms</label>
            <input type="text" name="payment_terms" value="{{ old('payment_terms') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('payment_terms')
                <p class="text-red-600 mt-1 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Client Type -->
        <div>
            <label class="block text-gray-700 font-medium mb-1">Client Type <span class="text-red-500">*</span></label>
            <select name="type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select Type</option>
                <option value="VAT" {{ old('type') == 'VAT' ? 'selected' : '' }}>VAT</option>
                <option value="NON-VAT" {{ old('type') == 'NON-VAT' ? 'selected' : '' }}>NON VAT</option>
            </select>
            @error('type')
                <p class="text-red-600 mt-1 text-sm">{{ $message }}</p>
            @enderror
        </div>

        <!-- Buttons -->
        <div class="flex space-x-3">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-md transition">Save Client</button>
            <a href="{{ route('clients.index') }}"
               class="bg-gray-400 hover:bg-gray-500 text-white font-semibold px-5 py-2 rounded-md transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
