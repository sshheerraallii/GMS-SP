@extends('layouts.default')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-10">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Security Guard Details</h1>
            <p class="text-sm text-gray-500">Full profile information</p>
        </div>

        <a href="{{ route('security-guards.index') }}"
           class="px-4 py-2 rounded-lg border hover:bg-gray-100">
            ← Back to list
        </a>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-xl shadow p-6 space-y-8">

        <!-- Basic Info -->
        <div>
            <h2 class="text-xl font-semibold mb-4">
                {{ $securityGuard->fullname }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><span class="font-medium">Email:</span> {{ $securityGuard->email_address }}</div>
                <div><span class="font-medium">Phone:</span> {{ $securityGuard->phone_number }}</div>

                <div><span class="font-medium">License #:</span> {{ $securityGuard->license_number }}</div>
                <div><span class="font-medium">License Expiry:</span> {{ $securityGuard->license_exp_date }}</div>

                <div><span class="font-medium">Category:</span> {{ $securityGuard->category }}</div>
                <div><span class="font-medium">Address:</span> {{ $securityGuard->adresse }}</div>

                <div><span class="font-medium">RTW Share Code:</span> {{ $securityGuard->rtw_share_code }}</div>
                <div><span class="font-medium">Visa Status:</span> {{ $securityGuard->visa_status }}</div>

                <div><span class="font-medium">NI Number:</span> {{ $securityGuard->ni_number }}</div>
                <div><span class="font-medium">Driving License:</span> {{ $securityGuard->driving_license }}</div>

                <div><span class="font-medium">Car:</span> {{ $securityGuard->car }}</div>
                <div><span class="font-medium">City:</span> {{ $securityGuard->city }}</div>
            </div>
        </div>

        <!-- Bank Deatils -->
        <div>
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Bank Deatils</h3>
           <div class="mt-4">
    <h3 class="font-semibold text-lg mb-2">Account Details</h3>

    <p>
        <strong>Sort Code:</strong>
        {{ $securityGuard->sort_code ?? '-' }}
    </p>

    <p>
        <strong>Account Number:</strong>
        {{ $securityGuard->account_number ?? '-' }}
    </p>
</div>
</div>

        <!-- Documents -->
        <div>
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Documents</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">

                <!-- Profile Picture -->
                <div class="border rounded-lg p-4 text-center">
                    <p class="font-medium mb-2">Profile Picture</p>
                    @if($securityGuard->profile_picture)
                        <img
                            src="{{ asset('storage/'.$securityGuard->profile_picture) }}"
                            class="mx-auto h-32 w-32 rounded-full object-cover border">
                    @else
                        <p class="text-gray-400">Not uploaded</p>
                    @endif
                </div>

                @php
                    $documents = [
                        'sia_license' => 'SIA License',
                        'driving_license_doc' => 'Driving License',
                        'passport' => 'Passport',
                        'evisa_ss' => 'E-Visa Screenshot',
                        'rtw_ss' => 'RTW Screenshot',
                        'proof_add1' => 'Proof of Address 1',
                        'proof_add2' => 'Proof of Address 2',
                        'ni_letter' => 'NI Letter'
                    ];
                @endphp

                @foreach($documents as $field => $label)
                    <div class="border rounded-lg p-4 flex flex-col justify-between">
                        <p class="font-medium mb-2">{{ $label }}</p>

                        @if($securityGuard->$field)
                            <a href="{{ asset('storage/'.$securityGuard->$field) }}"
                               target="_blank"
                               class="text-emerald-600 hover:underline">
                                View Document
                            </a>
                        @else
                            <p class="text-gray-400">Not uploaded</p>
                        @endif
                    </div>
                @endforeach

            </div>
        </div>

        <!-- Actions -->
       <div class="flex justify-end gap-3 pt-4 border-t">

    @can('guards.edit')
        <a href="{{ route('security-guards.edit', $securityGuard->id) }}"
           class="px-4 py-2 rounded-lg border hover:bg-gray-100">
            Edit
        </a>
    @endcan

    @can('guards.delete')
        <form action="{{ route('security-guards.destroy', $securityGuard->id) }}"
              method="POST"
              onsubmit="return confirm('Delete this guard permanently?')">
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="px-4 py-2 rounded-lg border text-red-600 hover:bg-red-50">
                Delete
            </button>
        </form>
    @endcan

</div>


    </div>
</div>
@endsection
