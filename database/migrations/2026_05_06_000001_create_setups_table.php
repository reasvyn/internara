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
            $table->boolean('is_installed')->default(false);
            $table->string('setup_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('completed_steps')->nullable();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->index('school_id');
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->index('department_id');
            $table->text('recovery_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setups');
    }
};
