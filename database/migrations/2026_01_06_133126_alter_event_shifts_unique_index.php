<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_shifts', function (Blueprint $table) {

            // 1) Drop FKs first (so MySQL allows index changes)
            $table->dropForeign(['event_id']);
            $table->dropForeign(['guard_id']);

            // 2) Drop old unique index
            $table->dropUnique('event_shifts_unique_slot');

            // 3) Add new unique index (your requirement)
            // allow multiple guards same slot, but block same guard duplicated on same date+time
            $table->unique(
                ['event_id', 'date', 'guard_id', 'start_time', 'end_time'],
                'event_shifts_unique_guard_slot'
            );

            // 4) Re-add FKs
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('guard_id')->references('id')->on('security_guards')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_shifts', function (Blueprint $table) {

            // Drop new FK constraints first
            $table->dropForeign(['event_id']);
            $table->dropForeign(['guard_id']);

            // Drop new unique index
            $table->dropUnique('event_shifts_unique_guard_slot');

            // Restore old unique (not recommended, but for rollback)
            $table->unique(
                ['event_id', 'date', 'start_time', 'end_time'],
                'event_shifts_unique_slot'
            );

            // Restore FKs
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('guard_id')->references('id')->on('security_guards')->cascadeOnDelete();
        });
    }
};
