<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V3-P4: per-event, per-guard payment tracking.
     *
     * Holds ONLY the editable tracking fields. Hours, rate and amount are
     * always computed live from event_shifts + the V3-P3 rate resolution,
     * never stored here, so the sheet cannot drift from the shift data.
     *
     * Unique on (event_id, guard_id): one payment row per guard per event.
     */
    public function up(): void
    {
        Schema::create('event_guard_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('guard_id')
                ->constrained('security_guards')
                ->cascadeOnDelete();

            $table->string('paid_by')->nullable()->comment('Free text — who made the payment');
            $table->date('paid_on')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'guard_id'], 'event_guard_payments_event_guard_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_guard_payments');
    }
};
