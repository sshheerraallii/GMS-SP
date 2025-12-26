<?php

namespace App\Http\Controllers;

use App\Models\SecurityGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SecurityGuardController extends Controller
{
    public function index()
    {
        $securityGuards = SecurityGuard::all();
        $ipAddress = request()->ip();

        return view('security-guards.index', compact('securityGuards','ipAddress'));
    }
    
    public function create()
    {
        if (Gate::allows('manage-posts')) {
            // Fetch distinct categories dynamically
            $categories = SecurityGuard::distinct('category')->pluck('category');

            return view('security-guards.create', compact('categories'));
        } else {
            abort(403, 'Permission denied');
        }
    }

    public function store(Request $request)
    {
        $guard = new SecurityGuard();

        // Text fields
        $guard->fullname = $request->fullname;
        $guard->email_address = $request->email_address;
        $guard->phone_number = $request->phone_number;
        $guard->license_number = $request->license_number;
        $guard->license_exp_date = $request->license_exp_date;
        $guard->category = $request->category;
        $guard->adresse = $request->adresse;
        $guard->rtw_share_code = $request->rtw_share_code;
        $guard->visa_status = $request->visa_status;
        $guard->ni_number = $request->ni_number;
        $guard->driving_license = $request->driving_license;
        $guard->car = $request->car;
        $guard->city = $request->city;

        // File uploads
        $files = [
            'profile_picture',
            'sia_license',
            'driving_license_doc',
            'passport',
            'evisa_ss',
            'rtw_ss',
            'proof_add1',
            'proof_add2',
            'ni_letter'
        ];

        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                $guard->$file = $request->file($file)->store('documents', 'public');
            }
        }

        $guard->save();

        return redirect()->route('security-guards.index')->with('success', 'Guard created successfully');
    }

    public function show($id)
    {
        $securityGuard = SecurityGuard::findOrFail($id);

        return view('security-guards.show', compact('securityGuard'));
    }

    public function edit($id)
    {
        if (Gate::allows('manage-posts')) {
            $securityGuard = SecurityGuard::findOrFail($id);

            return view('security-guards.edit', compact('securityGuard'));
        } else {
            abort(403, 'Permission denied');
        }
    }

    public function update(Request $request, $id)
    {
        $guard = SecurityGuard::findOrFail($id);

        // Text fields
        $guard->fullname = $request->fullname;
        $guard->email_address = $request->email_address;
        $guard->phone_number = $request->phone_number;
        $guard->license_number = $request->license_number;
        $guard->license_exp_date = $request->license_exp_date;
        $guard->category = $request->category;
        $guard->adresse = $request->adresse;
        $guard->rtw_share_code = $request->rtw_share_code;
        $guard->visa_status = $request->visa_status;
        $guard->ni_number = $request->ni_number;
        $guard->driving_license = $request->driving_license;
        $guard->car = $request->car;
        $guard->city = $request->city;

        // File uploads (replace only if new file uploaded)
        $files = [
            'profile_picture',
            'sia_license',
            'driving_license_doc',
            'passport',
            'evisa_ss',
            'rtw_ss',
            'proof_add1',
            'proof_add2',
            'ni_letter'
        ];

        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                $guard->$file = $request->file($file)->store('documents', 'public');
            }
        }

        $guard->save();

        return redirect()->route('security-guards.index')->with('success', 'Guard updated successfully');
    }

    public function destroy($id)
    {
        if (Gate::allows('manage-posts')) {
            $securityGuard = SecurityGuard::findOrFail($id);
            $securityGuard->delete();

            return redirect()->route('security-guards.index')
                ->with('success', 'Security Guard deleted successfully');
        } else {
            abort(403, 'Permission denied');
        }
    }
}
