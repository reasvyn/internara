<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\School\Models\AcademicYear;

/**
 * Activates a new academic year and deactivates the current one.
 *
 * S1 - Secure: Only one academic year can be active at a time.
 */
class ActivateAcademicYearAction
{
    public function execute(AcademicYear $year): AcademicYear
    {
        AcademicYear::where('is_active', true)->update(['is_active' => false]);

        $year->is_active = true;
        $year->save();

        return $year;
    }
}
