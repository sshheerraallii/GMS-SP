@extends('layouts.default')

@section('title', 'ChaseUp')

@section('content')

<div
    x-data="chaseupPage({
        canViewAudit: @js($canViewAudit),
        routes: {
            updateFieldBase: @js(url('/chaseup/shift')),
            latestActorBase: @js(url('/chaseup/shift')),
            historyBase: @js(url('/chaseup/shift')),
        }
    })"
    x-init="init()"
>
    <div class="max-w-[1850px] mx-auto px-4">
        {{-- HEADER --}}
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">ChaseUp</h1>
                <div class="text-sm text-gray-500">
                    {{ \Carbon\Carbon::parse($selectedDate)->format('l, d M Y') }}
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="text-sm text-gray-600">
                    <span class="font-semibold text-red-600">{{ $rows->where('is_missing', true)->count() }}</span>
                    Missing
                </div>

                <button
                    type="button"
                    @click="saveAllDirty()"
                    :disabled="savingAll"
                    class="inline-flex items-center rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:opacity-60"
                >
                    <span x-show="!savingAll">Save Entries</span>
                    <span x-show="savingAll">Saving...</span>
                </button>
            </div>
        </div>

        {{-- FILTERS --}}
        <form method="GET" class="mb-4 rounded-xl border bg-white p-3">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Date</label>
                    <input
                        type="date"
                        name="date"
                        value="{{ request('date', $selectedDate) }}"
                        class="rounded-lg border px-3 py-2 text-sm"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Client</label>
                    <select name="client_id" class="rounded-lg border px-3 py-2 text-sm">
                        <option value="">All Clients</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" @selected((string) request('client_id') === (string) $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(isset($events))
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Event</label>
                        <select name="event_id" class="rounded-lg border px-3 py-2 text-sm">
                            <option value="">All Events</option>
                            @foreach($events as $e)
                                <option value="{{ $e->id }}" @selected((string) request('event_id') === (string) $e->id)>
                                    {{ $e->event_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Guard</label>
                    <select name="guard_id" class="rounded-lg border px-3 py-2 text-sm">
                        <option value="">All Guards</option>
                        @foreach($guards as $g)
                            <option value="{{ $g->id }}" @selected((string) request('guard_id') === (string) $g->id)>
                                {{ $g->fullname }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-medium text-gray-600">All Ok?</label>
                    <select name="all_ok" class="rounded-lg border px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="yes" @selected(request('all_ok') === 'yes')>Yes</option>
                        <option value="no" @selected(request('all_ok') === 'no')>No</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">On Way?</label>
                    <select name="on_way" class="rounded-lg border px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="yes" @selected(request('on_way') === 'yes')>Yes</option>
                        <option value="no" @selected(request('on_way') === 'no')>No</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Missing Data</label>
                    <select name="missing_only" class="rounded-lg border px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="1" @selected(request('missing_only') === '1')>Missing Only</option>
                        <option value="0" @selected(request('missing_only') === '0')>Complete Only</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                        Apply
                    </button>

                    <a
                        href="{{ route('chaseup.index') }}"
                        class="rounded-lg border px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Reset
                    </a>
                </div>
            </div>
        </form>

        {{-- TABLE --}}
        <div class="overflow-hidden rounded-xl border bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-[2600px] w-full text-sm text-gray-700">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr class="border-b">
                            <th class="sticky left-0 z-30 min-w-[110px] border-r bg-gray-100 p-3 text-left font-semibold">Date</th>
                            <th class="min-w-[110px] p-3 text-left font-semibold">Day</th>
                            <th class="sticky left-[110px] z-30 min-w-[220px] border-r bg-gray-100 p-3 text-left font-semibold">Guard Name</th>
                            <th class="sticky left-[330px] z-30 min-w-[170px] border-r bg-gray-100 p-3 text-left font-semibold">Location</th>
                            <th class="min-w-[120px] p-3 text-left font-semibold">Start Time</th>
                            <th class="min-w-[120px] p-3 text-left font-semibold">End Time</th>
                            <th class="min-w-[120px] p-3 text-left font-semibold">Break Hours</th>
                            <th class="min-w-[120px] p-3 text-left font-semibold">Total Hours</th>
                            <th class="min-w-[220px] p-3 text-left font-semibold">Client Name</th>

                            <th class="min-w-[180px] p-3 text-left font-semibold">Reminder</th>
                            <th class="min-w-[120px] p-3 text-left font-semibold">All Ok?</th>
                            <th class="min-w-[190px] p-3 text-left font-semibold">All Ok Response</th>
                            <th class="min-w-[120px] p-3 text-left font-semibold">On Way?</th>
                            <th class="min-w-[190px] p-3 text-left font-semibold">On Way Response</th>
                            <th class="min-w-[140px] p-3 text-left font-semibold">Reaching Time</th>
                            <th class="min-w-[180px] p-3 text-left font-semibold">Book On</th>
                            <th class="min-w-[260px] p-3 text-left font-semibold">Comments</th>

                            @if($canViewAudit)
                                <th class="min-w-[150px] p-3 text-left font-semibold">Actor</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $allOkNo = ($row['all_ok'] ?? null) === 'no';
                                $onWayNo = ($row['on_way'] ?? null) === 'no';
                                $missingReach = blank($row['reaching_time'] ?? null);

                                $rowBg = 'bg-white';
                                $rowStyle = '';
                                if (!empty($row['is_cancelled'])) {
                                    $rowBg = 'bg-red-100';
                                } elseif (!empty($row['is_missing'])) {
                                    $rowBg = 'bg-red-50';
                                } elseif ($allOkNo || $onWayNo) {
                                    $rowBg = 'bg-amber-50';
                                } elseif (!empty($row['client_color'])) {
                                    // Client colour tints only otherwise-normal rows,
                                    // so operational red/amber signals are never masked.
                                    $rowBg = '';
                                    $rowStyle = 'background-color: ' . $row['client_color'] . '26;';
                                }
                            @endphp

                            <tr class="border-t {{ $rowBg }}" style="{{ $rowStyle }}">
                                <td class="sticky left-0 z-20 border-r bg-inherit p-3 align-top">
                                    {{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}
                                </td>

                                <td class="p-3 align-top">
                                    {{ \Carbon\Carbon::parse($row['date'])->format('D') }}
                                </td>

                                <td class="sticky left-[110px] z-20 border-r bg-inherit p-3 align-top">
                                    <div class="font-semibold text-gray-900">
                                        {{ $row['guard_name'] ?? '-' }}
                                    </div>
                                    @if(!empty($row['is_cancelled']))
                                        <span class="mt-1 inline-flex items-center rounded-full bg-red-200 px-2 py-0.5 text-[11px] font-semibold text-red-800">Cancelled</span>
                                    @endif
                                </td>

                                <td class="sticky left-[330px] z-20 border-r bg-inherit p-3 align-top">
                                    {{ $row['location'] ?? '-' }}
                                </td>

                                <td class="p-3 align-top">
                                    {{ $row['start_time'] ?? '-' }}
                                </td>

                                <td class="p-3 align-top">
                                    {{ $row['end_time'] ?? '-' }}
                                </td>

                                <td class="p-3 align-top">
                                    {{ isset($row['break_hours']) && $row['break_hours'] !== '' ? $row['break_hours'] : '-' }}
                                </td>

                                <td class="p-3 align-top">
                                    {{ isset($row['total_hours']) && $row['total_hours'] !== '' ? $row['total_hours'] : '-' }}
                                </td>

                                <td class="p-3 align-top">
                                    <div class="font-medium text-gray-900">
                                        {{ $row['client_name'] ?? '-' }}
                                    </div>
                                    @if(!empty($row['event_name']))
                                        <div class="mt-0.5 text-xs text-gray-500">
                                            {{ $row['event_name'] }}
                                        </div>
                                    @endif
                                </td>

                                <td class="p-2 align-top">
                                    <input
                                        type="text"
                                        class="w-full rounded-lg border px-3 py-2 text-sm"
                                        data-field="reminder"
                                        data-shift-id="{{ $row['shift_id'] }}"
                                        value="{{ $row['reminder'] ?? '' }}"
                                        @input="markDirty($event)"
                                        @blur="queueFieldSave($event)"
                                    >
                                </td>

                                <td class="p-2 align-top">
                                    <select
                                        class="min-w-[110px] w-[110px] rounded-lg border px-3 py-2 text-sm {{ $allOkNo ? 'border-amber-400 bg-amber-50' : '' }}"
                                        data-field="all_ok"
                                        data-shift-id="{{ $row['shift_id'] }}"
                                        @change="markDirty($event); queueFieldSave($event)"
                                    >
                                        <option value="" @selected(blank($row['all_ok'] ?? null))>-</option>
                                        <option value="yes" @selected(($row['all_ok'] ?? null) === 'yes')>Yes</option>
                                        <option value="no" @selected(($row['all_ok'] ?? null) === 'no')>No</option>
                                    </select>
                                </td>

                                <td class="p-2 align-top">
                                    <input
                                        type="text"
                                        class="w-full rounded-lg border px-3 py-2 text-sm"
                                        data-field="all_ok_response"
                                        data-shift-id="{{ $row['shift_id'] }}"
                                        value="{{ $row['all_ok_response'] ?? '' }}"
                                        @input="markDirty($event)"
                                        @blur="queueFieldSave($event)"
                                    >
                                </td>

                                <td class="p-2 align-top">
                                    <select
                                        class="min-w-[110px] w-[110px] rounded-lg border px-3 py-2 text-sm {{ $onWayNo ? 'border-amber-400 bg-amber-50' : '' }}"
                                        data-field="on_way"
                                        data-shift-id="{{ $row['shift_id'] }}"
                                        @change="markDirty($event); queueFieldSave($event)"
                                    >
                                        <option value="" @selected(blank($row['on_way'] ?? null))>-</option>
                                        <option value="yes" @selected(($row['on_way'] ?? null) === 'yes')>Yes</option>
                                        <option value="no" @selected(($row['on_way'] ?? null) === 'no')>No</option>
                                    </select>
                                </td>

                                <td class="p-2 align-top">
                                    <input
                                        type="text"
                                        class="w-full rounded-lg border px-3 py-2 text-sm"
                                        data-field="on_way_response"
                                        data-shift-id="{{ $row['shift_id'] }}"
                                        value="{{ $row['on_way_response'] ?? '' }}"
                                        @input="markDirty($event)"
                                        @blur="queueFieldSave($event)"
                                    >
                                </td>

                                <td class="p-2 align-top">
                                    <input
                                        type="time"
                                        class="w-full rounded-lg border px-3 py-2 text-sm {{ $missingReach ? 'border-red-300 bg-red-50' : '' }}"
                                        data-field="reaching_time"
                                        data-shift-id="{{ $row['shift_id'] }}"
                                        value="{{ $row['reaching_time'] ?? '' }}"
                                        @input="markDirty($event)"
                                        @blur="queueFieldSave($event)"
                                    >
                                </td>

                                <td class="p-2 align-top">
                                    <input
                                        type="text"
                                        class="w-full rounded-lg border px-3 py-2 text-sm"
                                        data-field="book_on"
                                        data-shift-id="{{ $row['shift_id'] }}"
                                        value="{{ $row['book_on'] ?? '' }}"
                                        @input="markDirty($event)"
                                        @blur="queueFieldSave($event)"
                                    >
                                </td>

                                <td class="p-2 align-top">
                                    <input
                                        type="text"
                                        class="w-full rounded-lg border px-3 py-2 text-sm"
                                        data-field="comments"
                                        data-shift-id="{{ $row['shift_id'] }}"
                                        value="{{ $row['comments'] ?? '' }}"
                                        @input="markDirty($event)"
                                        @blur="queueFieldSave($event)"
                                    >
                                </td>

                                @if($canViewAudit)
                                    <td class="p-2 align-top">
                                        <button
                                            type="button"
                                            class="rounded-lg border px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                            @click="openAudit({{ $row['shift_id'] }})"
                                        >
                                            Actor
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canViewAudit ? 18 : 17 }}" class="p-8 text-center text-sm text-gray-500">
                                    No ChaseUp rows found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TOAST --}}
        <div
            x-show="toast.show"
            x-transition
            class="fixed bottom-5 right-5 z-50 rounded-lg bg-gray-900 px-4 py-3 text-sm font-medium text-white shadow-lg"
            x-text="toast.message"
            x-cloak
        ></div>
    </div>

    {{-- AUDIT MODAL --}}
   
  <template x-teleport="body">
    <div
        x-show="audit.open"
        x-transition.opacity
        x-cloak
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50" @click="closeAudit()"></div>

        {{-- Modal --}}
        <div
            @click.stop
            x-transition.scale.90
            class="relative z-10 w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-gray-200 overflow-hidden"
            style="max-height: 85vh;"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b bg-white px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Actor & Audit History</h3>
                    <p class="mt-1 text-xs text-gray-500" x-text="'Shift ID: ' + (audit.shiftId ?? '-')"></p>
                </div>

                <button
                    type="button"
                    @click="closeAudit()"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Close
                </button>
            </div>

            {{-- Scrollable Body --}}
            <div class="overflow-y-auto px-5 py-4" style="max-height: calc(85vh - 73px);">
                <template x-if="audit.loading">
                    <div class="py-10 text-center text-sm text-gray-500">Loading...</div>
                </template>

                <template x-if="!audit.loading">
                    <div class="space-y-4">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                                Latest Actor
                            </div>

                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <div class="font-medium text-gray-900" x-text="audit.latest.actor_name || '-'"></div>
                                <div class="text-sm text-gray-500" x-text="audit.latest.updated_at || '-'"></div>
                            </div>
                        </div>

                        <template x-if="!audit.history.length">
                            <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-500">
                                No audit history found.
                            </div>
                        </template>

                        <div class="space-y-3" x-show="audit.history.length">
                            <template x-for="(item, index) in audit.history" :key="index">
                                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                    <div class="mb-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="font-medium text-gray-900" x-text="item.actor_name || 'System'"></div>
                                        <div class="text-sm text-gray-500" x-text="item.created_at || '-'"></div>
                                    </div>

                                    <div class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500" x-text="item.action || 'update'"></div>

                                    <div class="grid gap-3 text-sm md:grid-cols-3">
                                        <div>
                                            <div class="mb-1 text-xs font-medium text-gray-400">Field</div>
                                            <div class="break-words text-gray-800" x-text="item.field_name || '-'"></div>
                                        </div>
                                        <div>
                                            <div class="mb-1 text-xs font-medium text-gray-400">Old</div>
                                            <div class="break-words text-gray-800" x-text="item.old_value ?? '-'"></div>
                                        </div>
                                        <div>
                                            <div class="mb-1 text-xs font-medium text-gray-400">New</div>
                                            <div class="break-words text-gray-800" x-text="item.new_value ?? '-'"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>  
    
</div>

<script>
function chaseupPage(config) {
    return {
        dirtyMap: {},
        savingAll: false,

        toast: {
            show: false,
            message: ''
        },

        audit: {
            open: false,
            loading: false,
            shiftId: null,
            latest: {},
            history: []
        },

        // ---- Excel-style filter/sort engine (client-side only) ----
        _cf: {
            ready: false,
            table: null,
            thead: null,
            tbody: null,
            popover: null,
            filters: {},        // colIndex -> Set of allowed raw values (absent = no filter)
            sort: { col: null, dir: null },
            buttons: {},        // colIndex -> header button element
            activeCol: null,
        },

        init() {
            this.$nextTick(() => this.cfInit());
        },

        // ---------- existing editing behaviour (unchanged) ----------
        markDirty(e) {
            const el = e.target;
            const shiftId = el.dataset.shiftId;
            const field = el.dataset.field;

            if (!shiftId || !field) return;

            if (!this.dirtyMap[shiftId]) {
                this.dirtyMap[shiftId] = {};
            }

            this.dirtyMap[shiftId][field] = el.value;
        },

        async queueFieldSave(e) {
            const el = e.target;
            const shiftId = el.dataset.shiftId;
            const field = el.dataset.field;

            if (!shiftId || !field) return;

            try {
                const response = await fetch(`${config.routes.updateFieldBase}/${shiftId}/field`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        field: field,
                        value: el.value
                    })
                });

                const data = await response.json();

                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Save failed');
                }

                if (this.dirtyMap[shiftId] && this.dirtyMap[shiftId][field] !== undefined) {
                    delete this.dirtyMap[shiftId][field];

                    if (Object.keys(this.dirtyMap[shiftId]).length === 0) {
                        delete this.dirtyMap[shiftId];
                    }
                }

                this.showToast('Saved');
            } catch (error) {
                this.showToast(error.message || 'Unable to save');
            }
        },

        async saveAllDirty() {
            const entries = Object.entries(this.dirtyMap);

            if (!entries.length) {
                this.showToast('Nothing pending');
                return;
            }

            this.savingAll = true;

            try {
                for (const [shiftId, fields] of entries) {
                    for (const [field, value] of Object.entries(fields)) {
                        const response = await fetch(`${config.routes.updateFieldBase}/${shiftId}/field`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                field: field,
                                value: value
                            })
                        });

                        const data = await response.json();

                        if (!response.ok || data.success === false) {
                            throw new Error(data.message || `Save failed for shift ${shiftId}`);
                        }
                    }
                }

                this.dirtyMap = {};
                this.showToast('All entries saved');
            } catch (error) {
                this.showToast(error.message || 'Bulk save failed');
            } finally {
                this.savingAll = false;
            }
        },

        async openAudit(shiftId) {
            if (!config.canViewAudit) return;

            this.audit.open = true;
            this.audit.loading = true;
            this.audit.shiftId = shiftId;
            this.audit.latest = {};
            this.audit.history = [];

            try {
                const [latestRes, historyRes] = await Promise.all([
                    fetch(`${config.routes.latestActorBase}/${shiftId}/latest-actor`, {
                        headers: { 'Accept': 'application/json' }
                    }),
                    fetch(`${config.routes.historyBase}/${shiftId}/history`, {
                        headers: { 'Accept': 'application/json' }
                    }),
                ]);

                const latestData = latestRes.ok ? await latestRes.json() : {};
                const historyData = historyRes.ok ? await historyRes.json() : {};

                this.audit.latest = {
                    actor_name: latestData.actor_name || null,
                    updated_at: latestData.timestamp || latestData.updated_at || null,
                };

                const rawHistory = Array.isArray(historyData.history) ? historyData.history : [];

                this.audit.history = rawHistory.flatMap((log) => {
                    const changedFields = log.new_values && typeof log.new_values === 'object'
                        ? Object.keys(log.new_values)
                        : [];

                    if (!changedFields.length) {
                        return [{
                            action: log.action || 'update',
                            actor_name: log.actor_name || 'System',
                            created_at: log.created_at || '-',
                            field_name: '-',
                            old_value: '-',
                            new_value: '-',
                        }];
                    }

                    return changedFields.map((field) => ({
                        action: log.action || 'update',
                        actor_name: log.actor_name || 'System',
                        created_at: log.created_at || '-',
                        field_name: field,
                        old_value: (log.old_values && typeof log.old_values === 'object' && field in log.old_values)
                            ? String(log.old_values[field] ?? '-')
                            : '-',
                        new_value: (log.new_values && typeof log.new_values === 'object' && field in log.new_values)
                            ? String(log.new_values[field] ?? '-')
                            : '-',
                    }));
                });

                if (!latestRes.ok && !historyRes.ok) {
                    this.showToast('Unable to load audit');
                }
            } catch (error) {
                this.showToast(error.message || 'Unable to load audit');
            } finally {
                this.audit.loading = false;
            }
        },

        closeAudit() {
            this.audit.open = false;
            this.audit.loading = false;
            this.audit.shiftId = null;
            this.audit.latest = {};
            this.audit.history = [];
        },

        showToast(message) {
            this.toast.message = message;
            this.toast.show = true;

            clearTimeout(this.toast._timer);
            this.toast._timer = setTimeout(() => {
                this.toast.show = false;
            }, 1800);
        },

        // ---------- filter/sort engine ----------
        cfInit() {
            if (this._cf.ready) return;

            const root = this.$el;
            const table = root.querySelector('table');
            if (!table || !table.tHead || !table.tBodies.length) return;

            this._cf.table = table;
            this._cf.thead = table.tHead;
            this._cf.tbody = table.tBodies[0];

            this.cfInjectStyles();
            this.cfBuildPopover();
            this.cfBuildHeaderButtons();

            // Close popover on outside click / scroll / escape.
            document.addEventListener('mousedown', (ev) => {
                if (!this._cf.popover || this._cf.popover.style.display === 'none') return;
                const btn = this._cf.activeCol !== null ? this._cf.buttons[this._cf.activeCol] : null;
                if (this._cf.popover.contains(ev.target) || (btn && btn.contains(ev.target))) return;
                this.cfClosePopover();
            });
            document.addEventListener('keydown', (ev) => {
                if (ev.key === 'Escape') this.cfClosePopover();
            });
            const scroller = root.querySelector('.overflow-x-auto');
            if (scroller) scroller.addEventListener('scroll', () => this.cfClosePopover());
            window.addEventListener('scroll', () => this.cfClosePopover(), true);

            this._cf.ready = true;
        },

        cfInjectStyles() {
            if (document.getElementById('cf-styles')) return;
            const css = `
.cf-th-wrap{display:flex;align-items:center;justify-content:space-between;gap:6px;}
.cf-btn{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;
  width:20px;height:20px;border-radius:4px;border:1px solid transparent;background:transparent;
  font-size:11px;line-height:1;color:#6b7280;cursor:pointer;}
.cf-btn:hover{background:#e5e7eb;color:#111827;}
.cf-btn.cf-active{background:#111827;color:#fff;border-color:#111827;}
.cf-pop{position:fixed;z-index:9998;width:260px;max-width:calc(100vw - 16px);
  background:#fff;border:1px solid #e5e7eb;border-radius:12px;
  box-shadow:0 10px 30px rgba(0,0,0,.18);font-size:13px;color:#111827;overflow:hidden;}
.cf-pop .cf-sort{display:flex;gap:6px;padding:10px;border-bottom:1px solid #f0f0f0;}
.cf-pop .cf-sort button{flex:1;padding:6px 8px;border:1px solid #e5e7eb;border-radius:8px;
  background:#fff;cursor:pointer;font-weight:600;color:#374151;}
.cf-pop .cf-sort button:hover{background:#f9fafb;}
.cf-pop .cf-search{padding:8px 10px;border-bottom:1px solid #f0f0f0;}
.cf-pop .cf-search input{width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;}
.cf-pop .cf-list{max-height:220px;overflow:auto;padding:6px 10px;}
.cf-pop .cf-opt{display:flex;align-items:center;gap:8px;padding:3px 0;cursor:pointer;}
.cf-pop .cf-opt input{flex:0 0 auto;}
.cf-pop .cf-opt span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.cf-pop .cf-all{border-bottom:1px solid #f0f0f0;font-weight:600;}
.cf-pop .cf-foot{display:flex;gap:6px;padding:10px;border-top:1px solid #f0f0f0;}
.cf-pop .cf-foot button{flex:1;padding:6px 8px;border-radius:8px;cursor:pointer;font-weight:600;border:1px solid #e5e7eb;background:#fff;color:#374151;}
.cf-pop .cf-foot .cf-apply{background:#111827;color:#fff;border-color:#111827;}
.cf-pop .cf-foot .cf-apply:hover{background:#000;}
.cf-pop .cf-foot button:hover{background:#f9fafb;}
.cf-pop .cf-foot .cf-apply:hover{background:#000;}
`;
            const style = document.createElement('style');
            style.id = 'cf-styles';
            style.textContent = css;
            document.head.appendChild(style);
        },

        cfBuildHeaderButtons() {
            const ths = Array.from(this._cf.thead.querySelectorAll('th'));
            ths.forEach((th, col) => {
                const label = th.textContent.trim();
                if (label === 'Actor' || label === '') return; // skip non-data columns

                th.innerHTML = '';
                const wrap = document.createElement('div');
                wrap.className = 'cf-th-wrap';

                const span = document.createElement('span');
                span.textContent = label;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cf-btn';
                btn.textContent = '▾';
                btn.addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    this.cfOpenPopover(col, btn);
                });

                wrap.appendChild(span);
                wrap.appendChild(btn);
                th.appendChild(wrap);

                this._cf.buttons[col] = btn;
            });
        },

        cfDataRows() {
            return Array.from(this._cf.tbody.querySelectorAll('tr'))
                .filter(r => r.children.length > 1); // exclude the "no rows" colspan row
        },

        cfCellValue(row, col) {
            const td = row.children[col];
            if (!td) return '';
            const ctrl = td.querySelector('input, select, textarea');
            const raw = ctrl ? (ctrl.value ?? '') : td.textContent;
            return String(raw).replace(/\s+/g, ' ').trim();
        },

        cfCompare(a, b) {
            if (a === b) return 0;
            if (a === '') return 1;   // blanks last
            if (b === '') return -1;
            const aNum = /^-?\d*\.?\d+$/.test(a);
            const bNum = /^-?\d*\.?\d+$/.test(b);
            if (aNum && bNum) return parseFloat(a) - parseFloat(b);
            return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
        },

        cfDistinct(col) {
            const set = new Set();
            this.cfDataRows().forEach(r => set.add(this.cfCellValue(r, col)));
            return Array.from(set).sort((x, y) => this.cfCompare(x, y));
        },

        cfApplyFilters() {
            const active = Object.entries(this._cf.filters);
            this.cfDataRows().forEach(row => {
                let show = true;
                for (const [colStr, allowed] of active) {
                    if (!allowed) continue;
                    if (!allowed.has(this.cfCellValue(row, parseInt(colStr, 10)))) {
                        show = false;
                        break;
                    }
                }
                row.style.display = show ? '' : 'none';
            });
            this.cfUpdateIndicators();
        },

        cfApplySort(col, dir) {
            const rows = this.cfDataRows();
            rows.sort((r1, r2) => {
                const base = this.cfCompare(this.cfCellValue(r1, col), this.cfCellValue(r2, col));
                return dir === 'desc' ? -base : base;
            });
            const tbody = this._cf.tbody;
            rows.forEach(r => tbody.appendChild(r));
            this._cf.sort = { col, dir };
            this.cfUpdateIndicators();
        },

        cfUpdateIndicators() {
            Object.entries(this._cf.buttons).forEach(([colStr, btn]) => {
                const col = parseInt(colStr, 10);
                const filtered = !!this._cf.filters[col];
                const sorted = this._cf.sort.col === col;
                btn.classList.toggle('cf-active', filtered || sorted);
                let label = '▾';
                if (sorted) label = this._cf.sort.dir === 'desc' ? '↓' : '↑';
                if (filtered) label = '⚑';
                if (filtered && sorted) label = (this._cf.sort.dir === 'desc' ? '⚑↓' : '⚑↑');
                btn.textContent = label;
            });
        },

        cfBuildPopover() {
            const pop = document.createElement('div');
            pop.className = 'cf-pop';
            pop.style.display = 'none';
            document.body.appendChild(pop);
            this._cf.popover = pop;
        },

        cfOpenPopover(col, btn) {
            if (this._cf.activeCol === col && this._cf.popover.style.display !== 'none') {
                this.cfClosePopover();
                return;
            }
            this._cf.activeCol = col;
            const pop = this._cf.popover;
            const values = this.cfDistinct(col);
            const current = this._cf.filters[col]; // Set or undefined

            pop.innerHTML = '';

            // sort row
            const sortRow = document.createElement('div');
            sortRow.className = 'cf-sort';
            const asc = document.createElement('button');
            asc.textContent = 'Sort A→Z';
            asc.addEventListener('click', () => { this.cfApplySort(col, 'asc'); this.cfClosePopover(); });
            const desc = document.createElement('button');
            desc.textContent = 'Sort Z→A';
            desc.addEventListener('click', () => { this.cfApplySort(col, 'desc'); this.cfClosePopover(); });
            sortRow.appendChild(asc);
            sortRow.appendChild(desc);
            pop.appendChild(sortRow);

            // search
            const searchWrap = document.createElement('div');
            searchWrap.className = 'cf-search';
            const search = document.createElement('input');
            search.type = 'text';
            search.placeholder = 'Search values…';
            searchWrap.appendChild(search);
            pop.appendChild(searchWrap);

            // list
            const list = document.createElement('div');
            list.className = 'cf-list';

            // select-all
            const allLabel = document.createElement('label');
            allLabel.className = 'cf-opt cf-all';
            const allCb = document.createElement('input');
            allCb.type = 'checkbox';
            const allText = document.createElement('span');
            allText.textContent = '(Select all)';
            allLabel.appendChild(allCb);
            allLabel.appendChild(allText);
            list.appendChild(allLabel);

            const optEls = [];
            values.forEach(v => {
                const label = document.createElement('label');
                label.className = 'cf-opt';
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.value = v;
                cb.checked = current ? current.has(v) : true;
                const span = document.createElement('span');
                span.textContent = v === '' ? '(Blanks)' : v;
                span.title = span.textContent;
                label.appendChild(cb);
                label.appendChild(span);
                list.appendChild(label);
                optEls.push({ label, cb, value: v });
            });

            const syncAll = () => {
                const visible = optEls.filter(o => o.label.style.display !== 'none');
                allCb.checked = visible.length > 0 && visible.every(o => o.cb.checked);
            };
            syncAll();

            allCb.addEventListener('change', () => {
                optEls.filter(o => o.label.style.display !== 'none')
                      .forEach(o => { o.cb.checked = allCb.checked; });
            });
            optEls.forEach(o => o.cb.addEventListener('change', syncAll));

            search.addEventListener('input', () => {
                const q = search.value.toLowerCase();
                optEls.forEach(o => {
                    const text = (o.value === '' ? '(blanks)' : o.value).toLowerCase();
                    o.label.style.display = text.includes(q) ? '' : 'none';
                });
                syncAll();
            });

            pop.appendChild(list);

            // footer
            const foot = document.createElement('div');
            foot.className = 'cf-foot';
            const apply = document.createElement('button');
            apply.className = 'cf-apply';
            apply.textContent = 'Apply';
            apply.addEventListener('click', () => {
                const checked = optEls.filter(o => o.cb.checked).map(o => o.value);
                if (checked.length === optEls.length) {
                    delete this._cf.filters[col]; // all selected = no filter
                } else {
                    this._cf.filters[col] = new Set(checked);
                }
                this.cfApplyFilters();
                this.cfClosePopover();
            });
            const clear = document.createElement('button');
            clear.textContent = 'Clear';
            clear.addEventListener('click', () => {
                delete this._cf.filters[col];
                this.cfApplyFilters();
                this.cfClosePopover();
            });
            const clearAll = document.createElement('button');
            clearAll.textContent = 'Reset all';
            clearAll.addEventListener('click', () => {
                this._cf.filters = {};
                this._cf.sort = { col: null, dir: null };
                this.cfApplyFilters();
                this.cfClosePopover();
            });
            foot.appendChild(apply);
            foot.appendChild(clear);
            foot.appendChild(clearAll);
            pop.appendChild(foot);

            // position near the button
            pop.style.display = 'block';
            const rect = btn.getBoundingClientRect();
            const width = pop.offsetWidth || 260;
            let left = rect.left;
            if (left + width > window.innerWidth - 8) {
                left = Math.max(8, window.innerWidth - width - 8);
            }
            let top = rect.bottom + 4;
            pop.style.left = left + 'px';
            pop.style.top = top + 'px';

            // clamp height if near bottom
            const maxH = window.innerHeight - top - 8;
            pop.style.maxHeight = Math.max(180, maxH) + 'px';
            pop.style.overflow = 'auto';

            search.focus();
        },

        cfClosePopover() {
            if (this._cf.popover) this._cf.popover.style.display = 'none';
            this._cf.activeCol = null;
        },
    };
}
</script>

@endsection