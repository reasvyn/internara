<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Mentee\MenteeState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'is_active', 'internal_notes'])]
class Mentee extends BaseModel
{
    use HasFactory;

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'mentee_id');
    }

    public function asMenteeState(): MenteeState
    {
        return MenteeState::fromModel($this);
    }
}
