<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_competency_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('internship_registrations')->onDelete('cascade');
            $table->foreignUuid('competency_id')->constrained('competencies')->onDelete('cascade');
            $table->foreignUuid('evaluator_id')->constrained('users')->onDelete('cascade');
            $table->float('score');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['registration_id', 'competency_id'], 'reg_comp_idx');
            $table->index(['registration_id', 'evaluator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_competency_logs');
    }
};
