<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Actions;

use App\Academics\AcademicYear\Events\AcademicYearActivated;
use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use Illuminate\Support\Facades\Event;

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

            Event::dispatch(new AcademicYearActivated($year));

            $this->log('academic_year_activated', $year, [
                'name' => $year->name,
                'is_active' => true,
            ]);

            return $year;
        });
    }
}
