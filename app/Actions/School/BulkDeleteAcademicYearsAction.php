<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Core\LogAuditAction;
use App\Exceptions\RejectedException;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class BulkDeleteAcademicYearsAction
{
    public function __construct(
        protected readonly LogAuditAction $logAudit,
    ) {}

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

        return DB::transaction(function () use ($years) {
            $count = 0;

            foreach ($years as $year) {
                $yearId = $year->id;
                $yearName = $year->name;

                $year->delete();

                $this->logAudit->execute(
                    action: 'academic_year_deleted',
                    subjectType: AcademicYear::class,
                    subjectId: $yearId,
                    payload: ['name' => $yearName],
                );

                $count++;
            }

            return $count;
        });
    }
}
