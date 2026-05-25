<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('evaluator_id')->constrained('users')->onDelete('cascade')->index();
            $table->string('evaluation_type', 50)->default('mentor')->index();
            $table->foreignUuid('mentor_id')->nullable()->constrained('users')->onDelete('set null')->index();
            $table->foreignUuid('registration_id')->nullable()->constrained('registrations')->onDelete('set null');
            $table->index('registration_id');
            $table->string('target_type', 50)->nullable();
            $table->string('target_id', 36)->nullable();
            $table->float('overall_score')->index();
            $table->text('feedback')->nullable();
            $table->json('criteria_scores')->nullable();
            $table->timestamps();

            $table->index(['mentor_id', 'created_at']);
            $table->index(['mentor_id', 'evaluation_type']);
            $table->index(['evaluator_id', 'overall_score']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
