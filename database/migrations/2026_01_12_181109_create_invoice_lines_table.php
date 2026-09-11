<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->unsignedInteger('line_no')->default(1);

            $table->date('service_date');

            $table->string('description')->default('Provision of Guards');

            $table->time('shift_start');
            $table->time('shift_end');

            $table->unsignedInteger('quantity')->default(0);

            // hours per guard for that shift (e.g. 4.00)
            $table->decimal('hours', 8, 2)->default(0);

            // rate charged to client (charge_rate snapshot)
            $table->decimal('rate', 12, 2)->default(0);

            // quantity * hours * rate
            $table->decimal('amount', 14, 2)->default(0);

            $table->timestamps();

            $table->index(['invoice_id', 'service_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
