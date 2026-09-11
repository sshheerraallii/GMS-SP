@extends('layouts.default')

@section('title', 'Executive')

@section('content')
<style>[x-cloak]{ display:none !important; }</style>

<div class="max-w-7xl mx-auto mt-6 space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Executive Dashboard</h1>
        <p class="text-sm text-gray-500">
            Company-wide profitability by date range: charge &minus; pay &minus; expenses. Cancelled shifts count as zero.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
        <div>
            <label class="mb-1 block text-xs text-gray-600">From</label>
            <input type="date" name="date_from" value="{{ $from }}" class="rounded border border-gray-300 px-2 py-1">
        </div>
        <div>
            <label class="mb-1 block text-xs text-gray-600">To</label>
            <input type="date" name="date_to" value="{{ $to }}" class="rounded border border-gray-300 px-2 py-1">
        </div>
        <div>
            <label class="mb-1 block text-xs text-gray-600">View</label>
            <select name="group" class="rounded border border-gray-300 px-2 py-1">
                <option value="event"  {{ ($group ?? 'event') === 'event'  ? 'selected' : '' }}>Event-wise</option>
                <option value="client" {{ ($group ?? 'event') === 'client' ? 'selected' : '' }}>Client-wise</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs text-gray-600">Client (adds a second section)</label>
            <select name="client_id" class="rounded border border-gray-300 px-2 py-1">
                <option value="">&mdash; none &mdash;</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ (string) $clientId === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Apply</button>
    </form>

    @include('executive.partials.section', [
        'title' => 'Company-wide',
        'data'  => $system,
        'group' => $group ?? 'event',
    ])

    @if($client)
        @include('executive.partials.section', [
            'title' => 'Client: ' . (optional($clients->firstWhere('id', $clientId))->name ?? ''),
            'data'  => $client,
            'group' => 'event',
        ])
    @endif
</div>
@endsection
