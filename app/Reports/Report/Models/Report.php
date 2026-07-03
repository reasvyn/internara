<?php

declare(strict_types=1);

namespace App\Reports\Report\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Registration\Models\Registration;
use App\Reports\Report\Enums\ReportStatus;
use App\User\Models\User;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
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
        'archived_data',
    ]),
]
class Report extends BaseModel
{
    use HasFactory;

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

    public function captureSnapshot(): void
    {
        if (! $this->registration_id || ! $this->registration) {
            return;
        }

        $registration = $this->registration;
        $student = $registration->student;
        $profile = $student?->profile;
        $internship = $registration->internship;
        $placement = $registration->placement;
        $company = $placement?->company;
        $department = $profile?->department;

        $mentors = $registration->mentors;

        $this->archived_data = array_merge($this->archived_data ?? [], array_filter([
            'captured_at' => now()->toIso8601String(),
            'student_name' => $student?->name,
            'student_email' => $student?->email,
            'student_number' => $profile?->id_number,
            'student_phone' => $profile?->phone,
            'internship_name' => $internship?->name,
            'company_name' => $company?->name
                ?? ($registration->proposed_company_details['company_name'] ?? null),
            'company_address' => $company?->address
                ?? ($registration->proposed_company_details['address'] ?? null),
            'department_name' => $department?->name,
            'supervisor_name' => $mentors->first(fn ($m) => $m->hasRole('supervisor'))?->name,
            'teacher_name' => $mentors->first(fn ($m) => $m->hasRole('teacher'))?->name,
            'academic_year' => $internship?->academicYear?->name,
        ], fn ($v) => $v !== null));
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
