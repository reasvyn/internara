<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('internship_placements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('company_id')
                ->index()
                ->constrained('internship_companies')
                ->cascadeOnDelete();
            $table
                ->foreignUuid('internship_id')
                ->index()
                ->constrained('internships')
                ->cascadeOnDelete();
            $table->integer('capacity_quota')->default(1);
            $table->uuid('mentor_id')->nullable()->index();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
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
