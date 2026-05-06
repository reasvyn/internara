<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setups', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_installed')->default(false);
            $table->string('setup_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('completed_steps')->nullable();
            $table->foreignUuid('school_id')->nullable();
            $table->foreignUuid('department_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setups');
    }
};
