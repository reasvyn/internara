<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Core\LogAuditAction;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

/**
 * Updates an existing academic year.
 *
 * S1 - Secure: Logged for audit trail.
 * S2 - Sustain: Atomic updates.
 */
class UpdateAcademicYearAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(AcademicYear $year, array $data): AcademicYear
    {
        return DB::transaction(function () use ($year, $data) {
            $year->update($data);

            $this->logAudit->execute(
                action: 'academic_year_updated',
                subjectType: AcademicYear::class,
                subjectId: $year->id,
                payload: $data,
            );

            return $year;
        });
    }
}
