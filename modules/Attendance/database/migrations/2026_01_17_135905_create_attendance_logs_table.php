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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('registration_id')->index();
            $table->uuid('student_id')->index();
            $table->string('academic_year', 10)->nullable()->index();
            $table->date('date');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->timestamps();

            // Unique constraint to prevent multiple logs for the same student on the same day
            $table->unique(['student_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
