<?php

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
        Schema::create('assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Operational Context
            $table->uuid('registration_id')->index();
            $table->string('academic_year')->nullable()->index();
            
            // Evaluation Metadata
            $table->uuid('evaluator_id')->nullable()->index();
            $table->string('type')->index(); // 'mentor' or 'teacher'
            
            // Content
            $table->decimal('score', 5, 2)->default(0);
            $table->json('content')->nullable(); // Detailed criteria breakdown
            $table->text('feedback')->nullable();
            
            // Status
            $table->timestamp('finalized_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};