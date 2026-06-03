<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\InternshipPhase\Models;

use App\Domain\Core\Models\BaseModel;
use Database\Factories\InternshipPhaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['internship_id', 'name', 'description', 'start_date', 'end_date', 'order', 'color'])]
class InternshipPhase extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): InternshipPhaseFactory
    {
        return InternshipPhaseFactory::new();
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'order' => 'integer',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }
}
