<?php

declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a specific location/quota within a company for an internship.
 */
#[Fillable(['company_id', 'internship_id', 'name', 'address', 'quota', 'filled_quota', 'description'])]
class Placement extends Model
{
    use HasFactory, HasUuid;

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

    public function isFull(): bool
    {
        return $this->filled_quota >= $this->quota;
    }

    public function availableSlots(): int
    {
        return max(0, $this->quota - $this->filled_quota);
    }

    public function hasAvailableSlots(): bool
    {
        return $this->availableSlots() > 0;
    }
}
