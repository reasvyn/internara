<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Assessment for internship registration.
 *
 * S1 - Secure: Evaluator authorization required.
 * S2 - Sustain: Rich model with scoring logic.
 */
class Assessment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'registration_id',
        'academic_year',
        'evaluator_id',
        'type',
        'score',
        'content',
        'feedback',
        'finalized_at',
    ];

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
        return $this->belongsTo(InternshipRegistration::class, 'registration_id');
    }

    /**
     * Get the evaluator.
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Check if assessment is finalized.
     */
    public function isFinalized(): bool
    {
        return ! is_null($this->finalized_at);
    }

    /**
     * Calculate total score from content array.
     */
    public function calculateTotalScore(): float
    {
        if (! is_array($this->content)) {
            return (float) $this->score;
        }

        $total = 0.0;
        foreach ($this->content as $criterion) {
            $total += (float) ($criterion['score'] ?? 0);
        }

        return $total;
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): AssessmentFactory
    {
        return AssessmentFactory::new();
    }
}
