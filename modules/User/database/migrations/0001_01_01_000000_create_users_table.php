<?php

declare(strict_types=1);

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
        Schema::create('users', function (Blueprint $table) {
            if (config('user.type_id') === 'uuid') {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }
            $table->string('name')->index();
            // Email is optional — username is the primary identity.
            // Institutions often create accounts before valid email is available.
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->unique();
            $table->string('password');

            // Generic signal that this account has pending setup steps.
            // True = account was provisioned but the user has not yet completed
            // the initial claim/activation flow. What "setup" means can evolve
            // (password change, profile completion, identity verification, etc.)
            // without requiring a schema change.
            $table->boolean('setup_required')->default(false);

            // Nullable reference to the onboarding batch that created this account.
            // No FK constraint — onboarding_batches table created in a separate migration.
            $table->uuid('onboarding_batch_id')->nullable()->index();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            if (config('user.type_id') === 'uuid') {
                $table->foreignUuid('user_id')->nullable()->index();
            } else {
                $table->foreignId('user_id')->nullable()->index();
            }
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
