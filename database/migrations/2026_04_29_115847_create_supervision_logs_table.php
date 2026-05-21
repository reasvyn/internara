<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervision_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('registration_id')
                ->constrained('registrations')
                ->onDelete('cascade');
            $table->foreignUuid('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->date('date');
            $table->string('topic')->nullable();
            $table->text('notes');
            $table->string('status')->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('registration_id');
            $table->index(['supervisor_id', 'date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervision_logs');
    }
};
