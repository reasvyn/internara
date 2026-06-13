<?php

declare(strict_types=1);

namespace App\Evaluation\Models;

use App\Core\Models\BaseModel;
use App\User\Models\User;
use Database\Factories\EvaluationFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[
    Fillable([
        'name',
        'description',
        'target_type',
        'is_active',
        'created_by',
    ]),
]
class EvaluationForm extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): EvaluationFormFactory
    {
        return EvaluationFormFactory::new();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(EvaluationSection::class, 'form_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EvaluationQuestion::class, 'form_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class, 'form_id');
    }
}
