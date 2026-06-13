<?php

declare(strict_types=1);

namespace App\Evaluation\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Database\Factories\EvaluationResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
