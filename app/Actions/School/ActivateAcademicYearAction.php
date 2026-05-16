<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Exceptions\RejectedException;
use App\Models\AcademicYear;

/**
 * Activates a new academic year and deactivates the current one.
 *
 * S1 - Secure: Only one academic year can be active at a time.
 */
class ActivateAcademicYearAction
{
    public function execute(AcademicYear $year): AcademicYear
    {
        if (! $year->asAcademicYearState()->canBeActivated()) {
            throw new RejectedException(__('academic_year.already_active'));
        }

        AcademicYear::where('is_active', true)->update(['is_active' => false]);

        $year->is_active = true;
        $year->save();

        return $year;
    }
}
