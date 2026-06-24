<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('internship_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('internship_id')->constrained()->cascadeOnDelete();
            $table->index('internship_id');
            $table->foreignUuid('placement_id')->nullable()->constrained()->nullOnDelete();
            $table->index('placement_id');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('internship_group_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('internship_group_id')
                ->nullable()
                ->constrained('internship_groups')
                ->cascadeOnDelete();
            $table->foreignUuid('registration_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('joined_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_groups');
        Schema::dropIfExists('internship_group_members');
    }
};
