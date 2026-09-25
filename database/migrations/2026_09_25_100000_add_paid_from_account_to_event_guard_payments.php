<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guard payment report: record WHICH account a guard was paid from.
     *
     * Free text by decision. Lives on the existing event_guard_payments
     * row so the event pay sheet (V3-P4) and the new guard payment report
     * remain two views of one record — never two copies.
     */
    public function up(): void
    {
        Schema::table('event_guard_payments', function (Blueprint $table) {
            $table->string('paid_from_account')->nullable()->after('paid_by')
                ->comment('Free text — which account the payment was made from');
        });
    }

    public function down(): void
    {
        Schema::table('event_guard_payments', function (Blueprint $table) {
            $table->dropColumn('paid_from_account');
        });
    }
};
