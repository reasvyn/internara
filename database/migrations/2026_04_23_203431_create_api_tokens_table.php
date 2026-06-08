<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('token')->index();
            $table->string('token_type', 20)->default('email');
            $table->string('name')->nullable();
            $table->text('scopes')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'token_type', 'token'], 'api_tokens_unique_active');
            $table->index(['expires_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};