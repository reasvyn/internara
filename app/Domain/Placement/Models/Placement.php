<?php

declare(strict_types=1);

namespace App\Domain\Placement\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Placement\Entities\PlacementCapacity;
use App\Domain\Placement\Entities\PlacementState;
use Database\Factories\PlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a specific location/quota within a company for an internship.
 */
#[Fillable(['company_id', 'internship_id', 'name', 'address', 'quota', 'filled_quota', 'description'])]
class Placement extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): PlacementFactory
    {
        return PlacementFactory::new();
    }

    protected $table = 'placements';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'placement_id');
    }

    public function asPlacementCapacity(): PlacementCapacity
    {
        return PlacementCapacity::fromModel($this);
    }

    public function asPlacementState(): PlacementState
    {
        return PlacementState::fromModel($this);
    }
}
