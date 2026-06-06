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
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->onDelete('cascade');

            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('gender')->nullable();
            $table->string('blood_type')->nullable();

            $table->string('pob')->nullable()->comment('Place of Birth');
            $table->date('dob')->nullable()->comment('Date of Birth');

            $table->json('emergency_contact')->nullable();

            $table->string('student_id_number', 50)->nullable()->unique();
            $table->string('employee_id_number', 30)->nullable()->unique();
            $table->string('mentor_type')->nullable(); // school_teacher | industry_supervisor | null
            $table->text('internal_notes')->nullable();

            $table->foreignUuid('department_id')->nullable()->constrained()->onDelete('set null');
            $table->index('department_id');

            $table->foreignUuid('company_id')->nullable()->constrained()->onDelete('set null');
            $table->index('company_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
