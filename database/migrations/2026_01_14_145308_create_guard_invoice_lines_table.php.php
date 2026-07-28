<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guard_invoice_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('guard_invoice_id')
                ->constrained('guard_invoices')
                ->cascadeOnDelete();

            $table->unsignedInteger('line_no')->default(1);

            $table->date('service_date');
            $table->string('description')->nullable();

            $table->time('shift_start');
            $table->time('shift_end');

            $table->decimal('hours', 10, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);

            $table->timestamps();

            $table->index(['guard_invoice_id', 'service_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guard_invoice_lines');
    }
};
