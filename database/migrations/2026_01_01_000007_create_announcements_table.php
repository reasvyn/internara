<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            // Basic data
            $table->uuid('id')->primary();
            $table->foreignUuid('created_by')
                ->constrained('users', 'id')
                ->onDelete('cascade');
            // Announcement content
            $table->string('title');
            $table->text('message');
            $table->string('type', 20)->default('info');
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('link')->nullable();
            $table->json('target_roles')->nullable();
            // Timestamps
            $table->timestamps();
            // Indexes
            $table->index('created_by');
            $table->index('created_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
