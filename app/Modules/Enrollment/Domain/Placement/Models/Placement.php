<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Placement\Entities\PlacementCapacity;
use App\Modules\Enrollment\Domain\Placement\Entities\PlacementState;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\Program\Domain\Internship\Models\Internship;
use Database\Factories\PlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a specific location/quota within a company for an internship.
 */

/**
 * @property string $id
 * @property string $company_id
 * @property string $internship_id
 * @property string $name
 * @property string $address
 * @property string $quota
 * @property string $filled_quota
 * @property string $description
 * @property-read Company|null $company
 * @property-read Internship|null $internship
 * @property-read Collection<int,Registration> $registrations
 */
#[
    Fillable([
        'company_id',
        'internship_id',
        'name',
        'address',
        'quota',
        'filled_quota',
        'description',
    ]),
]
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

    public function availableSlots(): int
    {
        return $this->quota - $this->filled_quota;
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
