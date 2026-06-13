<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('response_id')->constrained('evaluation_responses')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained('evaluation_questions')->cascadeOnDelete();
            $table->text('value')->nullable()->comment('Raw answer text/choice');
            $table->float('score')->nullable()->comment('Numeric score derived from value');
            $table->timestamps();

            $table->unique(['response_id', 'question_id'], 'ev_answer_unique');
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_answers');
    }
};
