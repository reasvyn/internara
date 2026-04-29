<?php

declare(strict_types=1);

namespace App\Actions\Department;

use App\Actions\Audit\LogAuditAction;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

/**
 * Action to delete a department.
 */
class DeleteDepartmentAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(Department $department): void
    {
        DB::transaction(function () use ($department) {
            $departmentId = $department->id;
            $departmentName = $department->name;

            $department->delete();

            $this->logAudit->execute(
                action: 'department_deleted',
                subjectType: Department::class,
                subjectId: $departmentId,
                payload: ['name' => $departmentName],
                module: 'Department'
            );
        });
    }
}
