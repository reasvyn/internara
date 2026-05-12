<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('internship_registrations')->onDelete('cascade');
            $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignUuid('rubric_id')->nullable()->constrained('rubrics')->nullOnDelete();
            $table->foreignUuid('evaluator_id')->constrained('users')->onDelete('cascade');

            $table->string('type', 20)->default('final'); // midterm, final, periodic
            $table->float('score')->nullable();
            $table->json('content')->nullable()->comment('Detailed scores per criteria/competency');
            $table->text('feedback')->nullable();

            $table->timestamp('finalized_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['registration_id', 'type']);
            $table->index(['registration_id', 'academic_year_id']);
            $table->index(['evaluator_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
