<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('form_id')->constrained('evaluation_forms')->cascadeOnDelete();
            $table->foreignUuid('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type', 30)->comment('mentor, program, company');
            $table->uuid('target_id')->comment('FK polymorphic target');
            $table->foreignUuid('registration_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->float('overall_score')->nullable()->comment('Auto-calculated from weighted answers');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index(['form_id', 'evaluator_id']);
            $table->index(['target_type', 'target_id']);
            $table->index('submitted_at');
            $table->index(['registration_id', 'form_id']);
            $table->index('registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_responses');
    }
};
