<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\AcademicYear\Actions;

use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearUpdated;
use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Actions\BaseCommandAction;

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
