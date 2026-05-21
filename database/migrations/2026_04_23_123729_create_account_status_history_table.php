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
        Schema::create('account_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table
                ->string('old_status')
                ->nullable()
                ->comment('Previous status (null if first entry)');
            $table->string('new_status')->comment('New status after transition');
            $table->text('reason')->nullable()->comment('Reason for status change');
            $table
                ->foreignUuid('triggered_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who triggered change (null if system)');
            $table
                ->string('triggered_by_role')
                ->nullable()
                ->comment('Role of user who triggered (for audit)');
            $table
                ->string('ip_address')
                ->nullable()
                ->comment('IP address where change was triggered');
            $table->text('user_agent')->nullable()->comment('Browser/client info');
            $table
                ->json('metadata')
                ->nullable()
                ->comment('Additional context (restrictions, expiry, etc.)');
            $table->timestamp('created_at');

            // Indexes for audit queries
            $table->index(['user_id', 'created_at']);
            $table->index(['triggered_by_user_id', 'created_at']);
            $table->index('new_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_status_history');
    }
};
