<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * onboarding_batches groups accounts created together in a single admin operation
     * (e.g. "Siswa Kelas 10 Batch 2026", "Guru Semester Ganjil 2026").
     * This enables bulk credential-slip export and claim-status monitoring per cohort.
     */
    public function up(): void
    {
        Schema::create('onboarding_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');

            // The role/stakeholder type this batch targets
            $table->string('type'); // student | teacher | mentor

            // Lifecycle: draft → issued → archived
            $table->string('status')->default('draft');

            $table->text('notes')->nullable();

            // When the batch was formally issued (credential slips distributed)
            $table->timestamp('issued_at')->nullable();

            // Admin who created this batch
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_batches');
    }
};
