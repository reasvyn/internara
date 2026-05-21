<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhanced login history table
        Schema::create('login_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address')->index();
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->default(false)->index();
            $table->string('failure_reason')->nullable(); // 'wrong_password', 'account_locked', 'mfa_failed', etc
            $table->double('latitude')->nullable(); // Geolocation
            $table->double('longitude')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('device_fingerprint')->nullable(); // Hash of device characteristics
            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });

        // Suspicious login attempts table
        Schema::create('suspicious_login_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->text('user_agent')->nullable();
            $table->json('suspicions'); // Array of reasons (impossible travel, simultaneous login, etc)
            $table->json('actions_taken'); // Array of actions (force_reauthentication, notify_user, etc)
            $table
                ->string('severity', 20)
                ->default('medium')
                ->index(); // low, medium, high, critical
            $table->boolean('user_verified')->default(false); // User confirmed this was them?
            $table->dateTime('detected_at')->index();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            // Indexes for compliance queries
            $table->index(['user_id', 'detected_at']);
            $table->index(['severity', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspicious_login_attempts');
        Schema::dropIfExists('login_history');
    }
};
