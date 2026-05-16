<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Core\LogAuditAction;
use App\Exceptions\RejectedException;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

/**
 * Deletes an academic year.
 *
 * S1 - Secure: Logged for audit trail. Cannot delete active year.
 */
class DeleteAcademicYearAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

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

        DB::transaction(function () use ($year) {
            $yearId = $year->id;
            $yearName = $year->name;

            $year->delete();

            $this->logAudit->execute(
                action: 'academic_year_deleted',
                subjectType: AcademicYear::class,
                subjectId: $yearId,
                payload: ['name' => $yearName],
            );
        });
    }
}
