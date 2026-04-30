<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Audit\LogAuditAction;
use App\Models\School;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Action to update the institution's profile.
 * 
 * S1 - Secure: Logged for accountability.
 * S2 - Sustain: Atomic updates.
 */
class UpdateSchoolAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(School $school, array $data): School
    {
        return DB::transaction(function () use ($school, $data) {
            // Extract logo file if present
            $logoFile = $data['logo_file'] ?? null;
            unset($data['logo_file']);

            $school->update($data);

            // Handle logo if provided
            if ($logoFile !== null) {
                $school->setLogo($logoFile);
            }

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
