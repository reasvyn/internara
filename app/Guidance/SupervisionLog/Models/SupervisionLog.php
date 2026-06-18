<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Registration\Models\Registration;
use App\Guidance\SupervisionLog\Entities\SupervisionLogState;
use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use App\User\Models\User;
use Database\Factories\SupervisionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'registration_id',
        'supervisor_id',
        'date',
        'topic',
        'notes',
        'status',
        'supervisor_feedback',
        'reviewed_by',
        'reviewed_at',
    ]),
]
class SupervisionLog extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'status' => SupervisionLogStatus::DRAFT->value,
        'type' => 'mentoring',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => SupervisionLogStatus::class,
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function asSupervisionLogState(): SupervisionLogState
    {
        return SupervisionLogState::fromModel($this);
    }

    protected static function newFactory(): SupervisionLogFactory
    {
        return SupervisionLogFactory::new();
    }
}
