<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('handbooks', function (Blueprint $table) {
            $table->index('created_by');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index('graded_by');
        });

        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->index('created_by');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->index('created_by');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->index('name');
            $table->index('industry_sector');
        });
    }

    public function down(): void
    {
        Schema::table('handbooks', fn (Blueprint $t) => $t->dropIndex(['created_by']));
        Schema::table('reports', fn (Blueprint $t) => $t->dropIndex(['graded_by']));
        Schema::table('certificate_templates', fn (Blueprint $t) => $t->dropIndex(['created_by']));
        Schema::table('announcements', fn (Blueprint $t) => $t->dropIndex(['created_by']));
        Schema::table('companies', fn (Blueprint $t) => $t->dropIndex(['name', 'industry_sector']));
    }
};
