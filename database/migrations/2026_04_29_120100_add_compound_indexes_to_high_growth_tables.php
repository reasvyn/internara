<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add compound indexes on high-growth tables for query performance.
 * Individual FK columns are already indexed via foreignUuid().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_registrations', function (Blueprint $table) {
            $table->index(['student_id', 'internship_id']);
            $table->index(['teacher_id', 'mentor_id']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index('registration_id');
            $table->index('status');
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->index('registration_id');
            $table->index(['user_id', 'status']);
        });

        Schema::table('requirement_submissions', function (Blueprint $table) {
            $table->index('registration_id');
        });

        Schema::table('supervision_logs', function (Blueprint $table) {
            $table->index('registration_id');
            $table->index(['supervisor_id', 'date']);
            $table->index('status');
        });

        Schema::table('monitoring_visits', function (Blueprint $table) {
            $table->index('registration_id');
            $table->index(['teacher_id', 'date']);
        });

        Schema::table('absence_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index('registration_id');
        });
    }

    public function down(): void
    {
        Schema::table('internship_registrations', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'internship_id']);
            $table->dropIndex(['teacher_id', 'mentor_id']);
            $table->dropIndex(['start_date', 'end_date']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('requirement_submissions', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
        });

        Schema::table('supervision_logs', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
            $table->dropIndex(['supervisor_id', 'date']);
            $table->dropIndex(['status']);
        });

        Schema::table('monitoring_visits', function (Blueprint $table) {
            $table->dropIndex(['registration_id']);
            $table->dropIndex(['teacher_id', 'date']);
        });

        Schema::table('absence_requests', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['registration_id']);
        });
    }
};
