<?php

declare(strict_types=1);

use App\Domain\Assessment\Aggregates\Rubric\Models\Competency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Competency::class)->constrained()->cascadeOnDelete();
            $table->index('competency_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('max_score', 5, 1)->default(100);
            $table->unsignedTinyInteger('weight')->default(0);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
