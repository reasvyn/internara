<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\AcademicYear\AcademicYearState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function asAcademicYearState(): AcademicYearState
    {
        return AcademicYearState::fromModel($this);
    }
}
