<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V5-P3: per-shift pay rate, charge rate and guard expense.
     *
     * All three are NULLABLE and default to NULL, so every existing row
     * keeps resolving exactly as before: a NULL pay/charge rate means
     * "use the event's category / base rate". Nothing is backfilled.
     *
     * Stored on the slot (source of truth for the assignment grid) AND on
     * the shift row (what billing / reports read), mirroring break_hours.
     * The shift row is destroyed and recreated on every assignment save,
     * so the values are always re-copied from the slot.
     *
     * expense = amount paid to the guard on top of pay (food, travel).
     */
    public function up(): void
    {
        foreach (['event_assignment_slots', 'event_shifts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('pay_rate', 8, 2)->nullable()->after('break_hours');
                $table->decimal('charge_rate', 8, 2)->nullable()->after('pay_rate');
                $table->decimal('expense', 10, 2)->nullable()->after('charge_rate');
            });
        }
    }

    public function down(): void
    {
        foreach (['event_assignment_slots', 'event_shifts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['pay_rate', 'charge_rate', 'expense']);
            });
        }
    }
};
