<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\School\Models\Department;
use Illuminate\Support\Facades\DB;

/**
 * Setup a Department during initial installation.
 */
class SetupDepartmentAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    /**
     * @param array{name: string, description?: ?string} $data
     */
    public function execute(string $schoolId, array $data): Department
    {
        return DB::transaction(function () use ($schoolId, $data) {
            $department = Department::create([
                ...$data,
                'school_id' => $schoolId,
            ]);

            $this->logAudit->execute(
                action: 'department_setup_completed',
                subjectType: Department::class,
                subjectId: $department->id,
                payload: array_merge($data, ['school_id' => $schoolId]),
                module: 'Setup',
            );

            return $department;
        });
    }
}
