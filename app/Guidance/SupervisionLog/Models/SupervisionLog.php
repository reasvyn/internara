<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Models\Registration;
use App\Guidance\SupervisionLog\Entities\SupervisionStatus;
use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use App\Guidance\SupervisionLog\Enums\SupervisionType;
use App\User\Models\User;
use Database\Factories\SupervisionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'supervisor_id', 'type', 'date', 'topic', 'notes', 'status', 'is_verified', 'verified_at', 'verified_by'])]
class SupervisionLog extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'status' => SupervisionLogStatus::PENDING->value,
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => SupervisionType::class,
            'status' => SupervisionLogStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function asSupervisionStatus(): SupervisionStatus
    {
        return SupervisionStatus::fromModel($this);
    }

    protected static function newFactory(): SupervisionLogFactory
    {
        return SupervisionLogFactory::new();
    }
}
