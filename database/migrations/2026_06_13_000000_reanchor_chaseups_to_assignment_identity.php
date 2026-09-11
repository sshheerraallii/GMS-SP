<?php
// database/migrations/2026_06_13_000000_reanchor_chaseups_to_assignment_identity.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Stable business-identity columns (nullable -> safe on existing rows)
        Schema::table('chaseups', function (Blueprint $table) {
            if (!Schema::hasColumn('chaseups', 'event_id')) {
                $table->unsignedBigInteger('event_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('chaseups', 'guard_id')) {
                $table->unsignedBigInteger('guard_id')->nullable()->after('event_id');
            }
        });

        // 2. Drop the destructive cascade FK + its unique key (this is what hard-deleted notes)
        $this->dropForeignKeyIfExists('chaseups', 'chaseups_event_shift_id_foreign');
        $this->dropIndexIfExists('chaseups', 'chaseups_event_shift_id_unique');

        // 3. event_shift_id is no longer the identity; allow NULL
        DB::statement('ALTER TABLE `chaseups` MODIFY `event_shift_id` BIGINT UNSIGNED NULL');

        // 4. New stable identity: one live note per (event, date, guard)
        //    Existing rows have event_id/guard_id = NULL, and MySQL treats NULLs as distinct,
        //    so they will NOT collide with each other or with new rows.
        Schema::table('chaseups', function (Blueprint $table) {
            $table->unique(['event_id', 'date', 'guard_id'], 'chaseups_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chaseups', function (Blueprint $table) {
            $table->dropUnique('chaseups_assignment_unique');
            $table->dropColumn(['event_id', 'guard_id']);
        });
        // The old CASCADE FK is intentionally NOT recreated.
    }

    private function dropForeignKeyIfExists(string $table, string $fk): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $fk)
            ->exists();
        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
};