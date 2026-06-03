<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Aggregates\Rubric\Models;

use App\Domain\Core\Models\BaseModel;
use Database\Factories\IndicatorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['competency_id', 'name', 'description', 'max_score', 'weight', 'order'])]
class Indicator extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'max_score' => 'float',
            'weight' => 'integer',
            'order' => 'integer',
        ];
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    protected static function newFactory(): IndicatorFactory
    {
        return IndicatorFactory::new();
    }
}
