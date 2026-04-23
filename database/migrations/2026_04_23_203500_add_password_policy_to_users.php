<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add password policy tracking columns
            $table->dateTime('password_changed_at')->nullable()->after('password');
            $table->dateTime('password_reset_required_at')->nullable()->after('password_changed_at');
            $table->timestamp('last_activity_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_changed_at', 'password_reset_required_at', 'last_activity_at']);
        });
    }
};
