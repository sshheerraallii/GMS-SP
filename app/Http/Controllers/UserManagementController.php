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



    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name'  => 'required|string|max:60',

            // We'll store this in users.email (acts as username/login). Keep it unique.
            'username'   => 'required|string|max:255|unique:users,email',

            'password'   => 'required|string|min:8|max:255',
            'role'       => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email'    => $validated['username'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }
}
