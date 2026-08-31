@extends('layouts.default')

@section('title', 'Event Summary Reports')

@section('content')
<div class="max-w-7xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Event Summary Reports</h1>
        <p class="text-sm text-gray-500">Overview of all events</p>
    </div>

    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left">Event</th>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-center">Days</th>
                    <th class="px-4 py-3 text-center">Guards</th>
                    <th class="px-4 py-3 text-center">Shifts</th>
                    <th class="px-4 py-3 text-center">Hours</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse($events as $event)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">
                            {{ $event['event_name'] }}
                            <div class="text-xs text-gray-500">
                                {{ $event['start_date'] }} → {{ $event['end_date'] }}
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if(!empty($event['client_color']))
                                    <span class="inline-block h-3 w-3 shrink-0 rounded-full" style="background: {{ $event['client_color'] }};"></span>
                                @endif
                                <span>{{ $event['client_name'] }}</span>
                            </div>
                        </td>

                        <td class="px-4 py-3 text-center">
                            {{ $event['total_days'] }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            {{ $event['total_guards'] }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            {{ $event['total_shifts'] }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            {{ $event['total_hours'] }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('reports.events.show', $event['id']) }}"
                               class="text-emerald-600 hover:underline text-sm">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                            No events found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
