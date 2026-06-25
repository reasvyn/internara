<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            // Basic data
            $table->uuid('id')->primary();
            $table->string('type', 20);
            $table->string('file_path', 512)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status', 20)->default('pending');
            $table->json('metadata')->nullable();
            $table->text('error_output')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            // Indexes
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
