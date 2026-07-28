@extends('layouts.default')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-10" x-data="guardsIndexColumns()">

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

        {{-- Left: per-page + column toggles --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="q" value="{{ request('q') }}">
                <input type="hidden" name="by" value="{{ request('by', 'all') }}">

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

            {{-- Column toggle --}}
            <div class="relative" @click.away="open = false">
                <button type="button"
                        @click="open = !open"
                        class="inline-flex items-center gap-2 rounded border bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Columns
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-show="open"
                     x-transition
                     class="absolute left-0 z-20 mt-2 w-72 rounded-lg border bg-white shadow-lg p-3"
                     style="display: none;">
                    <div class="mb-2 text-sm font-semibold text-gray-800">Visible columns</div>
                    <div class="space-y-2 max-h-80 overflow-auto">

                        <label class="flex items-center justify-between gap-3 text-sm">
                            <span>Name</span>
                            <input type="checkbox"
                                   checked
                                   disabled
                                   class="rounded border-gray-300 text-emerald-600 cursor-not-allowed opacity-60">
                        </label>

                        <template x-for="col in toggleableColumns" :key="col.key">
                            <label class="flex items-center justify-between gap-3 text-sm">
                                <span x-text="col.label"></span>
                                <input type="checkbox"
                                       class="rounded border-gray-300 text-emerald-600"
                                       :checked="isVisible(col.key)"
                                       @change="toggleColumn(col.key)">
                            </label>
                        </template>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <button type="button"
                                @click="resetDefaults()"
                                class="rounded border px-3 py-1.5 text-xs hover:bg-gray-50">
                            Reset default
                        </button>

                        <button type="button"
                                @click="showAll()"
                                class="rounded border px-3 py-1.5 text-xs hover:bg-gray-50">
                            Show all
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: search --}}
        <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-2">
            <input type="hidden" name="per_page" value="{{ request('per_page', 20) }}">

            <select name="by" class="border rounded px-2 py-2 text-sm bg-white">
                <option value="all" {{ request('by', 'all') === 'all' ? 'selected' : '' }}>All</option>
                <option value="name" {{ request('by') === 'name' ? 'selected' : '' }}>Name</option>
                <option value="license" {{ request('by') === 'license' ? 'selected' : '' }}>License #</option>
                <option value="email" {{ request('by') === 'email' ? 'selected' : '' }}>Email</option>
            </select>

            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Search guards…"
                   class="border rounded px-3 py-2 text-sm bg-white w-full sm:w-72" />

            <button type="submit"
                    class="px-4 py-2 rounded bg-gray-900 text-white text-sm hover:bg-black">
                Search
            </button>

            @if(request('q'))
                <a href="{{ route('security-guards.index', ['per_page' => request('per_page', 20)]) }}"
                   class="px-3 py-2 rounded border text-sm hover:bg-gray-50">
                    Clear
                </a>
            @endif
        </form>

    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        {{-- TOP SCROLLBAR (synced) --}}
        <div class="overflow-x-auto border-b border-gray-100" id="guards-scroll-top">
            <div class="h-4" id="guards-scroll-top-inner"></div>
        </div>

        {{-- Actual table scroll --}}
        <div class="overflow-x-auto" id="guards-scroll-bottom">
            <table class="min-w-[1000px] w-full text-sm text-left" id="guards-table">
                <thead class="bg-gray-100 sticky top-0 z-10">
                    <tr class="text-gray-700">
                        <th x-show="isVisible('row_no')" class="px-3 py-3 whitespace-nowrap">#</th>
                        <th class="px-3 py-3 whitespace-nowrap">Name</th>
                        <th x-show="isVisible('email')" class="px-3 py-3 whitespace-nowrap">Email</th>
                        <th x-show="isVisible('phone')" class="px-3 py-3 whitespace-nowrap">Phone</th>
                        <th x-show="isVisible('license_number')" class="px-3 py-3 whitespace-nowrap">License #</th>
                        <th x-show="isVisible('license_expiry')" class="px-3 py-3 whitespace-nowrap">Exp</th>
                        <th x-show="isVisible('category')" class="px-3 py-3 whitespace-nowrap">Category</th>
                        <th x-show="isVisible('account_details')" class="px-3 py-3 whitespace-nowrap">Account Details</th>
                        <th x-show="isVisible('visa')" class="px-3 py-3 whitespace-nowrap">Visa</th>
                        <th x-show="isVisible('city')" class="px-3 py-3 whitespace-nowrap">City</th>
                        <th x-show="isVisible('docs')" class="px-3 py-3 text-center whitespace-nowrap">Docs</th>
                        <th x-show="isVisible('actions')" class="px-3 py-3 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($securityGuards as $guard)
                        <tr class="hover:bg-gray-50">

                            <td x-show="isVisible('row_no')" class="px-3 py-3 whitespace-nowrap text-gray-600">
                                {{ ($securityGuards->firstItem() ?? 0) + $loop->index }}
                            </td>

                            <td class="px-3 py-3 font-medium whitespace-nowrap">
                                {{ $guard->fullname }}
                            </td>

                            <td x-show="isVisible('email')" class="px-3 py-3">
                                {{ $guard->email_address }}
                            </td>

                            <td x-show="isVisible('phone')" class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->phone_number }}
                            </td>

                            <td x-show="isVisible('license_number')" class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->license_number }}
                            </td>

                            <td x-show="isVisible('license_expiry')" class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->license_exp_date }}
                            </td>

                            <td x-show="isVisible('category')" class="px-3 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 rounded bg-gray-100 text-gray-800">
                                    {{ $guard->category }}
                                </span>
                            </td>

                            <td x-show="isVisible('account_details')" class="px-3 py-3 text-xs leading-5 whitespace-nowrap">
                                <div>
                                    <span class="text-gray-500">SC:</span>
                                    {{ $guard->sort_code ?? '-' }}
                                </div>

                                <div>
                                    <span class="text-gray-500">ACC:</span>
                                    {{ $guard->account_number ?? '-' }}
                                    @if(!empty($guard->beneficiary_name))
                                        <span class="text-gray-400">—</span>
                                        <span class="text-gray-700">{{ $guard->beneficiary_name }}</span>
                                    @endif
                                </div>
                            </td>

                            <td x-show="isVisible('visa')" class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->visa_status }}
                            </td>

                            <td x-show="isVisible('city')" class="px-3 py-3 whitespace-nowrap">
                                {{ $guard->city }}
                            </td>

                            <td x-show="isVisible('docs')" class="px-3 py-3 text-center">
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

                            <td x-show="isVisible('actions')" class="px-3 py-3 text-right whitespace-nowrap">
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
                            <td colspan="12" class="px-6 py-10 text-center text-gray-500">
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

<script>
function guardsIndexColumns() {
    const storageKey = 'guards_index_visible_columns_v1';

    const defaultVisible = [
        'license_number',
        'city',
        'actions',
    ];

    const toggleableColumns = [
        { key: 'row_no', label: '#' },
        { key: 'email', label: 'Email' },
        { key: 'phone', label: 'Phone' },
        { key: 'license_number', label: 'License #' },
        { key: 'license_expiry', label: 'Expiry' },
        { key: 'category', label: 'Category' },
        { key: 'account_details', label: 'Account Details' },
        { key: 'visa', label: 'Visa' },
        { key: 'city', label: 'City' },
        { key: 'docs', label: 'Docs' },
        { key: 'actions', label: 'Actions' },
    ];

    let stored = [];
    try {
        stored = JSON.parse(localStorage.getItem(storageKey) || '[]');
    } catch (e) {
        stored = [];
    }

    return {
        open: false,
        toggleableColumns,
        visible: Array.isArray(stored) && stored.length ? stored : defaultVisible,

        isVisible(key) {
            return this.visible.includes(key);
        },

        toggleColumn(key) {
            if (this.isVisible(key)) {
                this.visible = this.visible.filter(col => col !== key);
            } else {
                this.visible.push(key);
            }
            this.save();
            this.$nextTick(() => window.dispatchEvent(new Event('resize')));
        },

        resetDefaults() {
            this.visible = [...defaultVisible];
            this.save();
            this.$nextTick(() => window.dispatchEvent(new Event('resize')));
        },

        showAll() {
            this.visible = this.toggleableColumns.map(col => col.key);
            this.save();
            this.$nextTick(() => window.dispatchEvent(new Event('resize')));
        },

        save() {
            localStorage.setItem(storageKey, JSON.stringify(this.visible));
        }
    }
}

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

    // extra safety for Alpine x-show column width changes
    setTimeout(syncWidths, 50);
    setTimeout(syncWidths, 200);
    setTimeout(syncWidths, 500);
});
</script>
@endsection