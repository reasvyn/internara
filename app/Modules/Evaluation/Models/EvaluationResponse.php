<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Database\Factories\EvaluationResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @property string $id
 * @property float $overall_score
 * @property \Carbon\Carbon $submitted_at
 * @property string $form_id
 * @property string $evaluator_id
 * @property string $target_type
 * @property string $target_id
 * @property string $registration_id
 * @property string $notes
 * @property-read \App\Modules\Evaluation\Models\EvaluationForm|null $form
 * @property-read \App\Modules\User\Models\User|null $evaluator
 * @property-read \App\Modules\Enrollment\Domain\Registration\Models\Registration|null $registration
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Evaluation\Models\EvaluationAnswer> $answers
 */

#[
    Fillable([
        'form_id',
        'evaluator_id',
        'target_type',
        'target_id',
        'registration_id',
        'overall_score',
        'notes',
        'submitted_at',
    ]),
]
class EvaluationResponse extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'overall_score' => 'float',
        'submitted_at' => 'datetime',
    ];

    protected static function newFactory(): EvaluationResponseFactory
    {
        return EvaluationResponseFactory::new();
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'form_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class, 'response_id');
    }
}
