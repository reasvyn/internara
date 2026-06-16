<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Actions;

use App\Academics\AcademicYear\Events\AcademicYearUpdated;
use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Actions\BaseCommandAction;

/**
 * Updates an existing academic year.
 *
 * S1 - Secure: Logged for audit trail.
 * S2 - Sustain: Atomic updates.
 */
final class UpdateAcademicYearAction extends BaseCommandAction
{
    public function execute(AcademicYear $year, array $data): AcademicYear
    {
        return $this->transaction(function () use ($year, $data) {
            $year->update($data);

            $this->log('academic_year_updated', $year, $data);

            event(new AcademicYearUpdated($year));

            return $year;
        });
    }
}
