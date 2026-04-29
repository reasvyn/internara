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
        Schema::create('internship_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('internship_id')->constrained('internships')->onDelete('cascade');
            $table->foreignUuid('placement_id')->nullable()->constrained('internship_placements')->onDelete('set null');
            
            $table->foreignUuid('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('mentor_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('academic_year')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            $table->string('proposed_company_name')->nullable();
            $table->text('proposed_company_address')->nullable();

            $table->timestamps();
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
