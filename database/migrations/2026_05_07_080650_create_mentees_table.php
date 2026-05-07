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
        Schema::create('mentees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->onDelete('cascade')->index();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->onDelete('set null')->index();

            // Student specific identifiers
            $table->string('student_id_number')->nullable()->unique()->index()->comment('NIS/Local Student ID');
            $table->string('national_id_number')->nullable()->unique()->index()->comment('NISN/National Student ID');

            $table->string('class_name')->nullable()->index()->comment('e.g. XII-RPL-1');
            $table->integer('entry_year')->nullable()->index();

            $table->boolean('is_active')->default(true)->index();
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            $table->index(['department_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentees');
    }
};
