@extends('layouts.default')

@section('content')
<div class="container mx-auto p-4 max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Create User</h2>
        <a href="{{ route('users.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            Back
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}" class="bg-white shadow rounded p-6 space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}"
                       class="w-full border rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}"
                       class="w-full border rounded px-3 py-2" required>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Username</label>
            <input type="text" name="username" value="{{ old('username') }}"
                   class="w-full border rounded px-3 py-2" required>
            <p class="text-xs text-gray-500 mt-1">This will be used for login (stored in email field).</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Role</label>
            <select name="role" class="w-full border rounded px-3 py-2" required>
                <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="pt-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Create User
            </button>
        </div>
    </form>
</div>
@endsection
