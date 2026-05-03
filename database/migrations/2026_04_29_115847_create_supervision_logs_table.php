<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supervision_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('registration_id')
                ->constrained('internship_registrations')
                ->onDelete('cascade');
            $table->foreignUuid('supervisor_id')->constrained('users'); // The Teacher or Mentor
            $table->string('type'); // guidance, mentoring
            $table->date('date');
            $table->string('topic')->nullable();
            $table->text('notes');
            $table->string('status')->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();

            $table->index('registration_id');
            $table->index(['supervisor_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervision_logs');
    }
};
