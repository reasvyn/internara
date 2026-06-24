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
        Schema::create('placements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignUuid('internship_id')->constrained('internships')->onDelete('cascade');
            $table->string('name');
            $table->text('address')->nullable();
            $table->integer('quota')->default(1);
            $table->integer('filled_quota')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            // Prevent duplicate placements for the same company in the same internship
            $table->unique(['company_id', 'internship_id'], 'placement_company_internship_unique');
            $table->index(['company_id', 'internship_id']);
            $table->index(['internship_id', 'filled_quota']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placements');
    }
};
