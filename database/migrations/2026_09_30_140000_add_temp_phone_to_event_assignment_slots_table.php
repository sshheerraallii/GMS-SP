<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V4-P5: temporary guard = an assignment slot with NO guard_id and a
     * phone number. Not a guard record, no event_shifts row. Cleared the
     * moment a real guard is assigned to the slot.
     */
    public function up(): void
    {
        Schema::table('event_assignment_slots', function (Blueprint $table) {
            $table->string('temp_phone', 32)->nullable()->after('guard_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_assignment_slots', function (Blueprint $table) {
            $table->dropColumn('temp_phone');
        });
    }
};
