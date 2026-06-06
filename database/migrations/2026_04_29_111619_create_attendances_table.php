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
            $table->foreignUuid('registration_id')->constrained('registrations')->onDelete('cascade');

            $table->date('date')->index();
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();

            $table->string('clock_in_ip')->nullable();
            $table->string('clock_out_ip')->nullable();

            $table->decimal('clock_in_latitude', 10, 8)->nullable();
            $table->decimal('clock_in_longitude', 11, 8)->nullable();
            $table->decimal('clock_out_latitude', 10, 8)->nullable();
            $table->decimal('clock_out_longitude', 11, 8)->nullable();

            $table->string('status')->default('present')->index();
            $table->string('absence_type')->nullable()->comment('sick, permission, other');
            $table->text('absence_reason')->nullable();
            $table->string('absence_attachment')->nullable();
            $table->string('absence_status')->nullable()->comment('pending, approved, rejected');
            $table->foreignUuid('absence_processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('absence_processed_at')->nullable();
            $table->text('absence_admin_notes')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
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
