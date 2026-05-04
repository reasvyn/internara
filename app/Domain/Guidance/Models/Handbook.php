<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents an official guidance document or handbook.
 *
 * S2 - Sustain: Single source of truth for guidance materials.
 */
#[Fillable(['title', 'slug', 'content', 'version', 'is_active', 'published_at', 'created_by'])]
class Handbook extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(HandbookAcknowledgement::class);
    }

    public function isPublished(): bool
    {
        return $this->is_active && $this->published_at !== null;
    }
}
