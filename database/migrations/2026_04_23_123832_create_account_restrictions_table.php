<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_restrictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table
                ->string('restriction_type')
                ->comment('Type: module|feature|rate_limit|schedule|geolocation');
            $table
                ->string('restriction_key')
                ->comment(
                    'What is being restricted (e.g., "export", "bulk_action", "report_download")',
                );
            $table
                ->text('restriction_value')
                ->comment(
                    'Constraint details (e.g., "max_daily_attempts:5", "allowed_ips:192.168.*")',
                );
            $table->text('reason')->nullable()->comment('Why this restriction was applied');
            $table->uuid('applied_by_user_id')->index()->comment('Admin who applied restriction');
            $table->timestamp('applied_at');
            $table
                ->timestamp('expires_at')
                ->nullable()
                ->comment('Optional: auto-lift after this date');
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable()->comment('Additional context');
            $table->timestamps();

            // Composite index for active restrictions
            $table->index(['user_id', 'is_active', 'expires_at']);

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_restrictions');
    }
};
