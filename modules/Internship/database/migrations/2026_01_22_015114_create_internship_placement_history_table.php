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
        Schema::create('internship_placement_history', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Reference to the registration record
            $table->uuid('registration_id')->index();

            // Reference to the placement (Industry Partner)
            $table->uuid('placement_id')->index();

            // The action that triggered this log (assigned, changed, completed, etc)
            $table->string('action');

            // Human readable reason for the change
            $table->text('reason')->nullable();

            // Optional structured data (e.g., who made the change, old vs new values)
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internship_placement_history');
    }
};
