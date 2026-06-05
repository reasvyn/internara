<?php

declare(strict_types=1);

namespace App\Assessment\Core\Models;

use App\Assessment\Core\Entities\AssessmentResult;
use App\Core\Models\BaseModel;
use App\User\Models\User;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['registration_id', 'academic_year_id', 'rubric_id', 'evaluator_id', 'type', 'score', 'content', 'feedback', 'finalized_at'])]
class Assessment extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'content' => 'array',
        'score' => 'float',
        'finalized_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function asAssessmentResult(): AssessmentResult
    {
        return AssessmentResult::fromModel($this);
    }

    protected static function newFactory(): AssessmentFactory
    {
        return AssessmentFactory::new();
    }
}
