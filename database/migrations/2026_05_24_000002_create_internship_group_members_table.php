<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_group_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('internship_group_id')
                ->nullable()
                ->constrained('internship_groups')
                ->cascadeOnDelete();
            $table->index('internship_group_id');
            $table->foreignUuid('registration_id')->nullable()->constrained()->nullOnDelete();
            $table->index('registration_id');
            $table->foreignUuid('mentor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('mentor_id');
            $table->string('role');
            $table->dateTime('joined_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_group_members');
    }
};
