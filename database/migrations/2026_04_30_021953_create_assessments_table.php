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
            $table->uuid('registration_id');
            $table->string('academic_year', 9)->nullable();
            $table->uuid('evaluator_id');
            $table->string('type', 20)->default('final');
            $table->float('score')->nullable();
            $table->json('content')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('internship_registrations')->onDelete('cascade');
            $table->foreign('evaluator_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['registration_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
