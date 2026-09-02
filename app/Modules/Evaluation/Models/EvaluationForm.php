<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\User\Models\User;
use Database\Factories\EvaluationFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property bool $is_active
 * @property string $name
 * @property string $description
 * @property string $target_type
 * @property string $created_by
 * @property-read User|null $createdBy
 * @property-read Collection<int,EvaluationSection> $sections
 * @property-read Collection<int,EvaluationQuestion> $questions
 * @property-read Collection<int,EvaluationResponse> $responses
 */
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
