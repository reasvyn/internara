<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Internship\InternshipPeriod;
use App\Enums\Internship\InternshipStatus;
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
}
