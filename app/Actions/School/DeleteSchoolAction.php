<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Audit\LogAuditAction;
use App\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Only Super Admin can delete school. Blocks if departments exist.
 * S3 - Scalable: Cascades department nullification via FK constraint.
 */
class DeleteSchoolAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(School $school): void
    {
        if ($school->departments()->exists()) {
            throw new \RuntimeException('Cannot delete school: it has associated departments.');
        }

        DB::transaction(function () use ($school) {
            $school->delete();

            $this->logAudit->execute(
                action: 'school_deleted',
                subjectType: School::class,
                subjectId: $school->id,
                module: 'School'
            );
        });
    }
}
