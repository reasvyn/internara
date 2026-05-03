<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignment_type_id');
            $table->uuid('internship_id');
            $table->string('academic_year', 9)->nullable();
            $table->string('title');
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->timestamp('due_date')->nullable();
            $table->json('config')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table
                ->foreign('assignment_type_id')
                ->references('id')
                ->on('assignment_types')
                ->onDelete('cascade');
            $table
                ->foreign('internship_id')
                ->references('id')
                ->on('internships')
                ->onDelete('cascade');
            $table->index(['internship_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
