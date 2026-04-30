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
        Schema::create('requirement_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('registration_id')
                ->constrained('internship_registrations')
                ->cascadeOnDelete();
            $table
                ->foreignUuid('requirement_id')
                ->constrained('internship_requirements')
                ->cascadeOnDelete();
            $table->text('value')->nullable(); // For skills or conditions
            $table->string('status')->default('pending'); // pending, verified, rejected
            $table->text('notes')->nullable(); // Admin feedback
            $table->timestamp('verified_at')->nullable();
            $table->uuid('verified_by')->nullable()->index();
            $table->timestamps();

            // A student should only have one submission per requirement per registration
            $table->unique(
                ['registration_id', 'requirement_id'],
                'registration_requirement_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requirement_submissions');
    }
};
