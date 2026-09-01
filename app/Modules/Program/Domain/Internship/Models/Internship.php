<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\Internship\Models;

use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Entities\InternshipPeriod;
use App\Modules\Program\Domain\Internship\Entities\InternshipState;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use Database\Factories\InternshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @property string $id
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property \Carbon\Carbon $registration_start_date
 * @property \Carbon\Carbon $registration_end_date
 * @property array $phases
 * @property array $required_document_ids
 * @property array $grading_weights
 * @property \App\Modules\Program\Domain\Internship\Enums\InternshipStatus $status
 * @property string $academic_year_id
 * @property string $name
 * @property string $description
 * @property-read \App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear|null $academicYear
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Enrollment\Domain\Placement\Models\Placement> $placements
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Enrollment\Domain\Registration\Models\Registration> $registrations
 */

#[
    Fillable([
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'registration_start_date',
        'registration_end_date',
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
        'registration_start_date' => 'date',
        'registration_end_date' => 'date',
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
