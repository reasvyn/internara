<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Placement\PlacementCapacity;
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

    public function entity(): PlacementCapacity
    {
        return PlacementCapacity::fromModel($this);
    }

    public function isFull(): bool
    {
        return $this->entity()->isFull();
    }

    public function availableSlots(): int
    {
        return $this->entity()->availableSlots();
    }

    public function hasAvailableSlots(): bool
    {
        return $this->entity()->hasAvailableSlots();
    }
}
