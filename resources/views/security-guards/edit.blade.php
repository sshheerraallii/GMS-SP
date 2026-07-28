@extends('layouts.default')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-10">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">Edit Security Guard</h1>
        <p class="text-sm text-gray-500">Update guard details and documents</p>
    </div>

    {{-- Flash / Validation --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    @if(session('docs_updated') && count(session('docs_updated')))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900">
            <div class="font-semibold">Updated documents:</div>
            <ul class="list-disc pl-5 mt-1">
                @foreach(session('docs_updated') as $f)
                    <li>{{ ucwords(str_replace('_', ' ', $f)) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-900">
            <div class="font-semibold mb-1">Please fix the errors below.</div>
            <ul class="list-disc pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('security-guards.update', $securityGuard->id) }}"
          enctype="multipart/form-data"
          class="space-y-12">
        @csrf
        @method('PUT')

        <!-- ================= PERSONAL ================= -->
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-medium mb-6">Personal Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input label="Full Name" name="fullname" :value="old('fullname', $securityGuard->fullname)" />
                
                 <x-input
        label="DOB"
        name="dob"
        type="date"
        :value="old('dob', optional($securityGuard->dob)->format('Y-m-d'))"
    />
                
                
                <x-input label="Email Address" name="email_address" type="email" :value="old('email_address', $securityGuard->email_address)" />
                <x-input label="Phone Number" name="phone_number" :value="old('phone_number', $securityGuard->phone_number)" />
                <x-input label="Address" name="adresse" :value="old('adresse', $securityGuard->adresse)" />
                <x-input label="City" name="city" :value="old('city', $securityGuard->city)" />
            </div>
        </section>

        <!-- ================= LEGAL ================= -->
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-medium mb-6">License & Legal Status</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-input label="License Number" name="license_number" :value="old('license_number', $securityGuard->license_number)" />
              
              
                <x-input
    label="License Expiry Date"
    name="license_exp_date"
    type="date"
    :value="old('license_exp_date', optional($securityGuard->license_exp_date)->format('Y-m-d'))"
/>
                

                <x-select label="Category" name="category">
                    <option value="" disabled>Select</option>
                    <option value="SIA" @selected(old('category', $securityGuard->category) === 'SIA')>SIA</option>
                    <option value="Steward" @selected(old('category', $securityGuard->category) === 'Steward')>Steward</option>
                </x-select>

                <x-input label="RTW Share Code" name="rtw_share_code" :value="old('rtw_share_code', $securityGuard->rtw_share_code)" />
                
                 <x-input label="Share-Code Expiry" name="share_code_expiry" type="date" :value="old('share_code_expiry', optional($securityGuard->share_code_expiry)->format('Y-m-d'))" />
                
                <x-input label="NI Number" name="ni_number" :value="old('ni_number', $securityGuard->ni_number)" />

                <x-select label="Visa Status" name="visa_status">
                    <option value="" disabled>Select</option>
                    @foreach(['British','Student','PSW','Dependent','Spouse','ARC'] as $status)
                        <option value="{{ $status }}" @selected(old('visa_status', $securityGuard->visa_status) === $status)>
                            {{ $status }}
                        </option>
                    @endforeach
                </x-select>
            </div>
        </section>

        <!-- ================= TRANSPORT ================= -->
        <section class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-medium mb-6">Transport</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-select label="Driving License" name="driving_license">
                    <option value="" disabled>Select</option>
                    @foreach(['International','UK','No'] as $opt)
                        <option value="{{ $opt }}" @selected(old('driving_license', $securityGuard->driving_license) === $opt)>
                            {{ $opt }}
                        </option>
                    @endforeach
                </x-select>

                <x-select label="Car Ownership" name="car">
                    <option value="" disabled>Select</option>
                    <option value="Yes" @selected(old('car', $securityGuard->car) === 'Yes')>Yes</option>
                    <option value="No" @selected(old('car', $securityGuard->car) === 'No')>No</option>
                </x-select>
            </div>
        </section>

       <!-- ================= BANK DETAILS ================= -->
<section class="bg-white rounded-xl shadow p-6">
    <h2 class="text-lg font-medium mb-6">Bank Details</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-1">Sort Code</label>
            <input type="text"
                   name="sort_code"
                   maxlength="6"
                   value="{{ old('sort_code', $securityGuard->sort_code) }}"
                   class="w-full border rounded px-3 py-2">
            @error('sort_code')
                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Account Number</label>
            <input type="text"
                   name="account_number"
                   maxlength="8"
                   value="{{ old('account_number', $securityGuard->account_number) }}"
                   class="w-full border rounded px-3 py-2">
            @error('account_number')
                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Beneficiary Name</label>
            <input type="text"
                   name="beneficiary_name"
                   value="{{ old('beneficiary_name', $securityGuard->beneficiary_name) }}"
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
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-medium">Documents</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Upload only what you want to replace. Selected files will overwrite the current ones after you click <span class="font-medium">Update Guard</span>.
                    </p>
                </div>
            </div>

            @php
                $docs = [
    'profile_picture'   => 'Profile Picture',
    'sia_license'       => 'SIA License',
    'sia_license_back'  => 'SIA License (Back)',

    'driving_license_doc' => 'Driving License',
    'passport'            => 'Passport',
    'evisa_ss'            => 'E-Visa Screenshot',
    'rtw_ss'              => 'RTW Screenshot',
    'proof_add1'          => 'Proof of Address 1',
    'proof_add2'          => 'Proof of Address 2',
    'ni_letter'           => 'NI Letter',
    
    // NEW
    'act_blue'   => 'ACT Blue',
    'act_orange' => 'ACT Orange',
    'act_green'  => 'ACT Green',
    'first_aid'  => 'First Aid',
    'cctv_front' => 'CCTV License Front',
    'cctv_back'  => 'CCTV License Back',
    'other_doc1' => 'Other Document 1',
    'other_doc2' => 'Other Document 2',
    
    
];

            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($docs as $field => $label)
                    <div class="rounded-lg border border-gray-200 p-4" x-data="{ picked: null }">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-sm font-medium text-gray-800">{{ $label }}</div>

                                @if($securityGuard->$field)
                                    <a href="{{ Storage::disk('public')->url($securityGuard->$field) }}"
                                       target="_blank"
                                       class="mt-1 inline-block text-xs text-emerald-700 hover:underline">
                                        View current file
                                    </a>
                                @else
                                    <div class="mt-1 text-xs text-gray-500">No file uploaded yet.</div>
                                @endif
                            </div>

                            @if($securityGuard->$field)
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-700">
                                    Current
                                </span>
                            @endif
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Upload new (optional)</label>
                            <input
                                type="file"
                                name="{{ $field }}"
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-white"
                                @change="picked = $event.target.files?.[0] ? $event.target.files[0].name : null"
                            />

                            @error($field)
                                <div class="text-xs text-red-600 mt-2">{{ $message }}</div>
                            @enderror

                            <template x-if="picked">
                                <div class="mt-3 rounded-md border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-900">
                                    <div class="font-semibold">New file selected</div>
                                    <div class="break-all mt-1" x-text="picked"></div>
                                    <div class="mt-1 text-yellow-800">
                                        Will replace current file after you click <span class="font-semibold">Update Guard</span>.
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                @endforeach
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
                Update Guard
            </button>
        </div>

    </form>
</div>
@endsection
