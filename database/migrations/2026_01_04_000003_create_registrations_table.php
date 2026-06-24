<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('internship_id')->constrained('internships')->onDelete('cascade');
            $table
                ->foreignUuid('placement_id')
                ->nullable()
                ->constrained('placements')
                ->onDelete('set null');
            $table->index('placement_id');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('pending')->index();
            $table->json('proposed_company_details')->nullable();

            $table->timestamps();

            // Unique index to prevent duplicate registrations per student per internship
            $table->unique(['student_id', 'internship_id'], 'reg_student_internship_unique');
            $table->index(['student_id', 'status']);
            $table->index(['internship_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
