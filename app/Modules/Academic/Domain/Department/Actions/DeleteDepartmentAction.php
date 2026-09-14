<?php

declare(strict_types=1);

namespace App\Modules\Academic\Domain\Department\Actions;

use App\Modules\Academic\Domain\Department\Events\DepartmentDeleted;
use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;

/**
 * Action to delete a department.
 */
final class DeleteDepartmentAction extends BaseCommandAction
{
    public function execute(Department $department): void
    {
        if ($department->profiles()->exists()) {
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
