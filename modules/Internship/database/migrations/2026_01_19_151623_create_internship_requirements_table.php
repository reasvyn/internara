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
        Schema::create('internship_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Translation key or raw name
            $table->text('description')->nullable();
            $table->string('type'); // document, skill, condition
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('academic_year')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internship_requirements');
    }
};