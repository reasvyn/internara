<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\School\Models\AcademicYear;

/**
 * Updates an existing academic year.
 *
 * S1 - Secure: Logged for audit trail.
 * S2 - Sustain: Atomic updates.
 */
class UpdateAcademicYearAction extends BaseAction
{
    public function execute(AcademicYear $year, array $data): AcademicYear
    {
        return $this->transaction(function () use ($year, $data) {
            $year->update($data);

            $this->log('academic_year_updated', $year, $data);

            return $year;
        });
    }
}
