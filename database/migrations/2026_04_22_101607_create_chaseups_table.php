<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chaseups', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('event_shift_id');
            $table->date('date');

            $table->text('reminder')->nullable();

            // blank / yes / no
            $table->string('all_ok', 10)->nullable();
            $table->text('all_ok_response')->nullable();

            // blank / yes / no
            $table->string('on_way', 10)->nullable();
            $table->text('on_way_response')->nullable();

            $table->time('reaching_time')->nullable();
            $table->text('book_on')->nullable();
            $table->text('comments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('event_shift_id', 'chaseups_event_shift_id_unique');
            $table->index(['date'], 'chaseups_date_index');

            $table->foreign('event_shift_id', 'chaseups_event_shift_id_foreign')
                ->references('id')
                ->on('event_shifts')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chaseups');
    }
};