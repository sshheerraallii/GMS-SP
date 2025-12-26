@extends('layouts.default')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Security Guards List</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('security-guards.create') }}" class="btn btn-success mb-3">Add New Guard</a>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>License #</th>
                <th>License Exp Date</th>
                <th>Category</th>
                <th>Address</th>
                <th>RTW Share Code</th>
                <th>Visa Status</th>
                <th>NI #</th>
                <th>Driving License</th>
                <th>Car</th>
                <th>City</th>
                <th>Profile Picture</th>
                <th>SIA License</th>
                <th>Driving License Doc</th>
                <th>Passport</th>
                <th>Evisa SS</th>
                <th>RTW SS</th>
                <th>Proof Add 1</th>
                <th>Proof Add 2</th>
                <th>NI Letter</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($securityGuards as $guard)
            <tr>
                <td>{{ $guard->fullname }}</td>
                <td>{{ $guard->email_address }}</td>
                <td>{{ $guard->phone_number }}</td>
                <td>{{ $guard->license_number }}</td>
                <td>{{ $guard->license_exp_date }}</td>
                <td>{{ $guard->category }}</td>
                <td>{{ $guard->adresse }}</td>
                <td>{{ $guard->rtw_share_code }}</td>
                <td>{{ $guard->visa_status }}</td>
                <td>{{ $guard->ni_number }}</td>
                <td>{{ $guard->driving_license }}</td>
                <td>{{ $guard->car }}</td>
                <td>{{ $guard->city }}</td>
                
                <td>
                    @if($guard->profile_picture)
                        <img src="{{ asset('storage/' . $guard->profile_picture) }}" width="50" height="50">
                    @endif
                </td>

                <td>
                    @if($guard->sia_license)
                        <a href="{{ asset('storage/' . $guard->sia_license) }}" target="_blank">View</a>
                    @endif
                </td>

                <td>
                    @if($guard->driving_license_doc)
                        <a href="{{ asset('storage/' . $guard->driving_license_doc) }}" target="_blank">View</a>
                    @endif
                </td>

                <td>
                    @if($guard->passport)
                        <a href="{{ asset('storage/' . $guard->passport) }}" target="_blank">View</a>
                    @endif
                </td>

                <td>
                    @if($guard->evisa_ss)
                        <a href="{{ asset('storage/' . $guard->evisa_ss) }}" target="_blank">View</a>
                    @endif
                </td>

                <td>
                    @if($guard->rtw_ss)
                        <a href="{{ asset('storage/' . $guard->rtw_ss) }}" target="_blank">View</a>
                    @endif
                </td>

                <td>
                    @if($guard->proof_add1)
                        <a href="{{ asset('storage/' . $guard->proof_add1) }}" target="_blank">View</a>
                    @endif
                </td>

                <td>
                    @if($guard->proof_add2)
                        <a href="{{ asset('storage/' . $guard->proof_add2) }}" target="_blank">View</a>
                    @endif
                </td>

                <td>
                    @if($guard->ni_letter)
                        <a href="{{ asset('storage/' . $guard->ni_letter) }}" target="_blank">View</a>
                    @endif
                </td>

                <td>
                    <a href="{{ route('security-guards.edit', $guard->id) }}" class="btn btn-sm btn-primary mb-1">Edit</a>
                    <a href="{{ route('security-guards.show', $guard->id) }}" class="btn btn-sm btn-info mb-1">View</a>
                    <form action="{{ route('security-guards.destroy', $guard->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
