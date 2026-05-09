<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mentee_id')->nullable()->constrained('mentees')->onDelete('cascade');
            $table->foreignUuid('internship_id')->constrained('internships')->onDelete('cascade');
            $table
                ->foreignUuid('placement_id')
                ->nullable()
                ->constrained('internship_placements')
                ->onDelete('set null');

            $table->string('academic_year')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->string('proposed_company_name')->nullable();
            $table->text('proposed_company_address')->nullable();

            $table->string('status')->default('pending')->index();

            $table->timestamps();

            $table->index(['mentee_id', 'internship_id']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('registration_mentor', function (Blueprint $table) {
            $table->foreignUuid('registration_id')->constrained('internship_registrations')->onDelete('cascade');
            $table->foreignUuid('mentor_id')->constrained('mentors')->onDelete('cascade');
            $table->string('role')->nullable();
            $table->timestamps();

            $table->primary(['registration_id', 'mentor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_mentor');
        Schema::dropIfExists('internship_registrations');
    }
};
