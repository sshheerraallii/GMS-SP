@extends('layouts.default')

@section('content')

<div class="container mx-auto p-6"
     x-data="guardAssignment(
        @js($dailyGuards),
        @js($guards),
        '{{ $event->shift_mode }}',
        @js($assignments ?? []),
        @js(auth()->user()->can('shifts.assign'))
     )"
     x-init="init()">

    <h2 class="text-2xl font-bold mb-1">
        Assign Guards — {{ $event->event_name }}
    </h2>

    <p class="text-sm text-gray-600 mb-6">
        {{ $event->start_date }} → {{ $event->end_date }}
    </p>

    <p class="mb-6 text-sm font-medium text-blue-700">
        {{ $event->shift_mode === 'different'
            ? 'Different shifts per guard selected. Each assignment requires start, end, and break.'
            : 'Same shift for all guards selected. Global start/end can be set, and each row can carry its own break.' }}
    </p>

    {{-- GLOBAL SHIFT (ONLY FOR SAME SHIFT MODE) --}}
    <div x-show="shiftMode === 'same'" class="mb-8 border rounded-lg p-4 bg-yellow-50">
        <h3 class="font-semibold mb-3 text-gray-800">Global Shift Time</h3>

        <p class="text-sm text-gray-600 mb-4">
            This time will apply to all guards on all dates.
        </p>

        <div class="flex gap-4 max-w-md">
            <div class="w-1/2">
                <label class="block text-sm font-medium mb-1">Start Time</label>
                <select class="w-full border rounded px-3 py-2"
                        x-model="globalShiftStart"
                        @change="onGlobalShiftChange()"
                        :disabled="!canAssign">
                    <option value="">--</option>
                    <template x-for="t in timeOptions" :key="'gs-' + t">
                        <option :value="t" x-text="t"></option>
                    </template>
                </select>
            </div>

            <div class="w-1/2">
                <label class="block text-sm font-medium mb-1">End Time</label>
                <select class="w-full border rounded px-3 py-2"
                        x-model="globalShiftEnd"
                        @change="onGlobalShiftChange()"
                        :disabled="!canAssign">
                    <option value="">--</option>
                    <template x-for="t in timeOptions" :key="'ge-' + t">
                        <option :value="t" x-text="t"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>

    {{-- PER DATE TABLES --}}
    <template x-for="(count, date) in dailyGuards" :key="date">
        <div class="mb-8 border rounded-lg p-4 bg-white">

            <div class="mb-3 flex items-center justify-between gap-3">
               <h3 class="font-semibold">
    Date: <span x-text="date"></span>
    <span class="text-sm text-gray-500" x-text="'(' + formatDayName(date) + ')'"></span>
    <span class="text-sm text-gray-500">
        (Guards required: <span x-text="count"></span>)
    </span>
</h3>

                <template x-if="canAssign">
                    <button type="button"
                            class="inline-flex items-center rounded border px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-50 disabled:opacity-50"
                            @click="openMultiGuardModal(date)"
                            :disabled="emptySlotCount(date) === 0">
                        Select Multiple Guards
                    </button>
                </template>
            </div>

            <table class="w-full text-sm border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1">Guard</th>

                        <template x-if="shiftMode === 'same'">
                            <th class="border px-2 py-1">Shift Details</th>
                        </template>

                        <template x-if="shiftMode === 'different'">
                            <th class="border px-2 py-1">Shift (Start → End + Break)</th>
                        </template>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="i in count" :key="`${date}-${i}`">
                        <tr>
                            <td class="border px-2 py-1 align-top">
                                <template x-if="canAssign">
                                    <button type="button"
                                            class="w-full text-left px-2 py-1 border rounded"
                                            @click="openGuardModal(date, i-1)">
                                        <span x-text="assignedGuards[date][i-1]?.name || 'Select Guard'"></span>
                                    </button>
                                </template>

                                <template x-if="!canAssign">
                                    <div class="w-full text-left px-2 py-1 border rounded bg-gray-50 text-gray-500">
                                        <span x-text="assignedGuards[date][i-1]?.name || 'Select Guard'"></span>
                                    </div>
                                </template>
                            </td>

                            <template x-if="shiftMode === 'same'">
                                <td class="border px-2 py-1">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium mb-1 text-gray-600">Break</label>
                                            
                                           <select class="w-full border rounded px-2 py-1"
        x-model="assignedGuards[date][i-1].breakHours"
        @change="queueSaveRow(date, i-1)"
        :disabled="!canAssign">
    <template x-for="opt in breakOptions" :key="'same-break-' + date + '-' + i + '-' + opt.value">
        <option
            :value="opt.value"
            
            x-text="opt.label"></option>
    </template>
</select>
                                            
                                            
                                            
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-medium mb-1 text-gray-600">Shift Location</label>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                <input type="text"
                                                       class="w-full border rounded px-2 py-1"
                                                       x-model="assignedGuards[date][i-1].shift1Postcode"
                                                       @blur="queueSaveRow(date, i-1)"
                                                       placeholder="Postcode *"
                                                       :required="!!assignedGuards[date][i-1].id"
                                                       :disabled="!canAssign">
                                                <input type="text"
                                                       class="w-full border rounded px-2 py-1"
                                                       x-model="assignedGuards[date][i-1].shift1SiteName"
                                                       @blur="queueSaveRow(date, i-1)"
                                                       placeholder="Site name *"
                                                       :required="!!assignedGuards[date][i-1].id"
                                                       :disabled="!canAssign">
                                                <input type="text"
                                                       class="w-full border rounded px-2 py-1"
                                                       x-model="assignedGuards[date][i-1].shift1SiteAddress"
                                                       @blur="queueSaveRow(date, i-1)"
                                                       placeholder="Site address (optional)"
                                                       :disabled="!canAssign">
                                            </div>
                                        </div>
                                    </div>
                                    
                            {{-- Per shift save button --}}
        @can('shifts.assign')
            <button type="button"
        class="mt-2 inline-flex items-center justify-center rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50"
        @click="saveRow(date, i-1)"
        :disabled="isSavingRow(date, i-1)"
        x-text="isSavingRow(date, i-1) ? 'Saving...' : 'Save Row'">
</button>
        @endcan
                                    
                                    
                                </td>
                            </template>

                            <template x-if="shiftMode === 'different'">
                                <td class="border px-2 py-1">
                                    <div class="space-y-2">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                           <select class="border rounded px-2 py-1"
        x-model="assignedGuards[date][i-1].shift1Start"
        @change="onTimePicked('shift1'); queueSaveRow(date, i-1)"
        :disabled="!canAssign">
                                                <option value="">--</option>
                                                <template x-for="t in timeOptions" :key="date + '-' + i + '-s1s-' + t">
                                                    <option :value="t"
                                                            :selected="assignedGuards[date][i-1].shift1Start === t"
                                                            x-text="t"></option>
                                                </template>
                                            </select>

                                          <select class="border rounded px-2 py-1"
        x-model="assignedGuards[date][i-1].shift1End"
        @change="onTimePicked('shift1'); queueSaveRow(date, i-1)"
        :disabled="!canAssign">
                                                <option value="">--</option>
                                                <template x-for="t in timeOptions" :key="date + '-' + i + '-s1e-' + t">
                                                    <option :value="t"
                                                            :selected="assignedGuards[date][i-1].shift1End === t"
                                                            x-text="t"></option>
                                                </template>
                                            </select>

                                           <select class="border rounded px-2 py-1"
        x-model="assignedGuards[date][i-1].breakHours"
        @change="queueSaveRow(date, i-1)"
        :disabled="!canAssign">
                                               
    <template x-for="opt in breakOptions" :key="'diff-break-' + date + '-' + i + '-' + opt.value">
        <option
            :value="opt.value"
            x-text="opt.label"></option>
    </template>
</select>
                                            
                                            
                                            
                                            
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                            <input type="text"
                                                   class="w-full border rounded px-2 py-1"
                                                   x-model="assignedGuards[date][i-1].shift1Postcode"
                                                   @blur="queueSaveRow(date, i-1)"
                                                   placeholder="Postcode *"
                                                   :required="!!assignedGuards[date][i-1].id"
                                                   :disabled="!canAssign">
                                            <input type="text"
                                                   class="w-full border rounded px-2 py-1"
                                                   x-model="assignedGuards[date][i-1].shift1SiteName"
                                                   @blur="queueSaveRow(date, i-1)"
                                                   placeholder="Site name *"
                                                   :required="!!assignedGuards[date][i-1].id"
                                                   :disabled="!canAssign">
                                            <input type="text"
                                                   class="w-full border rounded px-2 py-1"
                                                   x-model="assignedGuards[date][i-1].shift1SiteAddress"
                                                   @blur="queueSaveRow(date, i-1)"
                                                   placeholder="Site address (optional)"
                                                   :disabled="!canAssign">
                                        </div>
                                    </div>
                                    
                                     {{-- Per shift save button--}}
        @can('shifts.assign')
            <button type="button"
        class="mt-2 inline-flex items-center justify-center rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50"
        @click="saveRow(date, i-1)"
        :disabled="isSavingRow(date, i-1)"
        x-text="isSavingRow(date, i-1) ? 'Saving...' : 'Save Row'">
</button>
        @endcan
                                    
                                    
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

   {{-- ACTIONS --}}
<div class="flex justify-between mt-10">
    <a href="{{ route('events.show', $event->id) }}" class="px-5 py-2 rounded border">
        View Event
    </a>

    <a href="{{ route('events.index') }}" class="px-5 py-2 rounded border">
        Back
    </a>
</div>

    {{-- GUARD SELECTION MODAL --}}
    <div x-show="guardModal"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         style="display:none;">
        <div class="bg-white w-full max-w-3xl rounded p-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-bold text-lg">Select Guard</h3>
                <button type="button" @click="guardModal=false" class="text-red-600 text-xl leading-none">✕</button>
            </div>

          <input type="text"
       x-ref="guardSearchInput"
       x-model="search"
       placeholder="Search by name or license number"
       class="w-full border rounded px-3 py-2 mb-3"
       :disabled="!canAssign">

            <div class="max-h-96 overflow-y-auto border rounded divide-y"
                 :class="!canAssign ? 'opacity-60 pointer-events-none' : ''">

                <template x-for="g in filteredGuards" :key="g.id">
                    <label class="flex items-center justify-between p-3"
                           :class="[
                               g.id === tempSelection.id ? 'bg-gray-100' : '',
                               isGuardDisabled(g) ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'
                           ]">
                        <div>
                            <div class="font-medium flex items-center gap-2">
                                <span x-text="g.fullname"></span>

                                <template x-if="isGuardExpired(g)">
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-700">
                                        Expired
                                    </span>
                                </template>

                                <template x-if="isCurrentRowGuard(g)">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700">
                                        Already assigned
                                    </span>
                                </template>
                            </div>

                            <div class="text-sm text-gray-500" x-text="'License: ' + (g.license_number || '—')"></div>

                            <template x-if="g.share_code_expiry">
                                <div class="text-xs text-gray-500" x-text="'Share-Code Expiry: ' + g.share_code_expiry"></div>
                            </template>

                            <template x-if="isGuardExpired(g) && !isCurrentRowGuard(g)">
                                <div class="text-xs text-red-600 mt-1">
                                    Cannot be newly assigned because share code is expired.
                                </div>
                            </template>
                        </div>

                        <input type="radio"
                               class="w-5 h-5"
                               name="modalRadio"
                               :value="g.id"
                               x-model="tempSelection.id"
                               :disabled="!canAssign || isGuardDisabled(g)">
                    </label>
                </template>
            </div>

            <div class="mt-3 flex justify-end gap-3">
                @can('shifts.assign')
                    <button type="button" @click="assignGuard()" class="bg-blue-600 text-white px-4 py-1 rounded">
                        Assign
                    </button>
                @endcan

                <button type="button" @click="guardModal=false" class="px-4 py-1 border rounded">
                    Cancel
                </button>
            </div>

            @cannot('shifts.assign')
                <div class="mt-3 text-sm text-gray-500">
                    You can view assignments, but you can’t change them.
                </div>
            @endcannot
        </div>
    </div>

    {{-- MULTI GUARD SELECTION MODAL --}}
    <div x-show="multiGuardModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         style="display:none;">
        <div class="w-full max-w-4xl rounded bg-white p-4">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold">Select Multiple Guards</h3>
                    <p class="text-sm text-gray-500">
                        Date: <span x-text="multiCurrentDate"></span>
                        — Empty slots:
                        <span x-text="multiRemainingSlots()"></span>
                    </p>
                </div>

                <button type="button"
                        @click="closeMultiGuardModal()"
                        class="text-xl leading-none text-red-600">
                    ✕
                </button>
            </div>

            <input type="text"
       x-ref="multiGuardSearchInput"
       x-model="multiSearch"
       placeholder="Search by name or license number"
       class="mb-3 w-full rounded border px-3 py-2"
       :disabled="!canAssign">

            <div class="max-h-96 overflow-y-auto divide-y rounded border"
                 :class="!canAssign ? 'pointer-events-none opacity-60' : ''">

                <template x-for="g in multiFilteredGuards" :key="'multi-' + g.id">
                    <label class="flex items-center justify-between p-3"
                           :class="[
                               isGuardDisabledForMulti(g) ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
                               isGuardPickedInMulti(g.id) ? 'bg-blue-50' : ''
                           ]">
                        <div>
                            <div class="flex items-center gap-2 font-medium">
                                <span x-text="g.fullname"></span>

                                <template x-if="isGuardExpired(g)">
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-700">
                                        Expired
                                    </span>
                                </template>

                                <template x-if="isGuardAlreadyUsedOnDate(g.id, multiCurrentDate)">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700">
                                        Already on this date
                                    </span>
                                </template>
                            </div>

                            <div class="text-sm text-gray-500" x-text="'License: ' + (g.license_number || '—')"></div>

                            <template x-if="g.share_code_expiry">
                                <div class="text-xs text-gray-500" x-text="'Share-Code Expiry: ' + g.share_code_expiry"></div>
                            </template>

                            <template x-if="isGuardDisabledForMulti(g)">
                                <div class="mt-1 text-xs text-red-600">
                                    Not selectable for this date.
                                </div>
                            </template>
                        </div>

                        <input type="checkbox"
                               class="h-5 w-5"
                               :checked="isGuardPickedInMulti(g.id)"
                               @change="toggleMultiGuard(g)"
                               :disabled="!canAssign || isGuardDisabledForMulti(g)">
                    </label>
                </template>
            </div>

            <div class="mt-3 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Selected:
                    <span x-text="multiSelectedGuards.length"></span>
                    /
                    <span x-text="multiRemainingSlots()"></span>
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            class="rounded border px-4 py-1"
                            @click="closeMultiGuardModal()">
                        Cancel
                    </button>

                    <button type="button"
                            class="rounded bg-blue-600 px-4 py-1 text-white"
                            @click="assignMultipleGuards()">
                        Assign Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@include('events.partials.import-additional-days')

<script>
function guardAssignment(dailyGuards, guards, shiftMode, existingAssignments, canAssign) {
    let assigned = {};

    for (let date in dailyGuards) {
        assigned[date] = Array.from({ length: Number(dailyGuards[date]) }, () => ({
            id: null,
            name: '',
            shift1Start: '',
            shift1End: '',
            breakHours: '0',
            shift1Postcode: '',
            shift1SiteName: '',
            shift1SiteAddress: ''
        }));
    }

    for (let date in existingAssignments) {
        if (!assigned[date]) continue;

        existingAssignments[date].forEach((row, idx) => {
            if (!assigned[date][idx]) return;

            const g = guards.find(x => String(x.id) === String(row.guard_id));

            assigned[date][idx] = {
                id: row.guard_id ?? null,
                name: g ? g.fullname : '',
                shift1Start: (row.shift1_start || '').toString().slice(0, 5),
                shift1End: (row.shift1_end || '').toString().slice(0, 5),
                breakHours: row.break_hours !== null && row.break_hours !== undefined && row.break_hours !== ''
                    ? String(parseFloat(row.break_hours))
                    : '0',
                shift1Postcode: row.shift1_postcode || '',
                shift1SiteName: row.shift1_site_name || '',
                shift1SiteAddress: row.shift1_site_address || '',
            };
        });
    }

    const timeOptions = [];
    const stepMinutes = 5;
    for (let h = 0; h < 24; h++) {
        for (let m = 0; m < 60; m += stepMinutes) {
            const hh = String(h).padStart(2, "0");
            const mm = String(m).padStart(2, "0");
            timeOptions.push(`${hh}:${mm}`);
        }
    }

    const breakOptions = [
        { value: '0', label: 'No Break (0h)' },
        { value: '0.25', label: '15 min (0.25h)' },
        { value: '0.5', label: '30 min (0.50h)' },
        { value: '0.75', label: '45 min (0.75h)' },
        { value: '1', label: '60 min (1h)' },
    ];

    return {
        dailyGuards,
        guards,
        shiftMode,
        canAssign,
        assignedGuards: assigned,
        savingRows: {},
autosaveTimers: {},

        timeOptions,
        breakOptions,
        
        formatDayName(date) {
    if (!date) return '';

    const d = new Date(date + 'T00:00:00');
    if (isNaN(d.getTime())) return '';

    return d.toLocaleDateString('en-GB', { weekday: 'long' });
},


        guardModal: false,
        search: '',
        tempSelection: { id: null },
        currentDate: null,
        currentIndex: null,

        multiGuardModal: false,
        multiSearch: '',
        multiCurrentDate: null,
        multiSelectedGuards: [],

        globalShiftStart: '',
        globalShiftEnd: '',

        latest: {
            shift1Start: '',
            shift1End: '',
        },
        
        
                       init() {
            for (let date in this.assignedGuards) {
                for (let idx = 0; idx < this.assignedGuards[date].length; idx++) {
                    const row = this.assignedGuards[date][idx];

                    const breakNum = parseFloat(row.breakHours ?? 0);
                    row.breakHours = Number.isNaN(breakNum) ? '0' : String(breakNum);

                    row.shift1Start = row.shift1Start ? String(row.shift1Start).slice(0, 5) : '';
                    row.shift1End = row.shift1End ? String(row.shift1End).slice(0, 5) : '';
                    row.shift1Postcode = row.shift1Postcode ?? '';
                    row.shift1SiteName = row.shift1SiteName ?? '';
                    row.shift1SiteAddress = row.shift1SiteAddress ?? '';
                }
            }

            if (this.shiftMode !== 'same') return;

            for (let date in this.assignedGuards) {
                for (let idx = 0; idx < this.assignedGuards[date].length; idx++) {
                    const row = this.assignedGuards[date][idx];

                    if (row.shift1Start && row.shift1End) {
                        this.globalShiftStart = row.shift1Start;
                        this.globalShiftEnd = row.shift1End;

                        this.latest.shift1Start = row.shift1Start;
                        this.latest.shift1End = row.shift1End;
                        return;
                    }
                }
            }
        },
        
        
        

        get filteredGuards() {
            if (!this.search) return this.guards;
            const q = this.search.toLowerCase();

            return this.guards.filter(g =>
                String(g.fullname).toLowerCase().includes(q) ||
                String(g.license_number || '').toLowerCase().includes(q)
            );
        },

        get multiFilteredGuards() {
            if (!this.multiSearch) return this.guards;

            const q = this.multiSearch.toLowerCase();

            return this.guards.filter(g =>
                String(g.fullname).toLowerCase().includes(q) ||
                String(g.license_number || '').toLowerCase().includes(q)
            );
        },

        emptySlotCount(date) {
            if (!this.assignedGuards?.[date]) return 0;

            return this.assignedGuards[date].filter(r => !r.id).length;
        },

        filledGuardIdsForDate(date) {
            if (!this.assignedGuards?.[date]) return [];

            return this.assignedGuards[date]
                .filter(r => r.id)
                .map(r => String(r.id));
        },

        multiRemainingSlots() {
            if (!this.multiCurrentDate) return 0;
            return this.emptySlotCount(this.multiCurrentDate);
        },

        isGuardAlreadyUsedOnDate(guardId, date) {
            return this.filledGuardIdsForDate(date).includes(String(guardId));
        },

        isGuardPickedInMulti(guardId) {
            return this.multiSelectedGuards.some(g => String(g.id) === String(guardId));
        },

        isGuardDisabledForMulti(g) {
            if (!this.multiCurrentDate) return true;

            if (this.isGuardExpired(g)) return true;

            if (this.isGuardAlreadyUsedOnDate(g.id, this.multiCurrentDate)) return true;

            const max = this.multiRemainingSlots();

            if (!this.isGuardPickedInMulti(g.id) && this.multiSelectedGuards.length >= max) {
                return true;
            }

            return false;
        },

        openMultiGuardModal(date) {
    if (!this.canAssign) return;

    this.multiCurrentDate = date;
    this.multiSearch = '';
    this.multiSelectedGuards = [];
    this.multiGuardModal = true;

    this.$nextTick(() => {
        this.$refs.multiGuardSearchInput?.focus();
    });
},

        closeMultiGuardModal() {
            this.multiGuardModal = false;
            this.multiSearch = '';
            this.multiCurrentDate = null;
            this.multiSelectedGuards = [];
        },

        toggleMultiGuard(guard) {
            if (!this.canAssign) return;

            const existingIndex = this.multiSelectedGuards.findIndex(
                g => String(g.id) === String(guard.id)
            );

            if (existingIndex !== -1) {
                this.multiSelectedGuards.splice(existingIndex, 1);
                return;
            }

            if (this.isGuardDisabledForMulti(guard)) {
                return;
            }

            this.multiSelectedGuards.push({
                id: guard.id,
                fullname: guard.fullname
            });
        },

        assignMultipleGuards() {
            if (!this.canAssign) return;
            if (!this.multiCurrentDate) return;
            if (!this.multiSelectedGuards.length) {
                this.closeMultiGuardModal();
                return;
            }

            const rows = this.assignedGuards[this.multiCurrentDate];
            if (!rows?.length) {
                this.closeMultiGuardModal();
                return;
            }

            let selectedIndex = 0;

            for (let rowIndex = 0; rowIndex < rows.length; rowIndex++) {
                if (selectedIndex >= this.multiSelectedGuards.length) break;
                if (rows[rowIndex].id) continue;

                const selected = this.multiSelectedGuards[selectedIndex];

                rows[rowIndex].id = selected.id;
                rows[rowIndex].name = selected.fullname;

                if (this.shiftMode === 'same') {
                    rows[rowIndex].shift1Start = this.globalShiftStart;
                    rows[rowIndex].shift1End = this.globalShiftEnd;
                    rows[rowIndex].shift1Postcode = rows[rowIndex].shift1Postcode || '';
                    rows[rowIndex].shift1SiteName = rows[rowIndex].shift1SiteName || '';
                    rows[rowIndex].shift1SiteAddress = rows[rowIndex].shift1SiteAddress || '';
                    rows[rowIndex].breakHours = rows[rowIndex].breakHours || '0';
                } else {
                    if (!rows[rowIndex].shift1Start && this.latest.shift1Start) {
                        rows[rowIndex].shift1Start = this.latest.shift1Start;
                    }
                    if (!rows[rowIndex].shift1End && this.latest.shift1End) {
                        rows[rowIndex].shift1End = this.latest.shift1End;
                    }
                    rows[rowIndex].breakHours = rows[rowIndex].breakHours || '0';
                }

                selectedIndex++;
            }

            this.closeMultiGuardModal();
        },

        isGuardExpired(g) {
            if (!g?.share_code_expiry) return false;

            const raw = String(g.share_code_expiry).slice(0, 10);
            const expiry = new Date(`${raw}T00:00:00`);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            return expiry < today;
        },

        isCurrentRowGuard(g) {
            if (!this.currentDate || this.currentIndex === null) return false;

            const current = this.assignedGuards?.[this.currentDate]?.[this.currentIndex];
            if (!current?.id) return false;

            return String(current.id) === String(g.id);
        },

        isGuardDisabled(g) {
            return this.isGuardExpired(g) && !this.isCurrentRowGuard(g);
        },

        openGuardModal(date, index) {
    if (!this.canAssign) return;

    this.currentDate = date;
    this.currentIndex = index;
    this.search = '';
    this.tempSelection = { id: this.assignedGuards[date][index]?.id ?? null };
    this.guardModal = true;

    this.$nextTick(() => {
        this.$refs.guardSearchInput?.focus();
    });
},

        assignGuard() {
            if (!this.canAssign) return;

            if (!this.tempSelection.id) {
                this.guardModal = false;
                return;
            }

            const selected = this.guards.find(g => String(g.id) === String(this.tempSelection.id));
            if (!selected) {
                this.guardModal = false;
                return;
            }

            if (this.isGuardDisabled(selected)) {
                alert('This guard cannot be newly assigned because the Share-Code Expiry date has passed.');
                return;
            }

            const row = this.assignedGuards[this.currentDate][this.currentIndex];

            this.assignedGuards[this.currentDate][this.currentIndex] = {
                ...row,
                id: selected.id,
                name: selected.fullname,
                breakHours: row.breakHours || '0',
            };

            if (this.shiftMode === 'same') {
                this.assignedGuards[this.currentDate][this.currentIndex].shift1Start = this.globalShiftStart;
                this.assignedGuards[this.currentDate][this.currentIndex].shift1End = this.globalShiftEnd;
            } else {
                const r = this.assignedGuards[this.currentDate][this.currentIndex];

                if (!r.shift1Start && this.latest.shift1Start) r.shift1Start = this.latest.shift1Start;
                if (!r.shift1End && this.latest.shift1End) r.shift1End = this.latest.shift1End;
            }

            this.queueSaveRow(this.currentDate, this.currentIndex);
this.guardModal = false;
        },

        onGlobalShiftChange() {
            if (!this.canAssign) return;
            if (this.shiftMode !== 'same') return;

            this.latest.shift1Start = this.globalShiftStart || this.latest.shift1Start;
            this.latest.shift1End = this.globalShiftEnd || this.latest.shift1End;

            for (let date in this.assignedGuards) {
                for (let idx = 0; idx < this.assignedGuards[date].length; idx++) {
                    this.assignedGuards[date][idx].shift1Start = this.globalShiftStart;
                    this.assignedGuards[date][idx].shift1End = this.globalShiftEnd;
                }
            }
        },

        onTimePicked(which) {
            if (!this.canAssign) return;
            if (this.shiftMode !== 'different') return;

            let newest = {
                shift1Start: '',
                shift1End: '',
            };

            for (let date in this.assignedGuards) {
                for (let idx = 0; idx < this.assignedGuards[date].length; idx++) {
                    const r = this.assignedGuards[date][idx];
                    if (r.shift1Start) newest.shift1Start = r.shift1Start;
                    if (r.shift1End) newest.shift1End = r.shift1End;
                }
            }

            if (newest.shift1Start) this.latest.shift1Start = newest.shift1Start;
            if (newest.shift1End) this.latest.shift1End = newest.shift1End;

            for (let date in this.assignedGuards) {
                for (let idx = 0; idx < this.assignedGuards[date].length; idx++) {
                    const r = this.assignedGuards[date][idx];

                    if (!r.shift1Start && this.latest.shift1Start) r.shift1Start = this.latest.shift1Start;
                    if (!r.shift1End && this.latest.shift1End) r.shift1End = this.latest.shift1End;
                    if (!r.breakHours) r.breakHours = '0';
                }
            }
        },


rowKey(date, index) {
    return `${date}-${index}`;
},

isSavingRow(date, index) {
    return !!this.savingRows[this.rowKey(date, index)];
},

queueSaveRow(date, index) {
    if (!this.canAssign) return;

    const key = this.rowKey(date, index);

    if (this.autosaveTimers[key]) {
        clearTimeout(this.autosaveTimers[key]);
    }

    this.autosaveTimers[key] = setTimeout(() => {
        this.saveRow(date, index);
    }, 700);
},

saveRow(date, index) {
    if (!this.canAssign) return;

    const row = this.assignedGuards?.[date]?.[index];

    if (!row) return;

    const norm = (t) => {
        if (!t) return null;
        t = String(t);
        return t.length >= 5 ? t.slice(0, 5) : t;
    };

    const normBreak = (v) => {
        if (v === null || v === undefined || v === '') return 0;
        const parsed = parseFloat(v);
        return Number.isNaN(parsed) ? 0 : parsed;
    };

    const key = this.rowKey(date, index);

    this.savingRows[key] = true;

    const payload = {
        date: date,
        slot_no: index + 1,
        guard_id: row.id || null,
        shift1_start: norm(row.shift1Start),
        shift1_end: norm(row.shift1End),
        break_hours: normBreak(row.breakHours),
        shift1_postcode: row.shift1Postcode ? String(row.shift1Postcode).trim() : null,
        shift1_site_name: row.shift1SiteName ? String(row.shift1SiteName).trim() : null,
        shift1_site_address: row.shift1SiteAddress ? String(row.shift1SiteAddress).trim() : null,
    };

    return fetch('{{ route('events.saveGuardSlot', $event->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
        body: JSON.stringify(payload),
    })
    .then(async res => {
        const contentType = res.headers.get('content-type') || '';
        const data = contentType.includes('application/json') ? await res.json() : await res.text();

        if (!res.ok) {
            if (res.status === 422 && data.errors) {
                const firstKey = Object.keys(data.errors)[0];
                const firstMsg = data.errors[firstKey]?.[0] || data.message || 'Validation error';
                alert(firstMsg);
                throw new Error(firstMsg);
            }

            throw new Error(data.message || 'Row save failed.');
        }

        return data;
    })
    .catch(err => {
        console.error('Error saving row:', err);
        alert(err.message || 'There was an error saving this row.');
    })
    .finally(() => {
        this.savingRows[key] = false;
    });
},



        submitAssignments() {
            if (!this.canAssign) return;

            const norm = (t) => {
                if (!t) return null;
                t = String(t);
                return t.length >= 5 ? t.slice(0, 5) : t;
            };

            const normBreak = (v) => {
                if (v === null || v === undefined || v === '') return 0;
                const parsed = parseFloat(v);
                return Number.isNaN(parsed) ? 0 : parsed;
            };

            let payload = { assignments: {} };

            for (let date in this.assignedGuards) {
                if (Number(this.dailyGuards[date]) === 0) continue;

                payload.assignments[date] = this.assignedGuards[date].map(g => ({
                    guard_id: g.id || null,
                    shift1_start: norm(g.shift1Start),
                    shift1_end: norm(g.shift1End),
                    break_hours: normBreak(g.breakHours),
                    shift1_postcode: g.shift1Postcode ? String(g.shift1Postcode).trim() : null,
                    shift1_site_name: g.shift1SiteName ? String(g.shift1SiteName).trim() : null,
                    shift1_site_address: g.shift1SiteAddress ? String(g.shift1SiteAddress).trim() : null,
                }));
            }

            fetch('{{ route('events.updateGuards', $event->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify(payload),
            })
            .then(async res => {
                const contentType = res.headers.get('content-type') || '';
                const data = contentType.includes('application/json') ? await res.json() : await res.text();

                if (!res.ok) {
                    if (res.status === 422 && data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        const firstMsg = data.errors[firstKey]?.[0] || data.message || 'Validation error';
                        alert(firstMsg);
                        throw new Error(firstMsg);
                    }
                    throw new Error(data.message || 'Network response was not ok');
                }

                return data;
            })
            .then(() => window.location.href = "{{ route('events.show', $event->id) }}")
            .catch(err => {
                console.error("Error saving assignments:", err);
                alert(err.message || "There was an error saving assignments. Check console.");
            });
        }
    };
}
</script>
@endsection