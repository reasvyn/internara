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
        Schema::create('journal_competency', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('journal_entry_id')->index();
            $table->uuid('competency_id')->index();
            $table->timestamps();

            // Note: No physical foreign keys across modules per Internara conventions.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_competency');
    }
};
