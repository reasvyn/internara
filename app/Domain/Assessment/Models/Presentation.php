<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Models;

use App\Domain\Assessment\Enums\PresentationStatus;
use App\Domain\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['registration_id', 'scheduled_at', 'location', 'status', 'presentation_score', 'report_score', 'final_score', 'notes', 'completed_at'])]
class Presentation extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'status' => 'scheduled',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => PresentationStatus::class,
            'presentation_score' => 'float',
            'report_score' => 'float',
            'final_score' => 'float',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function examiners(): HasMany
    {
        return $this->hasMany(PresentationExaminer::class, 'presentation_id');
    }
}
