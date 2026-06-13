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
        Schema::table('reports', function (Blueprint $table) {
            $table->string('title')->nullable()->after('registration_id');
            $table->json('content')->nullable()->after('title');
            $table->json('chapter_structure')->nullable()->after('content');
            $table->string('student_name')->nullable()->after('grade_letter');
            $table->string('student_email')->nullable()->after('student_name');
            $table->string('student_number')->nullable()->after('student_email');
            $table->string('internship_name')->nullable()->after('student_number');
            $table->string('company_name')->nullable()->after('internship_name');
            $table->string('department_name')->nullable()->after('company_name');
            $table->string('supervisor_name')->nullable()->after('department_name');
            $table->string('teacher_name')->nullable()->after('supervisor_name');
            $table->text('supervisor_notes')->nullable()->after('teacher_name');
            $table->timestamp('submitted_at')->nullable()->after('supervisor_notes');
            $table->float('score')->nullable()->after('submitted_at');
            $table->text('feedback')->nullable()->after('score');
            $table->foreignUuid('graded_by')->nullable()->constrained('users')->nullOnDelete()->after('feedback');
            $table->timestamp('graded_at')->nullable()->after('graded_by');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn([
                'title', 'content', 'chapter_structure', 'student_name', 'student_email',
                'student_number', 'internship_name', 'company_name', 'department_name',
                'supervisor_name', 'teacher_name', 'supervisor_notes', 'submitted_at',
                'score', 'feedback', 'graded_by', 'graded_at',
            ]);
        });
    }
};
