<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** V4-P4: free-text sign-off names on each chaseup row. */
    public function up(): void
    {
        Schema::table('chaseups', function (Blueprint $table) {
            $table->string('approved_by')->nullable()->after('comments');
            $table->string('verified_by')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('chaseups', function (Blueprint $table) {
            $table->dropColumn(['approved_by', 'verified_by']);
        });
    }
};
