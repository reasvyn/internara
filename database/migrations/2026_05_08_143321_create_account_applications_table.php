<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();

            $table->string('national_id_number', 50)->nullable();
            $table->string('student_id_number', 50)->nullable();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->onDelete('set null');
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->string('class_name')->nullable();
            $table->integer('entry_year')->nullable();

            $table->foreignUuid('internship_id')->constrained('internships')->onDelete('cascade');
            $table->foreignUuid('placement_id')->nullable()->constrained('placements')->onDelete('set null');
            $table->string('academic_year')->nullable();
            $table->string('proposed_company_name')->nullable();
            $table->text('proposed_company_address')->nullable();

            $table->string('status')->default('pending')->index();
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_applications');
    }
};
