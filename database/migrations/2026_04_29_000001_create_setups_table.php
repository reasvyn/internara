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
            $table->uuid('id')->primary();
            $table->string('version')->default('0.0.0');
            $table->boolean('is_installed')->default(false);
            $table->text('setup_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('completed_steps')->nullable();
            $table->uuid('admin_id')->nullable();
            $table->uuid('school_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->uuid('internship_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_installed');
            $table->index('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setups');
    }
};
