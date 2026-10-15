{{-- V5-P3: per-shift pay rate (all assigners), charge rate (Super Admin / Accountant), expense (Super Admin / Accountant). --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
    <div>
        <label class="block text-xs font-medium mb-1 text-gray-600">Pay rate /hr</label>
        <input type="number" step="0.01" min="0" inputmode="decimal"
               class="w-full border rounded px-2 py-1"
               x-model="assignedGuards[date][i-1].payRate"
               @blur="queueSaveRow(date, i-1)"
               :disabled="!canAssign">
    </div>
    @can('view-charge-rate')
    <div x-show="canSeeCharge" x-cloak>
        <label class="block text-xs font-medium mb-1 text-gray-600">Charge rate /hr</label>
        <input type="number" step="0.01" min="0" inputmode="decimal"
               class="w-full border rounded px-2 py-1"
               x-model="assignedGuards[date][i-1].chargeRate"
               @blur="queueSaveRow(date, i-1)"
               :disabled="!canAssign">
    </div>
    @endcan
    @can('edit-guard-payments')
    <div x-show="canSeeExpense" x-cloak>
        <label class="block text-xs font-medium mb-1 text-gray-600">Expense (paid to guard)</label>
        <input type="number" step="0.01" min="0" inputmode="decimal"
               class="w-full border rounded px-2 py-1"
               x-model="assignedGuards[date][i-1].expense"
               @blur="queueSaveRow(date, i-1)"
               placeholder="0.00"
               :disabled="!canAssign">
    </div>
    @endcan
</div>
