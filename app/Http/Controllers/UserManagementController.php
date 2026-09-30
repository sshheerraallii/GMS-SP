<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderByDesc('id')->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('users.create', compact('roles'));
    }

public function destroy(\App\Models\User $user)
{
    // Prevent deleting yourself
    if (auth()->id() === $user->id) {
        return back()->with('error', "You can't delete your own account.");
    }

    // Optional: remove role relations cleanly (not required, but tidy)
    $user->syncRoles([]);

    $user->delete();

    return back()->with('success', 'User deleted successfully.');
}



    /** V4-P6: set / clear the optional real email used for critical alerts. */
    public function updateContactEmail(Request $request, User $user)
    {
        $data = $request->validate(['contact_email' => 'nullable|email|max:255']);
        $user->contact_email = ($data['contact_email'] ?? '') !== '' ? $data['contact_email'] : null;
        $user->save();

        return back()->with('success', 'Contact email updated for ' . $user->name . '.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name'  => 'required|string|max:60',

            // We'll store this in users.email (acts as username/login). Keep it unique.
            'username'   => 'required|string|max:255|unique:users,email',

            'contact_email' => 'nullable|email|max:255',
            'password'   => 'required|string|min:8|max:255',
            'role'       => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email'    => $validated['username'],
            'contact_email' => $validated['contact_email'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }
}
