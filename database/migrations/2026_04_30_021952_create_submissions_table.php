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
            $table->foreignUuid('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->foreignUuid('registration_id')->constrained('registrations')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('users')->onDelete('cascade');

            $table->text('content')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 20)->default('draft');

            $table->float('score')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignUuid('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('graded_at')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['assignment_id', 'status']);
            $table->index(['registration_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
