@extends('layouts.default')

@section('content')
<div class="container mx-auto p-6"
     x-data="guardAssignment(
        @js($dailyGuards),
        @js($guards),
        '{{ $event->shift_mode }}',
        @js($assignments ?? []),
        @js(auth()->user()->can('shifts.assign'))
     )">

    <h2 class="text-2xl font-bold mb-1">
        Assign Guards — {{ $event->event_name }}
    </h2>

    <p class="text-sm text-gray-600 mb-6">
        {{ $event->start_date }} → {{ $event->end_date }}
    </p>

    <p class="mb-6 text-sm font-medium text-blue-700">
        {{ $event->shift_mode === 'different'
            ? 'Different shifts per guard selected. Each assignment requires start & end time.'
            : 'Same shift for all guards selected. Time input not required.' }}
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
                <input type="time"
                       class="w-full border rounded px-3 py-2"
                       x-model="globalShiftStart"
                       :disabled="!canAssign">
            </div>

            <div class="w-1/2">
                <label class="block text-sm font-medium mb-1">End Time</label>
                <input type="time"
                       class="w-full border rounded px-3 py-2"
                       x-model="globalShiftEnd"
                       :disabled="!canAssign">
            </div>
        </div>
    </div>

    {{-- PER DATE TABLES --}}
    <template x-for="(count, date) in dailyGuards" :key="date">
        <div class="mb-8 border rounded-lg p-4 bg-white">
            <h3 class="font-semibold mb-3">
                Date: <span x-text="date"></span>
                <span class="text-sm text-gray-500">
                    (Guards required: <span x-text="count"></span>)
                </span>
            </h3>

            <table class="w-full text-sm border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1">Guard</th>
                        <template x-if="shiftMode === 'different'">
                            <th class="border px-2 py-1">Shift 1 (Start → End)</th>
                            <th class="border px-2 py-1">Shift 2 (optional Start → End)</th>
                        </template>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="i in count" :key="`${date}-${i}`">
                        <tr>
                            <td class="border px-2 py-1">
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

                            <template x-if="shiftMode === 'different'">
                                <td class="border px-2 py-1">
                                    <div class="flex gap-2">
                                        <input type="time"
                                               class="border rounded px-2 py-1 w-1/2"
                                               x-model="assignedGuards[date][i-1].shift1Start"
                                               :disabled="!canAssign">
                                        <input type="time"
                                               class="border rounded px-2 py-1 w-1/2"
                                               x-model="assignedGuards[date][i-1].shift1End"
                                               :disabled="!canAssign">
                                    </div>
                                </td>
                                <td class="border px-2 py-1">
                                    <div class="flex gap-2">
                                        <input type="time"
                                               class="border rounded px-2 py-1 w-1/2"
                                               x-model="assignedGuards[date][i-1].shift2Start"
                                               :disabled="!canAssign">
                                        <input type="time"
                                               class="border rounded px-2 py-1 w-1/2"
                                               x-model="assignedGuards[date][i-1].shift2End"
                                               :disabled="!canAssign">
                                    </div>
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
            Back
        </a>

        @can('shifts.assign')
            <button type="button"
                    @click="submitAssignments()"
                    class="bg-blue-600 text-white px-6 py-2 rounded">
                Save Assignments
            </button>
        @endcan

        @cannot('shifts.assign')
            <span class="text-sm text-gray-500 self-center">
                You don’t have permission to save assignments.
            </span>
        @endcannot
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
                   x-model="search"
                   placeholder="Search by name or license number"
                   class="w-full border rounded px-3 py-2 mb-3"
                   :disabled="!canAssign">

            <div class="max-h-96 overflow-y-auto border rounded divide-y"
                 :class="!canAssign ? 'opacity-60 pointer-events-none' : ''">
                <template x-for="g in filteredGuards" :key="g.id">
                    <label class="flex items-center justify-between p-3 cursor-pointer"
                           :class="g.id === tempSelection.id ? 'bg-gray-100' : ''">
                        <div>
                            <div class="font-medium" x-text="g.fullname"></div>
                            <div class="text-sm text-gray-500" x-text="'License: ' + g.license_number"></div>
                        </div>

                        <input type="radio"
                               class="w-5 h-5"
                               name="modalRadio"
                               :value="g.id"
                               x-model="tempSelection.id"
                               :disabled="!canAssign">
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

</div>

<script>
function guardAssignment(dailyGuards, guards, shiftMode, existingAssignments, canAssign) {
    // Build blank structure (date -> rows)
    let assigned = {};
    for (let date in dailyGuards) {
        assigned[date] = Array.from({ length: Number(dailyGuards[date]) }, () => ({
            id: null,
            name: '',
            shift1Start: '',
            shift1End: '',
            shift2Start: '',
            shift2End: ''
        }));
    }

    // Prefill existing assignments (edit mode)
    for (let date in existingAssignments) {
        if (!assigned[date]) continue;

        existingAssignments[date].forEach((row, idx) => {
            if (!assigned[date][idx]) return;

            const g = guards.find(x => String(x.id) === String(row.guard_id));

            assigned[date][idx] = {
                id: row.guard_id ?? null,
                name: g ? g.fullname : '',
                shift1Start: (row.shift1_start || '').toString().slice(0, 5),
                shift1End:   (row.shift1_end   || '').toString().slice(0, 5),
                shift2Start: (row.shift2_start || '').toString().slice(0, 5),
                shift2End:   (row.shift2_end   || '').toString().slice(0, 5),
            };
        });
    }

    return {
        dailyGuards,
        guards,
        shiftMode,
        canAssign,
        assignedGuards: assigned,

        guardModal: false,
        search: '',
        tempSelection: { id: null },
        currentDate: null,
        currentIndex: null,

        globalShiftStart: '',
        globalShiftEnd: '',

        get filteredGuards() {
            if (!this.search) return this.guards;
            const q = this.search.toLowerCase();

            return this.guards.filter(g =>
                String(g.fullname).toLowerCase().includes(q) ||
                String(g.license_number).toLowerCase().includes(q)
            );
        },

        openGuardModal(date, index) {
            if (!this.canAssign) return;

            this.currentDate = date;
            this.currentIndex = index;
            this.tempSelection = { id: this.assignedGuards[date][index]?.id ?? null };
            this.guardModal = true;
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

            this.assignedGuards[this.currentDate][this.currentIndex] = {
                ...this.assignedGuards[this.currentDate][this.currentIndex],
                id: selected.id,
                name: selected.fullname
            };

            // Apply global shift times for "same" mode
            if (this.shiftMode === 'same') {
                this.assignedGuards[this.currentDate][this.currentIndex].shift1Start = this.globalShiftStart;
                this.assignedGuards[this.currentDate][this.currentIndex].shift1End   = this.globalShiftEnd;
                this.assignedGuards[this.currentDate][this.currentIndex].shift2Start = '';
                this.assignedGuards[this.currentDate][this.currentIndex].shift2End   = '';
            }

            this.guardModal = false;
        },

        submitAssignments() {
            if (!this.canAssign) return;

            const norm = (t) => {
                if (!t) return null;
                t = String(t);
                return t.length >= 5 ? t.slice(0, 5) : t; // "18:24:00" -> "18:24"
            };

            let payload = { assignments: {} };

            for (let date in this.assignedGuards) {
                if (Number(this.dailyGuards[date]) === 0) continue;

                payload.assignments[date] = this.assignedGuards[date]
                    .filter(g => g.id)
                    .map(g => ({
                        guard_id: g.id,
                        shift1_start: norm(g.shift1Start),
                        shift1_end:   norm(g.shift1End),
                        shift2_start: norm(g.shift2Start),
                        shift2_end:   norm(g.shift2End),
                    }));

                if (payload.assignments[date].length !== Number(this.dailyGuards[date])) {
                    alert(`On ${date}, you must assign exactly ${this.dailyGuards[date]} guards.`);
                    return;
                }
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
            .then(() => window.location.href = "{{ route('events.index') }}")
            .catch(err => {
                console.error("Error saving assignments:", err);
                alert(err.message || "There was an error saving assignments. Check console.");
            });
        }
    };
}
</script>
@endsection
