<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            // Basic data
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            // Additional data
            $table->text('description')->nullable();
            $table->string('group')->nullable();
            // Timestamps
            $table->timestamps();
            // Indexes
            $table->index(['group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
