<?php

declare(strict_types=1);

namespace App\Domain\Schedule\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a scheduled event in the internship program.
 *
 * S2 - Sustain: Centralized schedule management for all internship activities.
 */
#[Fillable(['title', 'description', 'start_at', 'end_at', 'type', 'location', 'internship_id', 'created_by'])]
class Schedule extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'internship_id');
    }

    public function isOngoing(): bool
    {
        return $this->start_at <= now() && ($this->end_at === null || $this->end_at >= now());
    }

    public function isUpcoming(): bool
    {
        return $this->start_at > now();
    }
}
