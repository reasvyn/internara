<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\School\Models\Department;
use Illuminate\Support\Facades\DB;

/**
 * Action to update an existing department.
 */
class UpdateDepartmentAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            unset($data['id']);
            $department->update($data);

            $this->logAudit->execute(
                action: 'department_updated',
                subjectType: Department::class,
                subjectId: $department->id,
                payload: $data,
                module: 'Department',
            );

            return $department;
        });
    }
}
