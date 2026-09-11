<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6: per-client colour (hex) used to tint the client's rows
     * on chaseup, the events index and reports. Nullable — a client
     * with no colour set is simply not tinted (existing behaviour).
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
