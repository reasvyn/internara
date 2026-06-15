<?php

declare(strict_types=1);

namespace App\Academics\Department\Actions;

use App\Academics\Department\Events\DepartmentDeleted;
use App\Academics\Department\Models\Department;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;

/**
 * Action to delete a department.
 */
final class DeleteDepartmentAction extends BaseCommandAction
{
    public function execute(Department $department): void
    {
        if ($department->profiles()->count() > 0) {
            throw new RejectedException(__('department.cannot_delete_with_profiles'));
        }

        $this->transaction(function () use ($department) {
            $name = $department->name;

            $department->delete();

            $this->dispatchEvent(new DepartmentDeleted($department));

            $this->log('department_deleted', $department, ['name' => $name]);
        });
    }
}
