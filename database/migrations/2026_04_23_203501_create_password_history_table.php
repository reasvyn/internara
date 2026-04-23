<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('password_hash'); // Hash of previous password
            $table->dateTime('changed_at');
            $table->timestamps();

            // Index for quick history lookup
            $table->index(['user_id', 'changed_at']);

            // Cleanup index (old entries)
            $table->index('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_history');
    }
};
