@extends('layouts.default')

<body>
@section('content')
<section class="h-80 bg-dark">
    <div class="container py-3 h-80">
        <div class="row d-flex justify-content-center align-items-center h-80">
            <div class="col">
                <div class="card card-registration my-4">
                    <div class="row g-0">
                        <div class="col-xl-12">
                            <div class="card-body p-md-5 text-black">
                                <h3 class="mb-5 text-uppercase">Edit Security Form</h3>
                                <form method="post" action="{{ route('security-guards.update', $securityGuard->id) }}" enctype="multipart/form-data">
                                    @csrf
                                    @method('put')

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <input type="text" name="fullname" class="form-control" value="{{ $securityGuard->fullname }}" />
                                            <label class="form-label">Full Name</label>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <input type="email" name="email_address" class="form-control" value="{{ $securityGuard->email_address }}" />
                                            <label class="form-label">Email Address</label>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <input type="text" name="phone_number" class="form-control" value="{{ $securityGuard->phone_number }}" />
                                            <label class="form-label">Phone Number</label>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <input type="text" name="license_number" class="form-control" value="{{ $securityGuard->license_number }}" />
                                            <label class="form-label">License #</label>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <input type="date" name="license_exp_date" class="form-control" value="{{ $securityGuard->license_exp_date }}" />
                                            <label class="form-label">License Exp Date</label>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <select class="form-select" name="category">
                                                <option selected disabled>Category</option>
                                                <option value="SIA" {{ $securityGuard->category == 'SIA' ? 'selected' : '' }}>SIA</option>
                                                <option value="Steward" {{ $securityGuard->category == 'Steward' ? 'selected' : '' }}>Steward</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-outline mb-4">
                                        <input type="text" name="adresse" class="form-control" value="{{ $securityGuard->adresse }}" />
                                        <label class="form-label">Address</label>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <input type="text" name="rtw_share_code" class="form-control" value="{{ $securityGuard->rtw_share_code }}" />
                                            <label class="form-label">RTW Share Code</label>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <select class="form-select" name="visa_status">
                                                <option selected disabled>Visa Status</option>
                                                <option value="British" {{ $securityGuard->visa_status == 'British' ? 'selected' : '' }}>British</option>
                                                <option value="Student" {{ $securityGuard->visa_status == 'Student' ? 'selected' : '' }}>Student</option>
                                                <option value="PSW" {{ $securityGuard->visa_status == 'PSW' ? 'selected' : '' }}>PSW</option>
                                                <option value="Dependent" {{ $securityGuard->visa_status == 'Dependent' ? 'selected' : '' }}>Dependent</option>
                                                <option value="Spouse" {{ $securityGuard->visa_status == 'Spouse' ? 'selected' : '' }}>Spouse</option>
                                                <option value="ARC" {{ $securityGuard->visa_status == 'ARC' ? 'selected' : '' }}>ARC</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <input type="text" name="ni_number" class="form-control" value="{{ $securityGuard->ni_number }}" />
                                            <label class="form-label">NI #</label>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <select class="form-select" name="driving_license">
                                                <option selected disabled>Driving License</option>
                                                <option value="International" {{ $securityGuard->driving_license == 'International' ? 'selected' : '' }}>International</option>
                                                <option value="UK" {{ $securityGuard->driving_license == 'UK' ? 'selected' : '' }}>UK</option>
                                                <option value="No" {{ $securityGuard->driving_license == 'No' ? 'selected' : '' }}>No</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <select class="form-select" name="car">
                                                <option selected disabled>Car</option>
                                                <option value="Yes" {{ $securityGuard->car == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="No" {{ $securityGuard->car == 'No' ? 'selected' : '' }}>No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <input type="text" name="city" class="form-control" value="{{ $securityGuard->city }}" />
                                            <label class="form-label">City</label>
                                        </div>
                                    </div>

                                    <h5 class="mb-3">Documents</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
    <label class="form-label" for="profile_picture">Profile Picture</label>
    @if($securityGuard->profile_picture)
        <div class="mb-1">
            <a href="{{ asset('storage/' . $securityGuard->profile_picture) }}" target="_blank">View Current File</a>
        </div>
    @endif
    <input type="file" name="profile_picture" id="profile_picture" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="sia_license">SIA License</label>
    @if($securityGuard->sia_license)
        <div class="mb-1">
            <a href="{{ asset('storage/' . $securityGuard->sia_license) }}" target="_blank">View Current File</a>
        </div>
    @endif
    <input type="file" name="sia_license" id="sia_license" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="driving_license_doc">Driving License Document</label>
    @if($securityGuard->driving_license_doc)
        <div class="mb-1">
            <a href="{{ asset('storage/' . $securityGuard->driving_license_doc) }}" target="_blank">View Current File</a>
        </div>
    @endif
    <input type="file" name="driving_license_doc" id="driving_license_doc" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="passport">Passport</label>
    @if($securityGuard->passport)
        <div class="mb-1">
            <a href="{{ asset('storage/' . $securityGuard->passport) }}" target="_blank">View Current File</a>
        </div>
    @endif
    <input type="file" name="passport" id="passport" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="evisa_ss">E-Visa Screenshot</label>
    @if($securityGuard->evisa_ss)
        <div class="mb-1">
            <a href="{{ asset('storage/' . $securityGuard->evisa_ss) }}" target="_blank">View Current File</a>
        </div>
    @endif
    <input type="file" name="evisa_ss" id="evisa_ss" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="rtw_ss">RTW Screenshot</label>
    @if($securityGuard->rtw_ss)
        <div class="mb-1">
            <a href="{{ asset('storage/' . $securityGuard->rtw_ss) }}" target="_blank">View Current File</a>
        </div>
    @endif
    <input type="file" name="rtw_ss" id="rtw_ss" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="proof_add1">Proof of Address 1</label>
    @if($securityGuard->proof_add1)
        <div class="mb-1">
            <a href="{{ asset('storage/' . $securityGuard->proof_add1) }}" target="_blank">View Current File</a>
        </div>
    @endif
    <input type="file" name="proof_add1" id="proof_add1" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="proof_add2">Proof of Address 2</label>
    @if($securityGuard->proof_add2)
        <div class="mb-1">
            <a href="{{ asset('storage/' . $securityGuard->proof_add2) }}" target="_blank">View Current File</a>
        </div>
    @endif
    <input type="file" name="proof_add2" id="proof_add2" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="ni_letter">NI Letter</label>
    @if($securityGuard->ni_letter)
        <div class="mb-1">
            <a href="{{ asset('storage/' . $securityGuard->ni_letter) }}" target="_blank">View Current File</a>
        </div>
    @endif
    <input type="file" name="ni_letter" id="ni_letter" class="form-control" />
</div>

                                    </div>

                                    <div class="d-flex justify-content-end pt-3">
                                        <button type="submit" class="btn btn-primary btn-lg ms-2">Update Form</button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>
@endsection
</body>
