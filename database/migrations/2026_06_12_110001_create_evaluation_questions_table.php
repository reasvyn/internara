<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('form_id')->constrained('evaluation_forms')->cascadeOnDelete();
            $table->uuid('section_id')->nullable()->index();
            $table->text('question_text');
            $table->string('question_type', 30)->default('rating_1_5')->comment('rating_1_5, rating_1_10, yes_no, multiple_choice, text, agreement');
            $table->json('options')->nullable()->comment('Choices for multiple_choice');
            $table->unsignedInteger('weight')->default(1);
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->index(['form_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_questions');
    }
};
