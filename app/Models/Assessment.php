<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Assessment\AssessmentResult;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Assessment for internship registration.
 *
 * S1 - Secure: Evaluator authorization required.
 * S2 - Sustain: Rich model with scoring logic.
 */
#[Fillable(['registration_id', 'academic_year_id', 'evaluator_id', 'type', 'score', 'content', 'feedback', 'finalized_at'])]
class Assessment extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'content' => 'array',
        'score' => 'float',
        'finalized_at' => 'datetime',
    ];

    /**
     * Get the internship registration.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * Get the academic year.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the evaluator.
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function entity(): AssessmentResult
    {
        return AssessmentResult::fromModel($this);
    }

    public function isFinalized(): bool
    {
        return $this->entity()->isFinalized();
    }

    public function calculateTotalScore(): float
    {
        return $this->entity()->calculateTotalScore();
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): AssessmentFactory
    {
        return AssessmentFactory::new();
    }
}
