<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4: structured site location (Postcode / Site Name / Site
     * Address) plus a per-shift soft-cancel flag.
     *
     * - The three site_* columns live on BOTH event_assignment_slots
     *   (the assignment grid's prefill source) and event_shifts (the
     *   downstream billing rows). The legacy `location` column stays and
     *   is auto-composed from the three, so timesheets / chaseup /
     *   invoicing keep working unchanged.
     * - cancelled_at is added on event_shifts now (cheap) but stays
     *   unused until Phase 4b wires the cancel toggle + zeroing.
     * All columns are nullable so existing rows are grandfathered.
     */
    public function up(): void
    {
        Schema::table('event_assignment_slots', function (Blueprint $table) {
            $table->string('site_postcode', 255)->nullable()->after('location');
            $table->string('site_name', 255)->nullable()->after('site_postcode');
            $table->string('site_address', 255)->nullable()->after('site_name');
        });

        Schema::table('event_shifts', function (Blueprint $table) {
            $table->string('site_postcode', 255)->nullable()->after('location');
            $table->string('site_name', 255)->nullable()->after('site_postcode');
            $table->string('site_address', 255)->nullable()->after('site_name');
            $table->timestamp('cancelled_at')->nullable()->after('site_address');
        });
    }

    public function down(): void
    {
        Schema::table('event_assignment_slots', function (Blueprint $table) {
            $table->dropColumn(['site_postcode', 'site_name', 'site_address']);
        });

        Schema::table('event_shifts', function (Blueprint $table) {
            $table->dropColumn(['site_postcode', 'site_name', 'site_address', 'cancelled_at']);
        });
    }
};
