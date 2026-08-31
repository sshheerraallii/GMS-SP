@extends('layouts.default')

@section('title', 'Guard Statement')

@section('content')
<div class="max-w-3xl mx-auto mt-8 space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Guard Statement</h1>
        <p class="text-sm text-gray-500">
            Generate an on-demand PDF for one guard over a date range. Each shift is valued at
            its event's pay rate, with a grand total. Nothing is saved.
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="GET" action="{{ route('reports.guardStatement.download') }}"
          class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">

        {{-- Guard (required) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Guard <span class="text-red-500">*</span>
            </label>
            <select name="guard_id" required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-800">
                <option value="">Select a guard…</option>
                @foreach($guards as $g)
                    <option value="{{ $g->id }}" {{ (string) old('guard_id') === (string) $g->id ? 'selected' : '' }}>
                        {{ $g->fullname }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Date range --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date from</label>
                <input type="date" name="date_from" value="{{ old('date_from') }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-800">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date to</label>
                <input type="date" name="date_to" value="{{ old('date_to') }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-800">
            </div>
        </div>
        <p class="-mt-3 text-xs text-gray-400">Leave the dates blank to include all dates.</p>

        {{-- Clients / Events (multi-select; empty = all) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Clients</label>
                <select name="client_ids[]" multiple size="6"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-800">
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-400">Select none for all clients (Ctrl/Cmd-click for multiple).</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Events</label>
                <select name="event_ids[]" multiple size="6"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-800">
                    @foreach($events as $e)
                        <option value="{{ $e->id }}">{{ $e->event_name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-400">Select none for all events.</p>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="rounded-md bg-gray-900 px-5 py-2 text-sm font-semibold text-white hover:bg-black">
                Generate PDF
            </button>
            <a href="{{ route('reports.home') }}"
               class="rounded-md border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Back
            </a>
        </div>
    </form>
</div>
@endsection
