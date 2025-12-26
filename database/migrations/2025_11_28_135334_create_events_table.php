<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            
            $table->string('event_name');
            $table->string('address');
            
            // Client reference (from clients table)
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            
            $table->decimal('charge_rate', 8, 2)->comment('Per hour');
            $table->date('invoice_date');
            $table->decimal('pay_rate', 8, 2)->comment('Per hour');
            
            // Assigned guards (from security_guards table using license_number)
            $table->json('assigned_guards'); // we'll store an array of license numbers
            
            $table->string('client_contact');
            $table->text('instructions')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
