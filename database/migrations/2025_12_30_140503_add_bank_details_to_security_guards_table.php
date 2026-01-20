<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            $table->string('sort_code', 6)->nullable()->after('city');
            $table->string('account_number', 8)->nullable()->after('sort_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            $table->dropColumn(['sort_code', 'account_number']);
        });
    }
};
