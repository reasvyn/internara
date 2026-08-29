<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\AcademicYear\Actions;

use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearDeleted;
use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;

/**
 * Deletes an academic year.
 *
 * S1 - Secure: Logged for audit trail. Cannot delete active year.
 */
final class DeleteAcademicYearAction extends BaseCommandAction
{
    /**
     * @throws RejectedException when the year is active or has linked data
     */
    public function execute(AcademicYear $year): void
    {
        $state = $year->asAcademicYearState();

        if (! $state->canBeDeleted()) {
            if ($state->isActive()) {
                throw new RejectedException(
                    __('academic_year.cannot_delete_active', ['name' => $year->name]),
                );
            }

            throw new RejectedException(
                __('academic_year.cannot_delete_has_data', ['name' => $year->name]),
            );
        }

        $this->transaction(function () use ($year) {
            $this->log('academic_year_deleted', $year, ['name' => $year->name]);

            event(new AcademicYearDeleted($year));

            $year->delete();
        });
    }
}
