<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->boolean('requires_presentation')->default(false)->after('status');
            $table->integer('presentation_weight')->default(50)->after('requires_presentation');
            $table->integer('report_weight')->default(50)->after('presentation_weight');
        });
    }

    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn(['requires_presentation', 'presentation_weight', 'report_weight']);
        });
    }
};
