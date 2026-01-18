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
        Schema::table('internship_registrations', function (Blueprint $table) {
            $table->string('academic_year', 10)->nullable()->after('student_id')->index();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('academic_year', 10)->nullable()->after('student_id')->index();
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->string('academic_year', 10)->nullable()->after('student_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internship_registrations', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });
    }
};
