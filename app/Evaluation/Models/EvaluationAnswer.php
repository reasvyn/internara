<?php

declare(strict_types=1);

namespace App\Evaluation\Models;

use App\Core\Models\BaseModel;
use Database\Factories\EvaluationAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'response_id',
        'question_id',
        'value',
        'score',
    ]),
]
class EvaluationAnswer extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'score' => 'float',
    ];

    protected static function newFactory(): EvaluationAnswerFactory
    {
        return EvaluationAnswerFactory::new();
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(EvaluationResponse::class, 'response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EvaluationQuestion::class, 'question_id');
    }
}
