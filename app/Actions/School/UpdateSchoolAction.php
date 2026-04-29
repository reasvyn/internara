<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Audit\LogAuditAction;
use App\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * Action to update the institution's profile.
 * 
 * S1 - Secure: Logged for accountability.
 * S2 - Sustain: Atomic updates.
 */
class UpdateSchoolAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(School $school, array $data): School
    {
        return DB::transaction(function () use ($school, $data) {
            $school->update($data);

            $this->logAudit->execute(
                action: 'school_profile_updated',
                subjectType: School::class,
                subjectId: $school->id,
                payload: $data,
                module: 'School'
            );

            return $school;
        });
    }
}
