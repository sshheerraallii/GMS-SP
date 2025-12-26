@extends('layouts.default')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Security Guard Details</h2>

    <a href="{{ route('security-guards.index') }}" class="btn btn-secondary mb-3">Back to List</a>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">{{ $securityGuard->fullname }}</h4>

            <div class="row mb-2">
                <div class="col-md-6"><strong>Email:</strong> {{ $securityGuard->email_address }}</div>
                <div class="col-md-6"><strong>Phone:</strong> {{ $securityGuard->phone_number }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6"><strong>License #:</strong> {{ $securityGuard->license_number }}</div>
                <div class="col-md-6"><strong>License Exp Date:</strong> {{ $securityGuard->license_exp_date }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6"><strong>Category:</strong> {{ $securityGuard->category }}</div>
                <div class="col-md-6"><strong>Address:</strong> {{ $securityGuard->adresse }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6"><strong>RTW Share Code:</strong> {{ $securityGuard->rtw_share_code }}</div>
                <div class="col-md-6"><strong>Visa Status:</strong> {{ $securityGuard->visa_status }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6"><strong>NI #:</strong> {{ $securityGuard->ni_number }}</div>
                <div class="col-md-6"><strong>Driving License:</strong> {{ $securityGuard->driving_license }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-6"><strong>Car:</strong> {{ $securityGuard->car }}</div>
                <div class="col-md-6"><strong>City:</strong> {{ $securityGuard->city }}</div>
            </div>

            <hr>

            <h5>Documents</h5>
            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Profile Picture:</strong><br>
                    @if($securityGuard->profile_picture)
                        <img src="{{ asset('storage/' . $securityGuard->profile_picture) }}" width="100" height="100">
                    @endif
                </div>
                <div class="col-md-4">
                    <strong>SIA License:</strong><br>
                    @if($securityGuard->sia_license)
                        <a href="{{ asset('storage/' . $securityGuard->sia_license) }}" target="_blank">View</a>
                    @endif
                </div>
                <div class="col-md-4">
                    <strong>Driving License Doc:</strong><br>
                    @if($securityGuard->driving_license_doc)
                        <a href="{{ asset('storage/' . $securityGuard->driving_license_doc) }}" target="_blank">View</a>
                    @endif
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Passport:</strong><br>
                    @if($securityGuard->passport)
                        <a href="{{ asset('storage/' . $securityGuard->passport) }}" target="_blank">View</a>
                    @endif
                </div>
                <div class="col-md-4">
                    <strong>Evisa SS:</strong><br>
                    @if($securityGuard->evisa_ss)
                        <a href="{{ asset('storage/' . $securityGuard->evisa_ss) }}" target="_blank">View</a>
                    @endif
                </div>
                <div class="col-md-4">
                    <strong>RTW SS:</strong><br>
                    @if($securityGuard->rtw_ss)
                        <a href="{{ asset('storage/' . $securityGuard->rtw_ss) }}" target="_blank">View</a>
                    @endif
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Proof Add 1:</strong><br>
                    @if($securityGuard->proof_add1)
                        <a href="{{ asset('storage/' . $securityGuard->proof_add1) }}" target="_blank">View</a>
                    @endif
                </div>
                <div class="col-md-4">
                    <strong>Proof Add 2:</strong><br>
                    @if($securityGuard->proof_add2)
                        <a href="{{ asset('storage/' . $securityGuard->proof_add2) }}" target="_blank">View</a>
                    @endif
                </div>
                <div class="col-md-4">
                    <strong>NI Letter:</strong><br>
                    @if($securityGuard->ni_letter)
                        <a href="{{ asset('storage/' . $securityGuard->ni_letter) }}" target="_blank">View</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
