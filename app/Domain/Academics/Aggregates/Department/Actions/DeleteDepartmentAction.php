<?php

declare(strict_types=1);

namespace App\Domain\Academics\Aggregates\Department\Actions;

use App\Domain\Academics\Aggregates\Department\Models\Department;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;

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
