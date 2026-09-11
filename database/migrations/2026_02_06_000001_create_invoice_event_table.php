<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_event', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('event_id');
            $table->timestamps();

            $table->primary(['invoice_id', 'event_id']);

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();

            $table->index('event_id');
        });

        // Backfill: for every existing invoice, attach its event_id into the pivot
        // Using INSERT IGNORE to be safe if rerun.
        DB::statement("
            INSERT IGNORE INTO invoice_event (invoice_id, event_id, created_at, updated_at)
            SELECT id, event_id, NOW(), NOW()
            FROM invoices
            WHERE event_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_event');
    }
};
