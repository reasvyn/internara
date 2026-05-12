<?php

declare(strict_types=1);

namespace App\Actions\School;

use App\Actions\Core\LogAuditAction;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Setup the School profile during initial installation.
 */
class SetupSchoolAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    /**
     * @param array{name: string, institutional_code: string, address?: string, email?: ?string, phone?: ?string, website?: ?string, principal_name?: ?string} $data
     */
    public function execute(array $data): School
    {
        return DB::transaction(function () use ($data) {
            $school = School::first();

            if ($school) {
                if (! $school->asSchoolState()->canBeCreated()) {
                    throw new RuntimeException('School already exists.');
                }

                $school->update($data);
            } else {
                $school = School::create($data);
            }

            $this->logAudit->execute(
                action: 'school_setup_completed',
                subjectType: School::class,
                subjectId: $school->id,
                payload: [
                    'name' => $data['name'],
                    'code' => $data['institutional_code'],
                ],
                module: 'Setup',
            );

            return $school;
        });
    }
}
