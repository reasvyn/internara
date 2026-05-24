<?php

declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['internship_id', 'name', 'description', 'start_date', 'end_date', 'order', 'color'])]
class InternshipPhase extends BaseModel
{
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
