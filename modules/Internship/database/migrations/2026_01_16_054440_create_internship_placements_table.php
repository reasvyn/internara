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
        Schema::create('internship_placements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->text('company_address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();
            $table->integer('capacity_quota')->default(1);
            $table->foreignUuid('internship_id')->constrained('internships')->cascadeOnDelete();
            $table->uuid('mentor_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internship_placements');
    }
};
