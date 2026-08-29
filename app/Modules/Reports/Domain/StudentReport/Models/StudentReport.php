<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Reports\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\User\Models\User;
use Database\Factories\StudentReportFactory;
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
class StudentReport extends BaseModel
{
    use HasFactory;

    protected $table = 'reports';

    protected $attributes = [
        'status' => StudentReportStatus::DRAFT->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentReportStatus::class,
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

    protected static function newFactory(): StudentReportFactory
    {
        return StudentReportFactory::new();
    }
}
