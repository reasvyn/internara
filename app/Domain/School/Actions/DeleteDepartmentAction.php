<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\School\Models\Department;

/**
 * Action to delete a department.
 */
class DeleteDepartmentAction extends BaseAction
{
    public function execute(Department $department): void
    {
        if (! $department->asDepartmentState()->canBeDeleted()) {
            throw new RejectedException('Cannot delete department with active profiles.');
        }

        $this->transaction(function () use ($department) {
            $this->log('department_deleted', $department, ['name' => $department->name]);

            $department->delete();
        });
    }
}
