<?php

declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Internship\Entities\InternshipPeriod;
use App\Domain\Internship\Entities\InternshipState;
use App\Domain\Internship\Enums\InternshipStatus;
use App\Domain\Placement\Models\Placement;
use App\Domain\Registration\Models\Registration;
use App\Domain\School\Models\AcademicYear;
use Database\Factories\InternshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year_id', 'name', 'start_date', 'end_date', 'registration_start_date', 'registration_end_date', 'description', 'status'])]
class Internship extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_start_date' => 'date',
        'registration_end_date' => 'date',
        'status' => InternshipStatus::class,
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function documentRequirements(): HasMany
    {
        return $this->hasMany(InternshipDocumentRequirement::class);
    }

    public function asInternshipPeriod(): InternshipPeriod
    {
        return InternshipPeriod::fromModel($this);
    }

    public function asInternshipState(): InternshipState
    {
        return InternshipState::fromModel($this);
    }

    protected static function newFactory(): InternshipFactory
    {
        return InternshipFactory::new();
    }
}
