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
        Schema::table('supervision_logs', function (Blueprint $table) {
            $table->text('supervisor_feedback')->nullable()->after('notes');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('supervisor_feedback');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('supervision_logs', function (Blueprint $table) {
            $table->dropColumn(['supervisor_feedback', 'reviewed_by', 'reviewed_at']);
        });
    }
};
