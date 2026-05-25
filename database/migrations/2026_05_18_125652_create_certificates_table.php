<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->index('registration_id');
            $table->string('certificate_number')->unique();
            $table->foreignUuid('template_id')->constrained('certificate_templates')->nullOnDelete();
            $table->index('template_id');
            $table->string('status')->default('issued')->index();
            $table->foreignUuid('issued_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('issued_at');
            $table->json('metadata')->nullable();
            $table->foreignUuid('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
