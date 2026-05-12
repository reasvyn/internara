<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Core\LogAuditAction;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Deletes an academic year.
 *
 * S1 - Secure: Logged for audit trail. Cannot delete active year.
 */
class DeleteAcademicYearAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    /**
     * @throws RuntimeException when attempting to delete active year
     */
    public function execute(AcademicYear $year): void
    {
        if (! $year->asAcademicYearState()->canBeDeleted()) {
            throw new RuntimeException('Cannot delete an active academic year. Deactivate it first.');
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
