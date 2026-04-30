<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a specific location/quota within a company for an internship.
 */
class InternshipPlacement extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'internship_id',
        'name',
        'address',
        'quota',
        'filled_quota',
        'description',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(InternshipCompany::class, 'company_id');
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(InternshipRegistration::class, 'placement_id');
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
