<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_shifts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained('security_guards')->cascadeOnDelete();

            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->unsignedTinyInteger('shift_no')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'date', 'start_time', 'end_time'], 'event_shifts_unique_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_shifts');
    }
};
