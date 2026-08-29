<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\SupervisionLog\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\SupervisionLog\Entities\SupervisionLogState;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\User\Models\User;
use Database\Factories\SupervisionLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'registration_id',
        'supervisor_id',
        'type',
        'date',
        'topic',
        'notes',
        'status',
        'supervisor_feedback',
        'reviewed_by',
        'reviewed_at',
        'is_verified',
        'verified_by',
        'verified_at',
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
