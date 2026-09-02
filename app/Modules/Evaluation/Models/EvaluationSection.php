<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\Models;

use App\Modules\Core\Models\BaseModel;
use Database\Factories\EvaluationSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $form_id
 * @property string $title
 * @property string $description
 * @property string $order
 * @property-read EvaluationForm|null $form
 * @property-read Collection<int,EvaluationQuestion> $questions
 */
#[
    Fillable([
        'form_id',
        'title',
        'description',
        'order',
    ]),
]
class EvaluationSection extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): EvaluationSectionFactory
    {
        return EvaluationSectionFactory::new();
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'form_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EvaluationQuestion::class, 'section_id');
    }
}
