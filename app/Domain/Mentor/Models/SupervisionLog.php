<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Models;

use App\Domain\Core\Concerns\HasUuid;
use App\Enums\SupervisionLogStatus;
use App\Enums\SupervisionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'supervisor_id', 'type', 'date', 'topic', 'notes', 'status', 'is_verified', 'verified_at', 'attachment_path'])]
class SupervisionLog extends Model
{
    use HasFactory, HasUuid;

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

    public function isCompleted(): bool
    {
        return $this->status?->isTerminal() ?? false;
    }

    public function isActive(): bool
    {
        return $this->status?->isActive() ?? false;
    }
}
