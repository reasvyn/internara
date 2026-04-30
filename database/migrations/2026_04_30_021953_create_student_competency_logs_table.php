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
            $table->uuid('registration_id');
            $table->uuid('competency_id');
            $table->uuid('evaluator_id');
            $table->float('score');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('internship_registrations')->onDelete('cascade');
            $table->foreign('competency_id')->references('id')->on('competencies')->onDelete('cascade');
            $table->foreign('evaluator_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['registration_id', 'competency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_competency_logs');
    }
};
