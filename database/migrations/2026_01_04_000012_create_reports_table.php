<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('registration_id')
                ->nullable()
                ->unique()
                ->constrained('registrations')
                ->nullOnDelete();
            $table->float('supervisor_score')->nullable();
            $table->float('teacher_score')->nullable();
            $table->float('exam_score')->nullable();
            $table->float('final_score')->nullable();
            $table->string('grade_letter')->nullable();
            $table->text('industry_feedback')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignUuid('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();

            $table->json('archived_data')->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->text('content')->nullable();
            $table->string('title')->nullable();
            $table->json('chapter_structure')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
