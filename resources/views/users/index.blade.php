@extends('layouts.default')

@section('content')
<div class="container mx-auto p-4 max-w-6xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold">Users</h2>
            <p class="text-sm text-gray-600">Super Admin only</p>
        </div>

        <a href="{{ route('users.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Create User
        </a>
    </div>

    {{-- Success --}}
    @if (session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error --}}
    @if (session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow rounded overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="text-left px-4 py-3">ID</th>
                    <th class="text-left px-4 py-3">Name</th>
                    <th class="text-left px-4 py-3">Username</th>
                    <th class="text-left px-4 py-3">Role(s)</th>
                    <th class="text-left px-4 py-3">Created</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse ($users as $u)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $u->id }}</td>

                        <td class="px-4 py-3 font-medium">
                            {{ $u->name }}
                            @if (auth()->id() === $u->id)
                                <span class="ml-2 text-xs text-gray-500">(You)</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">{{ $u->email }}</td>

                        <td class="px-4 py-3">
                            {{ $u->roles->pluck('name')->join(', ') ?: '-' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ optional($u->created_at)->format('Y-m-d H:i') }}
                        </td>

                        <td class="px-4 py-3">
                            <form method="POST"
                                  action="{{ route('users.destroy', $u) }}"
                                  onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 disabled:opacity-40"
                                        {{ auth()->id() === $u->id ? 'disabled' : '' }}>
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
