<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V4-P3: company-wide expenses that belong to no event and no client
     * (overhead). Date is required and decides which Executive date range
     * the expense counts in. Deliberately separate from event_expenses.
     */
    public function up(): void
    {
        Schema::create('general_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->decimal('amount', 14, 2);
            $table->date('date');
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('date', 'general_expenses_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_expenses');
    }
};
