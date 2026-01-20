@extends('layouts.default')

@section('content')
<div class="max-w-4xl mx-auto mt-10 p-6 bg-white rounded-lg shadow-md">

    <!-- Page Header -->
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Client Details</h2>

    <!-- Client Details List -->
    <ul class="divide-y divide-gray-200">
        <li class="py-2 flex justify-between">
            <span class="font-medium text-gray-700">Name:</span>
            <span class="text-gray-800">{{ $client->name }}</span>
        </li>
        <li class="py-2 flex justify-between">
            <span class="font-medium text-gray-700">Email:</span>
            <span class="text-gray-800">{{ $client->email_address }}</span>
        </li>
        <li class="py-2 flex justify-between">
            <span class="font-medium text-gray-700">Contact:</span>
            <span class="text-gray-800">{{ $client->contact_number }}</span>
        </li>
        <li class="py-2 flex justify-between">
            <span class="font-medium text-gray-700">Payment Terms:</span>
            <span class="text-gray-800">{{ $client->payment_terms }}</span>
        </li>
    </ul>

    <!-- Back Button -->
    <div class="mt-6">
        <a href="{{ route('clients.index') }}"
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md transition">
           Back
        </a>
    </div>
</div>
@endsection
