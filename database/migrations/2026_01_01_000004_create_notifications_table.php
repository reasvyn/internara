<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // Basic data
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->constrained('users', 'id')
                ->onDelete('cascade');
            $table->string('type', 50);
            // Notification content
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_read')->default(false);
            // Timestamps
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            // Indexes
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
