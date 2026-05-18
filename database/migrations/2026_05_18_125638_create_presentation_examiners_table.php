<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentation_examiners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('presentation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('examiner_id')->constrained('users')->cascadeOnDelete();
            $table->float('score')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['presentation_id', 'examiner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentation_examiners');
    }
};
