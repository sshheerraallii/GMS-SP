<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make events.charge_rate nullable so an event can be created
     * without a charge rate (the Accountant / Super Admin fills it in
     * later). Raw SQL is used deliberately: this project has no
     * doctrine/dbal, so Schema ->change() is unavailable. Existing
     * values are preserved untouched.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `events` MODIFY `charge_rate` DECIMAL(8,2) NULL COMMENT 'Per hour'");
    }

    /**
     * Reverse: restore NOT NULL. Backfill any NULLs to 0.00 first so
     * the constraint can be re-applied without error.
     */
    public function down(): void
    {
        DB::statement("UPDATE `events` SET `charge_rate` = 0.00 WHERE `charge_rate` IS NULL");
        DB::statement("ALTER TABLE `events` MODIFY `charge_rate` DECIMAL(8,2) NOT NULL COMMENT 'Per hour'");
    }
};
