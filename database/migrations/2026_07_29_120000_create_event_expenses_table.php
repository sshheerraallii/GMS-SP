<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 9: per-event extra expenses, used on the executive homepage
     * to compute profit = charge - pay - expenses.
     */
    public function up(): void
    {
        Schema::create('event_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('label');
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_expenses');
    }
};
