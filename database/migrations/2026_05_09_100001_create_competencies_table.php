<?php

declare(strict_types=1);

use App\Assessment\Rubric\Models\Rubric;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Rubric::class)->constrained()->cascadeOnDelete();
            $table->index('rubric_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('weight')->default(0);
            $table->string('evaluator_role');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencies');
    }
};
