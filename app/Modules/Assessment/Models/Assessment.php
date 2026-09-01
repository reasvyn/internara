<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Models;

use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Entities\AssessmentResult;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @property string $id
 * @property array $scores_data
 * @property float $score
 * @property \Carbon\Carbon $finalized_at
 * @property string $registration_id
 * @property string $rubric_id
 * @property string $evaluator_id
 * @property string $assessment_type
 * @property string $feedback
 * @property-read \App\Modules\Enrollment\Domain\Registration\Models\Registration|null $registration
 * @property-read \App\Modules\Assessment\Domain\Rubric\Models\Rubric|null $rubric
 * @property-read \App\Modules\User\Models\User|null $evaluator
 */

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
