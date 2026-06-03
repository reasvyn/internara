<?php

declare(strict_types=1);

namespace App\Domain\Academics\Aggregates\AcademicYear\Models;

use App\Domain\Academics\Aggregates\AcademicYear\Entities\AcademicYearState;
use App\Domain\Assessment\Aggregates\Assessment\Models\Assessment;
use App\Domain\Core\Models\BaseModel;
use App\Domain\Program\Aggregates\Internship\Models\Internship;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents an academic year for organizing internship cohorts.
 *
 * S2 - Sustain: Single source of truth for academic year context.
 */
#[Fillable(['name', 'start_date', 'end_date', 'is_active'])]
class AcademicYear extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function asAcademicYearState(): AcademicYearState
    {
        return AcademicYearState::fromModel($this);
    }

    protected static function newFactory(): AcademicYearFactory
    {
        return AcademicYearFactory::new();
    }
}
