<?php

declare(strict_types=1);

namespace App\Domain\Academics\Aggregates\AcademicYear\Actions;

use App\Domain\Academics\Aggregates\AcademicYear\Models\AcademicYear;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;

/**
 * Activates a new academic year and deactivates the current one.
 *
 * S1 - Secure: Only one academic year can be active at a time.
 */
final class ActivateAcademicYearAction extends BaseAction
{
    public function execute(AcademicYear $year): AcademicYear
    {
        if (! $year->asAcademicYearState()->canBeActivated()) {
            throw new RejectedException(__('academic_year.already_active'));
        }

        return $this->transaction(function () use ($year) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);

            $year->is_active = true;
            $year->save();

            $this->log('academic_year_activated', $year, [
                'name' => $year->name,
                'is_active' => true,
            ]);

            return $year;
        });
    }
}
