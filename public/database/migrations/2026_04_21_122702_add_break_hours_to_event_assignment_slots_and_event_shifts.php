<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_assignment_slots', function (Blueprint $table) {
            $table->decimal('break_hours', 4, 2)->default(0)->after('end_time');
        });

        Schema::table('event_shifts', function (Blueprint $table) {
            $table->decimal('break_hours', 4, 2)->default(0)->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('event_assignment_slots', function (Blueprint $table) {
            $table->dropColumn('break_hours');
        });

        Schema::table('event_shifts', function (Blueprint $table) {
            $table->dropColumn('break_hours');
        });
    }
};