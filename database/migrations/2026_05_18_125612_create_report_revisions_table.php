<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained()->cascadeOnDelete();
            $table->index('report_id');
            $table->integer('round');
            $table->text('feedback');
            $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('requested_at');
            $table->dateTime('resubmitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_revisions');
    }
};
