<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdpr_deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Nullable after deletion
            $table->string('user_email')->index();
            $table
                ->enum('deletion_type', ['anonymization', 'permanent_deletion'])
                ->default('anonymization');
            $table->string('reason');
            $table->foreignId('deleted_by')->constrained('users')->onDelete('cascade');
            $table->json('metadata')->nullable(); // User data snapshot for audit
            $table->dateTime('deleted_at');
            $table->timestamps();

            // Index for deletion audit queries
            $table->index(['deleted_at', 'deletion_type']);
            $table->index('deleted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdpr_deletion_logs');
    }
};
