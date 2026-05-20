<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Models;

use App\Domain\Assessment\Enums\EvaluatorRole;
use App\Domain\Core\Models\BaseModel;
use Database\Factories\CompetencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['rubric_id', 'name', 'description', 'weight', 'evaluator_role', 'order'])]
class Competency extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'order' => 'integer',
            'evaluator_role' => EvaluatorRole::class,
        ];
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class)->orderBy('order');
    }

    protected static function newFactory(): CompetencyFactory
    {
        return CompetencyFactory::new();
    }
}
