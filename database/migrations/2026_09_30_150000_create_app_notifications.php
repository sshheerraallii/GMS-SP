<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // V4-P6: optional real email (users.email is the login username).
        if (!Schema::hasColumn('users', 'contact_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('contact_email', 255)->nullable()->after('email');
            });
        }

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('url', 500)->nullable();
            // ",finance,ops," style list; "," + "all" + "," = everyone.
            $table->string('audience', 100)->default(',all,');
            $table->string('dedupe_key', 191)->nullable()->unique();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('app_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('notification_id');
            $table->timestamp('read_at')->useCurrent();
            $table->unique(['user_id', 'notification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notification_reads');
        Schema::dropIfExists('app_notifications');
        if (Schema::hasColumn('users', 'contact_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('contact_email');
            });
        }
    }
};
