<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Actions;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;

/**
 * Deletes an academic year.
 *
 * S1 - Secure: Logged for audit trail. Cannot delete active year.
 */
final class DeleteAcademicYearAction extends BaseAction
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
