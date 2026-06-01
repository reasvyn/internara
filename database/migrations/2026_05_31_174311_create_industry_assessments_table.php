<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('registrations')->onDelete('cascade');
            $table->foreignUuid('supervisor_id')->constrained('users')->onDelete('cascade');
            $table->decimal('score', 5, 2)->nullable();
            $table->json('rubric_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['registration_id', 'supervisor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_assessments');
    }
};
