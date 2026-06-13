<?php

declare(strict_types=1);

namespace App\Evaluation\Models;

use App\Core\Models\BaseModel;
use Database\Factories\EvaluationQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[
    Fillable([
        'form_id',
        'section_id',
        'question_text',
        'question_type',
        'options',
        'weight',
        'order',
        'is_required',
    ]),
]
class EvaluationQuestion extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'options' => 'json',
        'weight' => 'integer',
        'order' => 'integer',
        'is_required' => 'boolean',
    ];

    protected static function newFactory(): EvaluationQuestionFactory
    {
        return EvaluationQuestionFactory::new();
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'form_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(EvaluationSection::class, 'section_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class, 'question_id');
    }
}
