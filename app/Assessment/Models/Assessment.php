<?php

declare(strict_types=1);

namespace App\Assessment\Models;

use App\Assessment\Entities\AssessmentResult;
use App\Assessment\Rubric\Models\Rubric;
use App\Core\Models\BaseModel;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'registration_id',
        'rubric_id',
        'evaluator_id',
        'assessment_type',
        'score',
        'scores_data',
        'feedback',
        'finalized_at',
    ]),
]
class Assessment extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'scores_data' => 'array',
        'score' => 'float',
        'finalized_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
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
