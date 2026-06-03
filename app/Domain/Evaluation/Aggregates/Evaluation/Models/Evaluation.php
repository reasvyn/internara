<?php

declare(strict_types=1);

namespace App\Domain\Evaluation\Aggregates\Evaluation\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\Evaluation\Aggregates\Evaluation\Enums\EvaluationCategory;
use App\Domain\User\Models\User;
use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'evaluator_id', 'evaluation_type', 'mentor_id',
    'registration_id', 'target_type', 'target_id',
    'overall_score', 'feedback', 'criteria_scores',
])]
class Evaluation extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'evaluation_type' => EvaluationCategory::class,
        'overall_score' => 'float',
        'criteria_scores' => 'array',
    ];

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    protected static function newFactory(): EvaluationFactory
    {
        return EvaluationFactory::new();
    }

    public function scopeOfType(Builder $query, EvaluationCategory $type): Builder
    {
        return $query->where('evaluation_type', $type->value);
    }

    public function scopeByEvaluator(Builder $query, User $user): Builder
    {
        return $query->where('evaluator_id', $user->id);
    }

    public function scopeHighScore(Builder $query, float $threshold = 80): Builder
    {
        return $query->where('overall_score', '>=', $threshold);
    }
}
