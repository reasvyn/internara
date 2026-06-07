<?php

declare(strict_types=1);

namespace App\Enrollment\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Enums\PlacementChangeStatus;
use App\User\Models\User;
use Database\Factories\PlacementChangeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'registration_id',
        'from_placement_id',
        'to_placement_id',
        'reason',
        'requested_by',
        'status',
        'processed_by',
        'processed_at',
        'rejection_reason',
    ]),
]
class PlacementChangeRequest extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'status' => PlacementChangeStatus::PENDING->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => PlacementChangeStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function fromPlacement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'from_placement_id');
    }

    public function toPlacement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'to_placement_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    protected static function newFactory(): PlacementChangeRequestFactory
    {
        return PlacementChangeRequestFactory::new();
    }
}
