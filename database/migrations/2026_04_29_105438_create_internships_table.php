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
        Schema::create('internships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_year_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_start_date')->nullable();
            $table->date('registration_end_date')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('requires_presentation')->default(false)->after('status');
            $table->integer('presentation_weight')->default(50)->after('requires_presentation');
            $table->integer('report_weight')->default(50)->after('presentation_weight');
            $table->timestamps();

            $table->index(['academic_year_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internships');
    }
};
