<?php

declare(strict_types=1);

namespace App\Program\Internship\Models;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Models\BaseModel;
use App\Enrollment\Models\Placement;
use App\Enrollment\Models\Registration;
use App\Program\Internship\Entities\InternshipPeriod;
use App\Program\Internship\Entities\InternshipState;
use App\Program\Internship\Enums\InternshipStatus;
use Database\Factories\InternshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[
    Fillable([
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'description',
        'status',
        'phases',
        'required_document_ids',
        'grading_weights',
    ]),
]
class Internship extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => InternshipStatus::class,
        'phases' => 'json',
        'required_document_ids' => 'json',
        'grading_weights' => 'json',
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
