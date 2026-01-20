@extends('layouts.default')

@section('title', 'Event Summary Report')

@section('content')
<div class="max-w-5xl mx-auto">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">
            Event Summary Report
        </h1>
        <p class="text-sm text-gray-500">
            {{ $event->event_name }}
        </p>
    </div>

    <!-- Summary Card -->
    <div class="bg-white shadow rounded-lg p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">

        <div>
            <div class="text-xs text-gray-500">Event Name</div>
            <div class="font-medium">{{ $event->event_name }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Client</div>
            <div class="font-medium">
                {{ optional($event->client)->name ?? '—' }}
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Event Dates</div>
            <div class="font-medium">
                {{ $event->start_date }} → {{ $event->end_date }}
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Total Event Days</div>
            <div class="font-medium">{{ $totalDays }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Total Guards</div>
            <div class="font-medium">{{ $totalGuards }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Total Shifts</div>
            <div class="font-medium">{{ $totalShifts }}</div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Total Working Hours</div>
            <div class="font-medium">{{ $totalHours }}</div>
        </div>

    </div>

    <!-- Actions -->
    <div class="mt-6 flex gap-3">
        <a href="{{ route('reports.events.detailed', $event->id) }}"
           class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            View Detailed Report
        </a>

        <a href="{{ route('reports.events.index') }}"
           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium">
            Back to List
        </a>
    </div>

</div>
@endsection
