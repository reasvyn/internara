<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Audit\LogAuditAction;
use App\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * Setup the School profile during initial installation.
 */
class SetupSchoolAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): School
    {
        return DB::transaction(function () use ($data) {
            $school = School::create($data);

            $this->logAudit->execute(
                action: 'school_setup_completed',
                subjectType: School::class,
                subjectId: $school->id,
                payload: $data,
                module: 'Setup'
            );

            return $school;
        });
    }
}
