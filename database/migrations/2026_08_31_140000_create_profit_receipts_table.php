<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V3-P7: manually entered profit received, per client, on a date.
     *
     * Deliberately NOT linked to invoices.status — confirmed: there is no
     * linkage. This is a standalone ledger of profit actually received.
     * The date is what lets Received/Remaining respect the executive date
     * range, exactly as event_expenses already does.
     */
    public function up(): void
    {
        Schema::create('profit_receipts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->decimal('amount', 14, 2);
            $table->date('received_on');
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['client_id', 'received_on'], 'profit_receipts_client_date_idx');
            $table->index('received_on', 'profit_receipts_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_receipts');
    }
};
