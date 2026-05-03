<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignment_id');
            $table->uuid('registration_id');
            $table->uuid('student_id');
            $table->text('content')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table
                ->foreign('assignment_id')
                ->references('id')
                ->on('assignments')
                ->onDelete('cascade');
            $table
                ->foreign('registration_id')
                ->references('id')
                ->on('internship_registrations')
                ->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
