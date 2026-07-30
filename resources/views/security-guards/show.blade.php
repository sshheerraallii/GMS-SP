@extends('layouts.default')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-10">

    <!-- Header -->
   <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Security Guard Details</h1>
        <p class="text-sm text-gray-500">Full profile information</p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('security-guards.index') }}"
           class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
            ← Back to list
        </a>

        @can('view-guard-details')
        <button type="button"
                id="openCustomPdfModal"
                class="px-4 py-2 rounded-lg border border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100">
            Custom PDF
        </button>

        <a href="{{ route('security-guards.pdf', $securityGuard->id) }}"
           target="_blank"
           class="px-4 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100">
            Full PDF
        </a>
        @endcan
    </div>
</div>


@if(session('error'))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-900">
        {{ session('error') }}
    </div>
@endif
    
    

    <!-- Card -->
    @can('view-guard-details')
    <div class="bg-white rounded-xl shadow p-6 space-y-8">

        <!-- Basic Info -->
        <div>
            <h2 class="text-xl font-semibold mb-4">
                {{ $securityGuard->fullname }}
            </h2>

<div>
    <span class="font-medium">DOB:</span>
    {{ $securityGuard->dob ? \Carbon\Carbon::parse($securityGuard->dob)->format('d M Y') : '—' }}
</div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><span class="font-medium">Email:</span> {{ $securityGuard->email_address }}</div>
                <div><span class="font-medium">Phone:</span> {{ $securityGuard->phone_number }}</div>

                <div><span class="font-medium">License #:</span> {{ $securityGuard->license_number }}</div>
                <div><span class="font-medium">License Expiry:</span> {{ $securityGuard->license_exp_date }}</div>

                <div><span class="font-medium">Category:</span> {{ $securityGuard->category }}</div>
                <div><span class="font-medium">Address:</span> {{ $securityGuard->adresse }}</div>

                <div><span class="font-medium">RTW Share Code:</span> {{ $securityGuard->rtw_share_code }}</div>
                
                                <div><span class="font-medium">Share-Code Expiry:</span> {{ $securityGuard->share_code_expiry ? \Carbon\Carbon::parse($securityGuard->share_code_expiry)->format('d M Y') : '—' }}</div>

                
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
    @if(!empty($securityGuard->beneficiary_name))
        <span class="text-gray-500">—</span>
        <span class="font-medium">{{ $securityGuard->beneficiary_name }}</span>
    @endif
</p>

</div>
</div>

        <!-- Documents -->
        <div>
            @php
                $requiredDocs = [
                    'profile_picture','sia_license','sia_license_back','driving_license_doc','passport',
                    'evisa_ss','rtw_ss','proof_add1','proof_add2','ni_letter',
                ];
                $missingRequired = collect($requiredDocs)->filter(fn ($f) => empty($securityGuard->$f))->count();
            @endphp
            <h3 class="text-lg font-semibold mb-4 border-b pb-2 flex items-center">
                <span>Documents</span>
                @if($missingRequired > 0)
                    <span class="ml-2 inline-flex items-center rounded-full bg-red-100 text-red-700 text-xs font-medium px-2 py-0.5">
                        {{ $missingRequired }} required missing
                    </span>
                @endif
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">

                <!-- Profile Picture -->
                <div class="border rounded-lg p-4 text-center">
                    <p class="font-medium mb-2">Profile Picture</p>
                    @if($securityGuard->profile_picture)
                        <img
                            src="{{ asset('storage/'.$securityGuard->profile_picture) }}"
                            class="mx-auto h-32 w-32 rounded-full object-cover border">
                    @else
                        <span class="inline-flex items-center rounded-full bg-red-100 text-red-700 text-xs font-medium px-2 py-0.5">Missing</span>
                    @endif
                </div>

                @php
                    $documents = [
                        'sia_license' => 'SIA License',
                        'sia_license_back' => 'SIA License (Back)',
                        'driving_license_doc' => 'Driving License',
                        'passport' => 'Passport',
                        'evisa_ss' => 'E-Visa Screenshot',
                        'rtw_ss' => 'RTW Screenshot',
                        'proof_add1' => 'Proof of Address 1',
                        'proof_add2' => 'Proof of Address 2',
                        'ni_letter' => 'NI Letter',
                        
                        // NEW
    'act_blue' => 'ACT Blue',
    'act_orange' => 'ACT Orange',
    'act_green' => 'ACT Green',
    'first_aid' => 'First Aid',
    'cctv_front' => 'CCTV License Front',
    'cctv_back' => 'CCTV License Back',
    'other_doc1' => 'Other Document 1',
    'other_doc2' => 'Other Document 2',
                        
                        
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
                        @elseif(in_array($field, $requiredDocs))
                            <span class="inline-flex items-center self-start rounded-full bg-red-100 text-red-700 text-xs font-medium px-2 py-0.5">Missing</span>
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
    @else
    <!-- Restricted view (Moderator): Name / Address / Badge / Expiry only -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold mb-4">{{ $securityGuard->fullname }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="font-medium">Address:</span> {{ $securityGuard->adresse ?? '-' }}</div>
            <div><span class="font-medium">Badge No:</span> {{ $securityGuard->license_number ?? '-' }}</div>
            <div><span class="font-medium">Expiry:</span> {{ $securityGuard->license_exp_date ? \Carbon\Carbon::parse($securityGuard->license_exp_date)->format('d M Y') : '-' }}</div>
        </div>
    </div>
    @endcan
</div>

@can('view-guard-details')
<div id="customPdfModal" style="display:none; position:fixed; inset:0; z-index:9999;">
    <div id="customPdfBackdrop" style="position:absolute; inset:0; background:rgba(0,0,0,.55);"></div>

    <div
        style="
            position:absolute;
            top:20px;
            bottom:20px;
            left:50%;
            transform:translateX(-50%);
            width:min(1100px, calc(100vw - 32px));
            background:#fff;
            border-radius:16px;
            box-shadow:0 25px 50px rgba(0,0,0,.25);
            display:flex;
            flex-direction:column;
            overflow:hidden;
        "
    >
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 24px; border-bottom:1px solid #e5e7eb; flex:0 0 auto;">
            <div>
                <h2 style="margin:0; font-size:18px; font-weight:600; color:#1f2937;">Custom Guard PDF</h2>
                <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">Choose which fields and documents to include.</p>
            </div>

            <button type="button"
                    id="closeCustomPdfModal"
                    style="border:none; background:transparent; font-size:22px; line-height:1; cursor:pointer; color:#6b7280; padding:6px 10px;">
                ×
            </button>
        </div>

        <form method="GET"
              action="{{ route('security-guards.customPdf', $securityGuard->id) }}"
              target="_blank"
              style="display:flex; flex-direction:column; min-height:0; flex:1 1 auto;">

            @php
                $customFieldOptions = [
                    'fullname'          => 'Full Name',
                    'dob'               => 'DOB',
                    'email_address'     => 'Email',
                    'phone_number'      => 'Phone',
                    'license_number'    => 'License #',
                    'license_exp_date'  => 'License Expiry',
                    'category'          => 'Category',
                    'adresse'           => 'Address',
                    'rtw_share_code'    => 'RTW Share Code',
                    'share_code_expiry' => 'Share-Code Expiry',
                    'visa_status'       => 'Visa Status',
                    'ni_number'         => 'NI Number',
                    'driving_license'   => 'Driving License',
                    'car'               => 'Car',
                    'city'              => 'City',
                    'sort_code'         => 'Sort Code',
                    'account_number'    => 'Account Number',
                    'beneficiary_name'  => 'Beneficiary Name',
                ];

                $customDocOptions = [
                    'profile_picture'     => 'Profile Picture',
                    'sia_license'         => 'SIA License',
                    'sia_license_back'    => 'SIA License (Back)',
                    'driving_license_doc' => 'Driving License',
                    'passport'            => 'Passport',
                    'evisa_ss'            => 'E-Visa Screenshot',
                    'rtw_ss'              => 'RTW Screenshot',
                    'proof_add1'          => 'Proof of Address 1',
                    'proof_add2'          => 'Proof of Address 2',
                    'ni_letter'           => 'NI Letter',
                    'act_blue'            => 'ACT Blue',
                    'act_orange'          => 'ACT Orange',
                    'act_green'           => 'ACT Green',
                    'first_aid'           => 'First Aid',
                    'cctv_front'          => 'CCTV License Front',
                    'cctv_back'           => 'CCTV License Back',
                    'other_doc1'          => 'Other Document 1',
                    'other_doc2'          => 'Other Document 2',
                ];
            @endphp

            <div style="flex:1 1 auto; min-height:0; overflow-y:auto; padding:24px;">
                <div style="display:grid; grid-template-columns:1fr; gap:24px;">
                    <div style="border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
    <h3 style="margin:0; font-size:14px; font-weight:600; color:#1f2937;">Fields</h3>

    <button type="button"
            onclick="toggleGroup('fields', this)"
            style="font-size:12px; color:#2563eb; cursor:pointer; background:none; border:none;">
        Select All
    </button>
</div>
                        <div style="display:grid; grid-template-columns:1fr; gap:10px;">
                            @foreach($customFieldOptions as $field => $label)
                                <label style="display:flex; align-items:center; gap:8px; font-size:14px; color:#374151;">
                                    <input type="checkbox" name="fields[]" value="{{ $field }}">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
    <h3 style="margin:0; font-size:14px; font-weight:600; color:#1f2937;">Documents</h3>

    <button type="button"
            onclick="toggleGroup('docs', this)"
            style="font-size:12px; color:#2563eb; cursor:pointer; background:none; border:none;">
        Select All
    </button>
</div>
                        <div style="display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:10px;">
                            @foreach($customDocOptions as $field => $label)
                                <label style="display:flex; align-items:center; gap:8px; font-size:14px; color:#374151;">
                                    <input type="checkbox" name="docs[]" value="{{ $field }}">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; padding:16px 24px; border-top:1px solid #e5e7eb; flex:0 0 auto;">
                <button type="button"
                        id="cancelCustomPdfModal"
                        style="padding:10px 16px; border:1px solid #d1d5db; border-radius:10px; background:#fff; color:#374151; cursor:pointer;">
                    Cancel
                </button>

                <button type="submit"
                        style="padding:10px 18px; border:none; border-radius:10px; background:#059669; color:#fff; cursor:pointer;">
                    Generate PDF
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleGroup(name, btn) {
    const checkboxes = document.querySelectorAll(`input[name="${name}[]"]`);
    if (!checkboxes.length) return;

    const allChecked = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });

    btn.innerText = allChecked ? 'Select All' : 'Unselect All';
}

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('customPdfModal');
    const backdrop = document.getElementById('customPdfBackdrop');
    const openBtn = document.getElementById('openCustomPdfModal');
    const closeBtn = document.getElementById('closeCustomPdfModal');
    const cancelBtn = document.getElementById('cancelCustomPdfModal');

    if (!modal || !openBtn) return;

    function openModal() {
        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });
});
</script>



@endcan

@endsection
