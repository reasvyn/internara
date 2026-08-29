<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\AcademicYear\Actions;

use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearActivated;
use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;

/**
 * Activates a new academic year and deactivates the current one.
 *
 * S1 - Secure: Only one academic year can be active at a time.
 */
final class ActivateAcademicYearAction extends BaseCommandAction
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

            $this->dispatchEvent(new AcademicYearActivated($year));

            $this->log('academic_year_activated', $year, [
                'name' => $year->name,
                'is_active' => true,
            ]);

            return $year;
        });
    }
}
