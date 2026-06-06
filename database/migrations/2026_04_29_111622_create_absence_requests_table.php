<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Absence requests have been merged into the `attendances` table.
        // Use `attendances` with absence_type, absence_reason, absence_status columns instead.
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_requests');
    }
};
