@extends('layouts.default')

@section('title', 'Guard Reports')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Guard Reports</h1>
            <p class="text-sm text-gray-500">Select a guard and an event to generate the payroll-style report.</p>
        </div>
    </div>

    <!-- Picker Card -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <form method="GET"
              action="#"
              x-data="{ guardId: '', eventId: '' }"
              @submit.prevent="
                if(!guardId || !eventId) return;
                window.location.href = `{{ url('/reports/guards') }}/${guardId}/events/${eventId}`;
              "
              class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Guard</label>
                <select x-model="guardId"
                        class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Select a guard</option>
                    @foreach($guards as $g)
                        <option value="{{ $g->id }}">{{ $g->fullname }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Event</label>
                <select x-model="eventId"
                        class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Select an event</option>
                    @foreach($events as $e)
                        <option value="{{ $e->id }}">
                            {{ $e->event_name }} ({{ $e->start_date }} → {{ $e->end_date }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="w-full md:w-auto inline-flex items-center justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">
                    Generate Report
                </button>

                <a href="{{ route('reports.events.index') }}"
                   class="w-full md:w-auto inline-flex items-center justify-center rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                    Event Reports
                </a>
            </div>

            <div class="md:col-span-3 text-xs text-gray-500">
                Tip: If you don’t know the event, use “Recent Assignments” below.
            </div>

        </form>
    </div>

    <!-- Recent Assignments -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-900">Recent Assignments</h2>
            <p class="text-sm text-gray-500">Fast access to the latest guard + event combinations.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm table-fixed">
                <colgroup>
                    <col class="w-4/12">
                    <col class="w-5/12">
                    <col class="w-2/12">
                    <col class="w-1/12">
                </colgroup>

                <thead class="bg-white text-gray-700 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Guard</th>
                        <th class="px-5 py-3 text-left font-semibold">Event</th>
                        <th class="px-5 py-3 text-center font-semibold">Last Worked</th>
                        <th class="px-5 py-3 text-right font-semibold">Open</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($recent as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">
                                {{ $r['guard_name'] }}
                            </td>
                            <td class="px-5 py-3 text-gray-900">
                                <div class="font-medium">{{ $r['event_name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $r['event_dates'] }}</div>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-700">
                                {{ $r['last_worked_date'] }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('reports.guards.show', ['guard' => $r['guard_id'], 'event' => $r['event_id']]) }}"
                                   class="text-emerald-700 font-semibold hover:underline">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-gray-500">
                                No shift data found yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
