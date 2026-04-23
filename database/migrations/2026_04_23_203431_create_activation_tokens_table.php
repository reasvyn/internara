<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activation_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->index(); // Hashed token
            $table->enum('token_type', ['email', 'sms'])->default('email');
            $table->dateTime('expires_at')->index();
            $table->integer('attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->timestamps();

            // Ensure one active token per user per type
            $table->unique(['user_id', 'token_type'], 'unique_active_token');

            // Index for cleanup queries
            $table->index(['expires_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_tokens');
    }
};
