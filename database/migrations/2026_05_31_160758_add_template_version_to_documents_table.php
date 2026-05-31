<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedInteger('template_version')->default(1)->after('is_active');
            $table->foreignUuid('template_id')->nullable()->constrained('documents')->nullOnDelete()->after('template_version');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropColumn('template_version');
        });
    }
};
