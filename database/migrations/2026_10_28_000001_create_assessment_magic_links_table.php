<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_magic_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')
                ->constrained('registrations')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('token', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('submitted_at')->nullable();
            $table->string('evaluator_name')->nullable();
            $table->string('evaluator_phone')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
            $table->timestamps();

            $table->index('registration_id');
            $table->index('token');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_magic_links');
    }
};
