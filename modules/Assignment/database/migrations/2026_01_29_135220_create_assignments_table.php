<?php

declare(strict_types=1);

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
        Schema::create('assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('assignment_type_id')
                ->constrained('assignment_types')
                ->cascadeOnDelete();
            $table->uuid('internship_id')->nullable()->index();
            $table->string('academic_year', 10)->nullable()->index();
            $table->string('title');
            $table->string('group')->default('deliverable')->index(); // prerequisite, deliverable
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->dateTime('due_date')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
