<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_assignment_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->date('date');
            $table->unsignedInteger('slot_no');

            $table->foreignId('guard_id')
                ->nullable()
                ->constrained('security_guards')
                ->nullOnDelete();

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'date', 'slot_no'], 'event_assignment_slots_unique_slot');
            $table->index(['event_id', 'date'], 'event_assignment_slots_event_date_idx');
            $table->index(['guard_id'], 'event_assignment_slots_guard_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_assignment_slots');
    }
};