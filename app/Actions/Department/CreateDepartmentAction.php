<?php

declare(strict_types=1);

namespace App\Actions\Department;

use App\Actions\Audit\LogAuditAction;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a new department.
 */
class CreateDepartmentAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            $department = Department::create($data);

            $this->logAudit->execute(
                action: 'department_created',
                subjectType: Department::class,
                subjectId: $department->id,
                payload: $data,
                module: 'Department'
            );

            return $department;
        });
    }
}
