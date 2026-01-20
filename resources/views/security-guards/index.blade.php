@extends('layouts.default')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-10">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Security Guards</h1>
            <p class="text-sm text-gray-500">
                Showing {{ $securityGuards->total() }} total guards
            </p>
        </div>

        @can('guards.create')
            <a href="{{ route('security-guards.create') }}"
               class="inline-flex items-center justify-center bg-emerald-600 text-white font-semibold px-4 py-2 rounded-md transition hover:bg-emerald-700">
                + Add Guard
            </a>
        @endcan
    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Controls --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Show</label>

            <select name="per_page"
                    onchange="this.form.submit()"
                    class="border rounded px-2 py-2 text-sm bg-white">
                @foreach([10,20,30,40,50] as $size)
                    <option value="{{ $size }}" {{ request('per_page', 20) == $size ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                @endforeach
            </select>

            <span class="text-sm text-gray-600">per page</span>
        </form>

        <div class="text-sm text-gray-500">
            Page {{ $securityGuards->currentPage() }} of {{ $securityGuards->lastPage() }}
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        {{-- TOP SCROLLBAR (synced) --}}
        <div class="overflow-x-auto border-b border-gray-100" id="guards-scroll-top">
            <div class="h-4" id="guards-scroll-top-inner"></div>
        </div>

        {{-- Actual table scroll --}}
        <div class="overflow-x-auto" id="guards-scroll-bottom">
            <table class="min-w-[1900px] w-full text-sm text-left" id="guards-table">
                <thead class="bg-gray-100 sticky top-0 z-10">
                    <tr class="text-gray-700">
                        <th class="px-3 py-3 whitespace-nowrap">Name</th>
                        <th class="px-3 py-3 whitespace-nowrap">Email</th>
                        <th class="px-3 py-3 whitespace-nowrap">Phone</th>
                        <th class="px-3 py-3 whitespace-nowrap">License #</th>
                        <th class="px-3 py-3 whitespace-nowrap">Exp</th>
                        <th class="px-3 py-3 whitespace-nowrap">Category</th>
                        <th class="px-3 py-3 whitespace-nowrap">Account Details</th>
                        <th class="px-3 py-3 whitespace-nowrap">Visa</th>
                        <th class="px-3 py-3 whitespace-nowrap">City</th>
                        <th class="px-3 py-3 text-center whitespace-nowrap">Docs</th>
                        <th class="px-3 py-3 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($securityGuards as $guard)
                        <tr class="hover:bg-gray-50">

                            <td class="px-3 py-3 font-medium whitespace-nowrap">
                                {{ $guard->fullname }}
                            </td>

                            <td class="px-3 py-3">
                                {{ $guard->email_address }}
                            </td>

                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->phone_number }}
                            </td>

                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->license_number }}
                            </td>

                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->license_exp_date }}
                            </td>

                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 rounded bg-gray-100 text-gray-800">
                                    {{ $guard->category }}
                                </span>
                            </td>

                            {{-- Account Details --}}
                            <td class="px-3 py-3 text-xs leading-5 whitespace-nowrap">
                                <div>
                                    <span class="text-gray-500">SC:</span>
                                    {{ $guard->sort_code ?? '-' }}
                                </div>
                                <div>
                                    <span class="text-gray-500">ACC:</span>
                                    {{ $guard->account_number ?? '-' }}
                                </div>
                            </td>

                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->visa_status }}
                            </td>

                            <td class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->city }}
                            </td>

                            {{-- Docs --}}
                            <td class="px-3 py-3 text-center">
                                <div class="flex justify-center flex-wrap gap-2 text-xs">
                                    @php
                                        $docs = [
                                            'sia_license' => 'SIA',
                                            'passport'    => 'Pass',
                                            'rtw_ss'      => 'RTW',
                                            'ni_letter'   => 'NI',
                                        ];
                                    @endphp

                                    @foreach($docs as $docField => $docLabel)
                                        @if($guard->$docField)
                                            <a href="{{ Storage::disk('public')->url($guard->$docField) }}"
                                               target="_blank"
                                               class="inline-flex items-center rounded bg-emerald-50 px-2 py-1 text-emerald-700 hover:underline">
                                                {{ $docLabel }}
                                            </a>
                                        @endif
                                    @endforeach

                                    @if(!collect(array_keys($docs))->first(fn($d) => $guard->$d))
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('security-guards.show', $guard->id) }}"
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-xs transition">
                                        View
                                    </a>

                                    @can('guards.edit')
                                        <a href="{{ route('security-guards.edit', $guard->id) }}"
                                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-xs transition">
                                            Edit
                                        </a>
                                    @endcan

                                    @can('guards.delete')
                                        <form action="{{ route('security-guards.destroy', $guard->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Delete this guard?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-xs transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-10 text-center text-gray-500">
                                No security guards found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $securityGuards->links() }}
    </div>

</div>

{{-- Sync top/bottom horizontal scroll --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const top = document.getElementById('guards-scroll-top');
    const topInner = document.getElementById('guards-scroll-top-inner');
    const bottom = document.getElementById('guards-scroll-bottom');
    const table = document.getElementById('guards-table');

    if (!top || !topInner || !bottom || !table) return;

    const syncWidths = () => {
        topInner.style.width = table.scrollWidth + 'px';
    };

    syncWidths();

    // Recompute on resize/fonts/layout changes
    window.addEventListener('resize', syncWidths);

    let syncing = false;

    top.addEventListener('scroll', () => {
        if (syncing) return;
        syncing = true;
        bottom.scrollLeft = top.scrollLeft;
        syncing = false;
    });

    bottom.addEventListener('scroll', () => {
        if (syncing) return;
        syncing = true;
        top.scrollLeft = bottom.scrollLeft;
        syncing = false;
    });
});
</script>
@endsection
