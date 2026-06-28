<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->index('registration_id');
            $table->foreignUuid('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('incident_date');
            $table->string('type')->index();
            $table->string('severity')->index();
            $table->text('description');
            $table->string('location')->nullable();
            $table->text('action_taken')->nullable();
            $table->string('status')->default('reported')->index();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['registration_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};
