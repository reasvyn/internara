<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an evaluation of a mentor's performance.
 *
 * S1 - Secure: Evaluations are immutable once finalized (business logic).
 * S2 - Sustain: Stores criteria-based scores for granular feedback.
 */
#[Fillable(['evaluator_id', 'mentor_id', 'registration_id', 'overall_score', 'feedback', 'criteria_scores'])]
class Evaluation extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'overall_score' => 'float',
        'criteria_scores' => 'array',
    ];

    /**
     * Get the evaluator (usually a student or administrator).
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Get the mentor being evaluated.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Get the internship registration associated with this evaluation.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
