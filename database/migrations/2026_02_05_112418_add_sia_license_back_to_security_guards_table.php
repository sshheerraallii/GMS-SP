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
        $table->string('sia_license_back')->nullable()->after('sia_license');
    });
}

public function down(): void
{
    Schema::table('security_guards', function (Blueprint $table) {
        $table->dropColumn('sia_license_back');
    });
}

};
