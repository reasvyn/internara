<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_change_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_id')->constrained('internship_registrations')->cascadeOnDelete();
            $table->foreignUuid('from_placement_id')->constrained('internship_placements')->cascadeOnDelete();
            $table->foreignUuid('to_placement_id')->nullable()->constrained('internship_placements')->nullOnDelete();
            $table->text('reason');
            $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_change_requests');
    }
};
