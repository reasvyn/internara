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
            $table->unsignedTinyInteger('current_phase_index')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'internship_id']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
