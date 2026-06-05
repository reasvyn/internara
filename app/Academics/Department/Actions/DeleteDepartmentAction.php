<?php

declare(strict_types=1);

namespace App\Academics\Department\Actions;

use App\Academics\Department\Models\Department;
use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;

/**
 * Action to delete a department.
 */
final class DeleteDepartmentAction extends BaseAction
{
    public function execute(Department $department): void
    {
        if ($department->profiles()->count() > 0) {
            throw new RejectedException('Cannot delete department with active profiles.');
        }

        $this->transaction(function () use ($department) {
            $this->log('department_deleted', $department, ['name' => $department->name]);

            $department->delete();
        });
    }
}
