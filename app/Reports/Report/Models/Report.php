<?php

declare(strict_types=1);

namespace App\Reports\Report\Models;

use App\Core\Models\BaseModel;
use App\Reports\Report\Enums\ReportStatus;
use App\User\Models\User;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'registration_id',
    'supervisor_score',
    'teacher_score',
    'exam_score',
    'final_score',
    'grade_letter',
    'industry_feedback',
    'status',
    'finalized_by',
    'finalized_at',
    'student_name',
    'student_number',
    'student_email',
    'internship_name',
    'company_name',
    'department_name',
    'supervisor_name',
    'teacher_name',
    'archived_data',
])]
class Report extends BaseModel
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Report $report) {
            $report->captureSnapshot();
        });
    }

    public function captureSnapshot(): void
    {
        if ($this->registration_id && $this->registration) {
            $registration = $this->registration;
            $student = $registration->student;
            $profile = $student?->profile;
            $internship = $registration->internship;
            $placement = $registration->placement;
            $company = $placement?->company;
            $department = $profile?->department;

            if ($student) {
                $this->student_name = $student->name;
                $this->student_email = $student->email;
            }
            if ($profile) {
                $this->student_number = $profile->student_id_number;
            }

            if ($internship) {
                $this->internship_name = $internship->name;
            }
            if ($company) {
                $this->company_name = $company->name;
            } elseif ($registration->proposed_company_details) {
                $this->company_name = $registration->proposed_company_details['company_name'] ?? null;
            }
            if ($department) {
                $this->department_name = $department->name;
            }

            $supervisor = $registration->mentors->first(
                fn ($m) => $m->pivot?->role === 'supervisor' || $m->pivot?->role === 'mentor'
            );
            if ($supervisor) {
                $this->supervisor_name = $supervisor->name;
            }

            $teacher = $registration->mentors->first(
                fn ($m) => $m->pivot?->role === 'teacher' || $m->pivot?->role === 'advisor'
            );
            if ($teacher) {
                $this->teacher_name = $teacher->name;
            }

            $this->archived_data = array_merge($this->archived_data ?? [], [
                'captured_at' => now()->toIso8601String(),
                'student_phone' => $profile?->phone,
                'company_address' => $company?->address ?? ($registration->proposed_company_details['address'] ?? null),
                'academic_year' => $internship?->academicYear?->name,
            ]);
        }
    }

    protected $attributes = [
        'status' => ReportStatus::DRAFT->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'supervisor_score' => 'float',
            'teacher_score' => 'float',
            'exam_score' => 'float',
            'final_score' => 'float',
            'finalized_at' => 'datetime',
            'archived_data' => 'json',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    protected static function newFactory(): ReportFactory
    {
        return ReportFactory::new();
    }
}
