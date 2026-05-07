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

            // Identity identifiers (e.g. NISN, NIS, NIP)
            $table->string('national_identifier')->nullable();
            $table->string('registration_number')->nullable();

            $table->foreignUuid('school_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('department_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();

            $table->index(['national_identifier', 'registration_number']);
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
