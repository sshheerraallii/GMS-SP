<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V3-P5: one staff pay date per event.
     *
     * Nullable — existing events have none and are simply never included
     * in the pay-date reminder feed. No backfill.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('staff_pay_date')->nullable()->after('invoice_date')
                ->comment('Date staff are due to be paid for this event');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('staff_pay_date');
        });
    }
};
