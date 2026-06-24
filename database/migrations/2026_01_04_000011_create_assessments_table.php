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
            $table
                ->foreignUuid('registration_id')
                ->constrained('registrations')
                ->onDelete('cascade');
            $table->foreignUuid('evaluator_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('rubric_id')->nullable()->constrained('rubrics')->nullOnDelete();
            $table->index('rubric_id');

            $table->string('assessment_type', 30)->default('final'); // midterm | final | periodic | industry
            $table->float('score')->nullable();
            $table
                ->json('scores_data')
                ->nullable()
                ->comment('Detailed scores per criteria/competency');
            $table->text('feedback')->nullable();

            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['registration_id', 'assessment_type', 'evaluator_id']);
            $table->index(['registration_id', 'assessment_type']);
            $table->index(['evaluator_id', 'assessment_type']);
            $table->index('assessment_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
