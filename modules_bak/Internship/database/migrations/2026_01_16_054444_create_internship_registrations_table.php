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
        Schema::create('internship_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('internship_id')
                ->index()
                ->constrained('internships')
                ->cascadeOnDelete();
            $table
                ->foreignUuid('placement_id')
                ->nullable()
                ->index()
                ->constrained('internship_placements')
                ->nullOnDelete();
            $table->uuid('student_id')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('proposed_company_name')->nullable();
            $table->text('proposed_company_address')->nullable();
            $table->string('academic_year', 10)->nullable()->index();
            $table->uuid('teacher_id')->nullable()->index();
            $table->uuid('mentor_id')->nullable()->index();
            $table->timestamps();
            // Composite indexes for common query patterns
            $table->index(['student_id', 'academic_year']);
            $table->index(['internship_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internship_registrations');
    }
};
