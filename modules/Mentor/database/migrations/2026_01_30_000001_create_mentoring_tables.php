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
        Schema::create('mentoring_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('registration_id')->index();
            $table->uuid('teacher_id')->index(); // The visiting teacher
            $table->date('visit_date');
            $table->text('notes')->nullable();
            $table->json('findings')->nullable(); // Technical findings, soft skills, etc.
            $table->timestamps();
        });

        Schema::create('mentoring_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('registration_id')->index();
            $table->uuid('causer_id')->index(); // Teacher or Mentor who gives feedback
            $table->string('type')->default('feedback'); // session, feedback, advisory
            $table->string('subject');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentoring_logs');
        Schema::dropIfExists('mentoring_visits');
    }
};
