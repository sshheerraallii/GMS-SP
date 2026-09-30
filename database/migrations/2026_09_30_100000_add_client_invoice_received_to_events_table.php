<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V4-P1: per-event Yes/No — has the client's invoice been received?
     * Shown and edited on the Guard Payment Report. Not linked to
     * invoices.status (manual flag, by decision).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('client_invoice_received')->default(false)->after('staff_pay_date');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('client_invoice_received');
        });
    }
};
