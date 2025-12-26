<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            // New Fields
            $table->string('email_address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('license_number')->nullable(); // change CIN to License #
            $table->date('license_exp_date')->nullable();
            $table->string('rtw_share_code')->nullable();
            $table->string('visa_status')->nullable(); // will hold values like British, Student, etc.
            $table->string('ni_number')->nullable();
            $table->string('driving_license')->nullable(); // International / UK / No
            $table->string('car')->nullable(); // Yes / No
            $table->string('city')->nullable();

            // Document attachments
            $table->string('profile_picture')->nullable(); // was choose file
            $table->string('sia_license')->nullable();
            $table->string('driving_license_doc')->nullable();
            $table->string('passport')->nullable();
            $table->string('evisa_ss')->nullable();
            $table->string('rtw_ss')->nullable();
            $table->string('proof_add1')->nullable();
            $table->string('proof_add2')->nullable();
            $table->string('ni_letter')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            $table->dropColumn([
                'email_address',
                'phone_number',
                'license_number',
                'license_exp_date',
                'rtw_share_code',
                'visa_status',
                'ni_number',
                'driving_license',
                'car',
                'city',
                'profile_picture',
                'sia_license',
                'driving_license_doc',
                'passport',
                'evisa_ss',
                'rtw_ss',
                'proof_add1',
                'proof_add2',
                'ni_letter'
            ]);
        });
    }
};
