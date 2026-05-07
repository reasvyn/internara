<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('super_admin_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('target_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('requested_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('change_type', 20); // password, email, settings, deactivate, roles
            $table->json('change_data')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending, approved, rejected
            $table->integer('approvals_count')->default(0);
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['target_user_id', 'status']);
            $table->index(['requested_by_user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_approvals');
    }
};
