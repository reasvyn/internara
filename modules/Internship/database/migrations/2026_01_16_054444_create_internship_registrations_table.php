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
        Schema::create('internship_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('internship_id')->constrained('internships')->cascadeOnDelete();
            $table
                ->foreignUuid('placement_id')
                ->nullable()
                ->constrained('internship_placements')
                ->nullOnDelete();
            $table->uuid('student_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internship_registrations');
    }
};
