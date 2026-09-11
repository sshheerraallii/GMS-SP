<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            $table->string('beneficiary_name', 255)->nullable()->after('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            $table->dropColumn('beneficiary_name');
        });
    }
};
