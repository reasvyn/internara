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
        Schema::create('monitoring_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('internship_registrations')->onDelete('cascade');
            $table->foreignUuid('teacher_id')->constrained('users');
            $table->date('date');
            $table->text('notes');
            $table->text('company_feedback')->nullable();
            $table->text('student_condition')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();

            $table->index('registration_id');
            $table->index(['teacher_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_visits');
    }
};
