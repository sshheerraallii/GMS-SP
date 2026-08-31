@extends('layouts.default')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-10">

    <!-- Header -->
    <div class="mb-10">
        <h1 class="text-2xl font-semibold text-gray-800">Security Guard Registration</h1>
        <p class="text-sm text-gray-500">Step-by-step guard onboarding</p>
    </div>

    {{-- ERRORS --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            <div class="font-semibold mb-2">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Success --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <!-- Stepper -->
    <div class="flex gap-6 mb-10 text-sm">
        <div class="font-semibold text-emerald-600">1. Personal</div>
        <div class="text-gray-400">2. Legal</div>
        <div class="text-gray-400">3. Transport</div>
        <div class="text-gray-400">4. Documents</div>
    </div>

    <form method="POST"
          action="{{ route('security-guards.store') }}"
          enctype="multipart/form-data"
          class="space-y-12">
        @csrf

        <!-- ================= PERSONAL ================= -->
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-medium mb-6">Personal Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input label="Full Name" name="fullname" />
                <x-input label="Date of Birth" name="dob" type="date" />
                <x-input label="Email Address" name="email_address" type="email" />
                <x-input label="Phone Number" name="phone_number" />
                <x-input label="Address" name="adresse" />
                <x-input label="City" name="city" />
            </div>
        </section>

        <!-- ================= LEGAL ================= -->
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-medium mb-6">License & Legal Status</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-input label="License Number" name="license_number" />
                <x-input label="License Expiry Date" name="license_exp_date" type="date" />

                <x-select label="Category" name="category">
                    <option value="" {{ old('category') ? '' : 'selected' }}>Select</option>
                    <option value="SIA" {{ old('category') === 'SIA' ? 'selected' : '' }}>SIA</option>
                    <option value="Steward" {{ old('category') === 'Steward' ? 'selected' : '' }}>Steward</option>
                </x-select>

                <x-input label="RTW Share Code" name="rtw_share_code" />
                 <x-input label="Share-Code Expiry" name="share_code_expiry" type="date" />
                <x-input label="NI Number" name="ni_number" />

                <x-select label="Visa Status" name="visa_status">
                    <option value="" {{ old('visa_status') ? '' : 'selected' }}>Select</option>
                    <option value="British" {{ old('visa_status') === 'British' ? 'selected' : '' }}>British</option>
                    <option value="Student" {{ old('visa_status') === 'Student' ? 'selected' : '' }}>Student</option>
                    <option value="PSW" {{ old('visa_status') === 'PSW' ? 'selected' : '' }}>PSW</option>
                    <option value="Dependent" {{ old('visa_status') === 'Dependent' ? 'selected' : '' }}>Dependent</option>
                    <option value="Spouse" {{ old('visa_status') === 'Spouse' ? 'selected' : '' }}>Spouse</option>
                    <option value="ARC" {{ old('visa_status') === 'ARC' ? 'selected' : '' }}>ARC</option>
                </x-select>
            </div>
        </section>

        <!-- ================= TRANSPORT ================= -->
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-medium mb-6">Transport</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-select label="Driving License" name="driving_license">
                    <option value="" {{ old('driving_license') ? '' : 'selected' }}>Select</option>
                    <option value="International" {{ old('driving_license') === 'International' ? 'selected' : '' }}>International</option>
                    <option value="UK" {{ old('driving_license') === 'UK' ? 'selected' : '' }}>UK</option>
                    <option value="No" {{ old('driving_license') === 'No' ? 'selected' : '' }}>No</option>
                </x-select>

                <x-select label="Car Ownership" name="car">
                    <option value="" {{ old('car') ? '' : 'selected' }}>Select</option>
                    <option value="Yes" {{ old('car') === 'Yes' ? 'selected' : '' }}>Yes</option>
                    <option value="No" {{ old('car') === 'No' ? 'selected' : '' }}>No</option>
                </x-select>
            </div>
        </section>

        <!-- ================= Bank Details ================= -->
<section class="bg-white rounded-xl shadow p-6">
    <h2 class="text-lg font-medium mb-6">Bank Details</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-1">Sort Code</label>
            <input type="text"
                   name="sort_code"
                   maxlength="6"
                   value="{{ old('sort_code') }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Account Number</label>
            <input type="text"
                   name="account_number"
                   maxlength="8"
                   value="{{ old('account_number') }}"
                   class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Beneficiary Name</label>
            <input type="text"
                   name="beneficiary_name"
                   value="{{ old('beneficiary_name') }}"
                   class="w-full border rounded px-3 py-2"
                   placeholder="e.g. John Smith" />
            @error('beneficiary_name')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
</section>


        <!-- ================= DOCUMENTS ================= -->
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-medium mb-6">Documents</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-file-upload label="Profile Picture" name="profile_picture" />
                <x-file-upload label="SIA License" name="sia_license" />
                <x-file-upload label="SIA License (Back)" name="sia_license_back" />
                <x-file-upload label="Driving License" name="driving_license_doc" />
                <x-file-upload label="Passport" name="passport" />
                <x-file-upload label="E-Visa Screenshot" name="evisa_ss" />
                <x-file-upload label="RTW Screenshot" name="rtw_ss" />
                <x-file-upload label="Proof of Address 1" name="proof_add1" />
                <x-file-upload label="Proof of Address 2" name="proof_add2" />
                <x-file-upload label="NI Letter" name="ni_letter" />
                
                <x-file-upload label="ACT Blue" name="act_blue" />
<x-file-upload label="ACT Orange" name="act_orange" />
<x-file-upload label="ACT Green" name="act_green" />

<x-file-upload label="First Aid" name="first_aid" />

<x-file-upload label="CCTV License Front" name="cctv_front" />
<x-file-upload label="CCTV License Back" name="cctv_back" />

<x-file-upload label="Other Document 1" name="other_doc1" />
<x-file-upload label="Other Document 2" name="other_doc2" />
                
                
                
            </div>
        </section>

        <!-- ================= ACTIONS ================= -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('security-guards.index') }}"
               class="px-6 py-2 rounded-lg border hover:bg-gray-100">
                Cancel
            </a>

            <button type="submit"
                    class="px-8 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                Save Guard
            </button>
        </div>

    </form>
</div>

<script>
function previewFile(input) {
    const container = input.closest('div');
    const img = container.querySelector('img');
    const name = container.querySelector('.file-name');

    if (input.files[0]) {
        img.src = URL.createObjectURL(input.files[0]);
        img.classList.remove('hidden');
        name.textContent = input.files[0].name;
    }
}

function handleDrop(e, name) {
    e.preventDefault();
    const input = document.getElementById(name);
    input.files = e.dataTransfer.files;
    previewFile(input);
}
</script>
@endsection
