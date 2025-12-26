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
                                <h3 class="mb-5 text-uppercase">Security Registration Form</h3>
                                <form method="post" action="{{ route('security-guards.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <div class="form-outline">
                                                <input type="text" name="fullname" class="form-control form-control-lg" />
                                                <label class="form-label">Full Name</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <div class="form-outline">
                                                <input type="email" name="email_address" class="form-control form-control-lg" />
                                                <label class="form-label">Email Address</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <div class="form-outline">
                                                <input type="text" name="phone_number" class="form-control form-control-lg" />
                                                <label class="form-label">Phone Number</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <div class="form-outline">
                                                <input type="text" name="license_number" class="form-control form-control-lg" />
                                                <label class="form-label">License #</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <div class="form-outline">
                                                <input type="date" name="license_exp_date" class="form-control form-control-lg" />
                                                <label class="form-label">License Exp Date</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <select class="form-select" name="category">
                                                <option selected disabled>Category</option>
                                                <option value="SIA">SIA</option>
                                                <option value="Steward">Steward</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-outline mb-4">
                                        <input type="text" name="adresse" class="form-control form-control-lg" />
                                        <label class="form-label">Address</label>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <input type="text" name="rtw_share_code" class="form-control form-control-lg" />
                                            <label class="form-label">RTW Share Code</label>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <select class="form-select" name="visa_status">
                                                <option selected disabled>Visa Status</option>
                                                <option value="British">British</option>
                                                <option value="Student">Student</option>
                                                <option value="PSW">PSW</option>
                                                <option value="Dependent">Dependent</option>
                                                <option value="Spouse">Spouse</option>
                                                <option value="ARC">ARC</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <input type="text" name="ni_number" class="form-control form-control-lg" />
                                            <label class="form-label">NI #</label>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <select class="form-select" name="driving_license">
                                                <option selected disabled>Driving License</option>
                                                <option value="International">International</option>
                                                <option value="UK">UK</option>
                                                <option value="No">No</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <select class="form-select" name="car">
                                                <option selected disabled>Car</option>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <input type="text" name="city" class="form-control form-control-lg" />
                                            <label class="form-label">City</label>
                                        </div>
                                    </div>

                                    <h5 class="mb-3">Documents</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
    <label class="form-label" for="profile_picture">Profile Picture</label>
    <input type="file" name="profile_picture" id="profile_picture" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="sia_license">SIA License</label>
    <input type="file" name="sia_license" id="sia_license" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="driving_license_doc">Driving License</label>
    <input type="file" name="driving_license_doc" id="driving_license_doc" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="passport">Passport</label>
    <input type="file" name="passport" id="passport" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="evisa_ss">Evisa Screenshot</label>
    <input type="file" name="evisa_ss" id="evisa_ss" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="rtw_ss">RTW Screenshot</label>
    <input type="file" name="rtw_ss" id="rtw_ss" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="proof_add1">Proof of Address 1</label>
    <input type="file" name="proof_add1" id="proof_add1" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="proof_add2">Proof of Address 2</label>
    <input type="file" name="proof_add2" id="proof_add2" class="form-control" />
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="ni_letter">NI Letter</label>
    <input type="file" name="ni_letter" id="ni_letter" class="form-control" />
</div>

                                    </div>

                                    <div class="d-flex justify-content-end pt-3">
                                        <button type="submit" class="btn btn-success btn-lg ms-2">Submit Form</button>
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
