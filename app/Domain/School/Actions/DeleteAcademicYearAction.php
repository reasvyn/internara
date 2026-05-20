<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\School\Models\AcademicYear;

/**
 * Deletes an academic year.
 *
 * S1 - Secure: Logged for audit trail. Cannot delete active year.
 */
class DeleteAcademicYearAction extends BaseAction
{
    /**
     * @throws RuntimeException when the year is active or has linked data
     */
    public function execute(AcademicYear $year): void
    {
        $state = $year->asAcademicYearState();

        if (! $state->canBeDeleted()) {
            if ($state->isActive()) {
                throw new RejectedException(__('academic_year.cannot_delete_active', ['name' => $year->name]));
            }

            throw new RejectedException(__('academic_year.cannot_delete_has_data', ['name' => $year->name]));
        }

        $this->transaction(function () use ($year) {
            $this->log('academic_year_deleted', $year, ['name' => $year->name]);

            $year->delete();
        });
    }
}
