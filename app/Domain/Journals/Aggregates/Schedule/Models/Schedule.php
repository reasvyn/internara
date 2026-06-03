<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\Schedule\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Journals\Aggregates\Schedule\Entities\ScheduleStatus;
use App\Domain\Program\Aggregates\Internship\Models\Internship;
use App\Domain\User\Models\User;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a scheduled event in the internship program.
 *
 * S2 - Sustain: Centralized schedule management for all internship activities.
 */
#[Fillable(['title', 'description', 'start_at', 'end_at', 'type', 'location', 'internship_id', 'created_by'])]
class Schedule extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): ScheduleFactory
    {
        return ScheduleFactory::new();
    }

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
        return $this->belongsTo(Internship::class, 'internship_id');
    }

    public function asScheduleStatus(): ScheduleStatus
    {
        return ScheduleStatus::fromModel($this);
    }
}
