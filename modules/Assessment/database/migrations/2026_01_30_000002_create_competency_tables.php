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
        Schema::create('competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('technical'); // technical, softskill
            $table->timestamps();
        });

        Schema::create('department_competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('department_id')->index();
            $table->uuid('competency_id')->index();
            $table->integer('weight')->default(1); // Importance weight
            $table->timestamps();
        });

        Schema::create('student_competency_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('registration_id')->index();
            $table->uuid('competency_id')->index();
            $table->integer('score')->default(0); // 0-100
            $table->text('notes')->nullable();
            $table->string('proof_url')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_competency_logs');
        Schema::dropIfExists('department_competencies');
        Schema::dropIfExists('competencies');
    }
};
