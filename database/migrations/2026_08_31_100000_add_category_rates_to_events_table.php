<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V3-P3: per-category pay and charge rates.
     *
     * Guards carry a category (`security_guards.category` = SIA | Steward)
     * and the two categories are not always billed or paid at the same
     * rate. These four columns hold the per-category rates.
     *
     * All four are NULLABLE and there is deliberately NO BACKFILL. The
     * existing `events.pay_rate` / `events.charge_rate` columns are left
     * untouched and act as the fallback, so every existing event keeps
     * behaving exactly as it does today until someone sets a category
     * rate on it. Adding columns does not require doctrine/dbal.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('pay_rate_sia', 8, 2)->nullable()->after('pay_rate')
                ->comment('Per hour, SIA guards. NULL = fall back to pay_rate');
            $table->decimal('pay_rate_steward', 8, 2)->nullable()->after('pay_rate_sia')
                ->comment('Per hour, Steward guards. NULL = fall back to pay_rate');
            $table->decimal('charge_rate_sia', 8, 2)->nullable()->after('charge_rate')
                ->comment('Per hour, SIA guards. NULL = fall back to charge_rate');
            $table->decimal('charge_rate_steward', 8, 2)->nullable()->after('charge_rate_sia')
                ->comment('Per hour, Steward guards. NULL = fall back to charge_rate');
        });
    }

    /**
     * Reverse: drop the four columns. No data loss beyond the category
     * rates themselves, since the base rates were never modified.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'pay_rate_sia',
                'pay_rate_steward',
                'charge_rate_sia',
                'charge_rate_steward',
            ]);
        });
    }
};
