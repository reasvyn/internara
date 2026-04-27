<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Enhanced login history table
        if (!Schema::hasTable('login_history')) {
            Schema::create('login_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('ip_address')->index();
                $table->string('user_agent')->nullable();
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
        }

        // Suspicious login attempts table
        if (!Schema::hasTable('suspicious_login_attempts')) {
            Schema::create('suspicious_login_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('ip_address');
                $table->string('user_agent')->nullable();
                $table->json('suspicions'); // Array of reasons (impossible travel, simultaneous login, etc)
                $table->json('actions_taken'); // Array of actions (force_reauthentication, notify_user, etc)
                $table
                    ->enum('severity', ['low', 'medium', 'high', 'critical'])
                    ->default('medium')
                    ->index();
                $table->boolean('user_verified')->default(false); // User confirmed this was them?
                $table->dateTime('detected_at')->index();
                $table->dateTime('resolved_at')->nullable();
                $table->timestamps();

                // Indexes for compliance queries
                $table->index(['user_id', 'detected_at']);
                $table->index(['severity', 'resolved_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('suspicious_login_attempts');
        Schema::dropIfExists('login_history');
    }
};
