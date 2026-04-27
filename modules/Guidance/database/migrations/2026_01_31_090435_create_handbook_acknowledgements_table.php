<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('handbook_acknowledgements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // External Relation (User/Student) - No FK Constraint
            $table->uuid('student_id')->index();

            // Internal Relation (Guidance) - FK Constraint Allowed
            $table->foreignUuid('handbook_id')->constrained()->cascadeOnDelete();

            $table->timestamp('acknowledged_at');
            $table->timestamps();

            // Prevent duplicate acknowledgements
            $table->unique(['student_id', 'handbook_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handbook_acknowledgements');
    }
};
