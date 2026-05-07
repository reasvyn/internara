<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\SupervisionLog\SupervisionStatus;
use App\Enums\Mentor\SupervisionLogStatus;
use App\Enums\Mentor\SupervisionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'supervisor_id', 'type', 'date', 'topic', 'notes', 'status', 'is_verified', 'verified_at', 'attachment_path'])]
class SupervisionLog extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'date' => 'date',
        'type' => SupervisionType::class,
        'status' => SupervisionLogStatus::class,
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function entity(): SupervisionStatus
    {
        return SupervisionStatus::fromModel($this);
    }

    public function isCompleted(): bool
    {
        return $this->entity()->isCompleted();
    }

    public function isActive(): bool
    {
        return $this->entity()->isActive();
    }
}
