<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\School\Models\AcademicYear;

class BulkDeleteAcademicYearsAction extends BaseAction
{
    public function execute(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $years = AcademicYear::whereIn('id', $ids)->get();

        if ($years->isEmpty()) {
            return 0;
        }

        foreach ($years as $year) {
            $state = $year->asAcademicYearState();

            if (! $state->canBeDeleted()) {
                $key = $state->isActive() ? 'cannot_delete_active' : 'cannot_delete_has_data';

                throw new RejectedException(
                    __("academic_year.{$key}", ['name' => $year->name]),
                );
            }
        }

        return $this->transaction(function () use ($years) {
            $count = 0;

            foreach ($years as $year) {
                $yearId = $year->id;
                $yearName = $year->name;

                $year->delete();

                $this->log(
                    'academic_year_deleted',
                    $year,
                    ['name' => $yearName],
                );

                $count++;
            }

            return $count;
        });
    }
}
