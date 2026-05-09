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
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('registration_id')->constrained('internship_registrations')->onDelete('cascade');

            $table->date('date')->index();
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();

            $table->string('clock_in_ip')->nullable();
            $table->string('clock_out_ip')->nullable();

            $table->decimal('clock_in_latitude', 10, 8)->nullable();
            $table->decimal('clock_in_longitude', 11, 8)->nullable();
            $table->decimal('clock_out_latitude', 10, 8)->nullable();
            $table->decimal('clock_out_longitude', 11, 8)->nullable();

            $table->string('status')->default('present')->index(); // present, late, early_out, absent, etc.
            $table->boolean('is_verified')->default(false)->index();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index('registration_id');
            $table->index(['registration_id', 'date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
