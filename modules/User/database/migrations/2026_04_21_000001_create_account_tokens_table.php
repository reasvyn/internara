<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * account_tokens stores single-use activation / credential-reset codes
     * for users who may not have a valid email address.
     *
     * Design decisions:
     * - token is stored as HMAC-SHA256 (never plaintext). The plaintext code
     *   is only returned once to the issuing admin and never persisted.
     * - expires_at enforces a time-bounded window; claimed_at marks consumption.
     * - issued_by records the admin who generated the token for audit purposes.
     */
    public function up(): void
    {
        Schema::create('account_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->index();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // 'activation'        — first-time account claim (username not yet set by user)
            // 'credential_reset'  — admin-initiated re-entry for existing users without email
            $table->string('type')->default('activation');

            // HMAC-SHA256 of the plaintext code (never store plaintext)
            $table->string('token', 64)->index();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('claimed_at')->nullable();

            // Admin / system actor who generated this token
            $table->uuid('issued_by')->nullable();
            $table->foreign('issued_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // IP address recorded at claim time for audit trail
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            // Composite index for fast "find active token for user + type" lookups
            $table->index(['user_id', 'type', 'claimed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_tokens');
    }
};
