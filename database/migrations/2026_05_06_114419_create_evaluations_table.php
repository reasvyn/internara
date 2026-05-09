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
            $table->foreignUuid('mentor_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignUuid('registration_id')->nullable()->constrained('internship_registrations')->onDelete('set null')->index();
            $table->float('overall_score')->index();
            $table->text('feedback')->nullable();
            $table->json('criteria_scores')->nullable();
            $table->timestamps();

            $table->index(['mentor_id', 'created_at']);
            $table->index(['evaluator_id', 'overall_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
