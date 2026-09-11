<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guard_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained('security_guards')->cascadeOnDelete();

            // separate numbering from client invoices (we'll generate later)
            $table->string('invoice_number')->unique();

            $table->enum('status', ['draft', 'issued', 'paid', 'voided'])->default('draft');

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            // snapshots (so issued invoices remain stable even if event/pay rate changes)
            $table->string('event_name_snapshot')->nullable();
            $table->decimal('pay_rate_snapshot', 10, 2)->default(0);

            $table->decimal('total_hours', 10, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->string('pdf_path')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Core rule: exactly 1 invoice per guard per event
            $table->unique(['event_id', 'guard_id'], 'guard_invoices_event_guard_unique');
            $table->index(['status', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guard_invoices');
    }
};
