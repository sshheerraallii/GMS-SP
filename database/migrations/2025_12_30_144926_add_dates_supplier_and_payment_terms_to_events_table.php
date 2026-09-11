<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('events', function (Blueprint $table) {

        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();

        $table->foreignId('supplier_id')
            ->nullable()
            ->constrained('suppliers')
            ->nullOnDelete();

        $table->string('payment_terms')
            ->nullable()
            ->comment('weekly, biweekly, monthly, days_55, weeks_5');
    });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {

            $table->dropForeign(['supplier_id']);

            $table->dropColumn([
                'start_date',
                'end_date',
                'supplier_id',
                'payment_terms',
            ]);
        });
    }
};
