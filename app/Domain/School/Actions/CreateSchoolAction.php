<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\School\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Prevents duplicate school records.
 * S2 - Sustain: Single source of truth for school creation.
 */
class CreateSchoolAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): School
    {
        $existing = School::first();

        if ($existing) {
            throw new \RuntimeException('School record already exists. Use update instead.');
        }

        return DB::transaction(function () use ($data) {
            $school = School::create($data);

            $this->logAudit->execute(
                action: 'school_created',
                subjectType: School::class,
                subjectId: $school->id,
                payload: $data,
                module: 'School',
            );

            return $school;
        });
    }
}
