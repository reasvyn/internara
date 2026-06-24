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
            $table->string('certificate_number')->unique();
            $table->string('qr_hash')->unique();
            $table->string('status')->default('issued')->index();
            $table->text('template_content')->nullable();
            $table->foreignUuid('issued_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('issued_at');
            $table->timestamps();

            $table->index('registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
