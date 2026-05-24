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

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('emergency_contact_address')->nullable();

            $table->text('bio')->nullable();

            // Identity identifiers
            $table->string('national_id_number', 50)->nullable();
            $table->string('student_id_number', 50)->nullable();

            $table->foreignUuid('school_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('department_id')->nullable()->constrained()->onDelete('set null');

            $table->string('employment_status')->nullable();
            $table->string('nip', 18)->nullable();
            $table->string('nuptk', 16)->nullable();
            $table->string('competence_field')->nullable();
            $table->string('position')->nullable();

            $table->timestamps();

            $table->index(['national_id_number', 'student_id_number']);
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
