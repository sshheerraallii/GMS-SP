<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('security_guards', function (Blueprint $table) {

            $table->string('act_blue')->nullable();
            $table->string('act_orange')->nullable();
            $table->string('act_green')->nullable();

            $table->string('first_aid')->nullable();

            $table->string('cctv_front')->nullable();
            $table->string('cctv_back')->nullable();

            $table->string('other_doc1')->nullable();
            $table->string('other_doc2')->nullable();

        });
    }

    public function down()
    {
        Schema::table('security_guards', function (Blueprint $table) {

            $table->dropColumn([
                'act_blue',
                'act_orange',
                'act_green',
                'first_aid',
                'cctv_front',
                'cctv_back',
                'other_doc1',
                'other_doc2',
            ]);

        });
    }
};