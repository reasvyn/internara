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
        Schema::create('logbooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table
                ->foreignUuid('registration_id')
                ->constrained('registrations')
                ->onDelete('cascade');

            $table->date('date');
            $table->text('content');
            $table->text('learning_outcomes')->nullable();

            $table->string('status')->default('draft'); // draft, submitted, verified, revision_required
            $table->boolean('is_verified')->default(false);
            $table
                ->foreignUuid('verified_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('mentor_feedback')->nullable();

            $table->text('supervisor_note')->nullable()->after('mentor_feedback');
            $table->timestamp('supervisor_reviewed_at')->nullable()->after('supervisor_note');
            $table
                ->foreignUuid('supervisor_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->after('supervisor_reviewed_at');

            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['registration_id', 'is_verified']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbooks');
    }
};
