<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Core\LogAuditAction;
use App\Models\School\AcademicYear;
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
     * @throws \InvalidArgumentException when attempting to delete active year
     */
    public function execute(AcademicYear $year): void
    {
        if ($year->is_active) {
            throw new \InvalidArgumentException('Cannot delete an active academic year. Deactivate it first.');
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
