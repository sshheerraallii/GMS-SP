<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // One client-invoice per event (per your rule)
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 64)->unique();

            $table->string('status', 20)->default('draft'); // draft|issued|paid|void
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            // Snapshots (so later changes to event/client won't rewrite old invoices)
            $table->string('event_name_snapshot')->nullable();
            $table->string('client_type_snapshot', 20)->nullable(); // VAT|Non VAT
            $table->decimal('charge_rate_snapshot', 12, 2)->default(0);

            // VAT snapshot
            $table->decimal('vat_rate', 5, 4)->default(0); // 0.2000 or 0.0000

            // Supplier/from section
            $table->string('supplier_name_snapshot')->nullable();

            // Editable notes (prefill only for Secure Premises LTD, else empty)
            $table->text('payment_notes')->nullable();

            // Totals snapshot
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // PDF storage
            $table->string('pdf_path')->nullable();

            $table->timestamps();

            $table->unique('event_id');
            $table->index(['status', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
