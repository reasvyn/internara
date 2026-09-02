<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Placement\Enums\PlacementChangeStatus;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\PlacementChangeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property Carbon $processed_at
 * @property PlacementChangeStatus $status
 * @property string $registration_id
 * @property string $from_placement_id
 * @property string $to_placement_id
 * @property string $reason
 * @property string $requested_by
 * @property string $processed_by
 * @property string $rejection_reason
 * @property-read Registration|null $registration
 * @property-read Placement|null $fromPlacement
 * @property-read Placement|null $toPlacement
 * @property-read User|null $requester
 * @property-read User|null $processor
 */
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
