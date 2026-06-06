<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('account_recovery_codes');

        Schema::table('activation_tokens', function (Blueprint $table) {
            $table->dropUnique('unique_active_token');
            $table->unique(['user_id', 'token_type', 'token'], 'unique_active_token');
            $table->dateTime('expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('activation_tokens', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable(false)->change();
            $table->dropUnique('unique_active_token');
            $table->unique(['user_id', 'token_type'], 'unique_active_token');
        });
    }
};
