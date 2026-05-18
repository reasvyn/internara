<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('internship_registrations')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('location')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->float('presentation_score')->nullable();
            $table->float('report_score')->nullable();
            $table->float('final_score')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentations');
    }
};
