<?php

declare(strict_types=1);

namespace App\Academics\Department\Actions;

use App\Academics\Department\Events\DepartmentDeleted;
use App\Academics\Department\Models\Department;
use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use Illuminate\Support\Facades\Event;

/**
 * Action to delete a department.
 */
final class DeleteDepartmentAction extends BaseAction
{
    public function execute(Department $department): void
    {
        if ($department->profiles()->count() > 0) {
            throw new RejectedException(__('department.cannot_delete_with_profiles'));
        }

        $this->transaction(function () use ($department) {
            $name = $department->name;

            $department->delete();

            Event::dispatch(new DepartmentDeleted($department));

            $this->log('department_deleted', $department, ['name' => $name]);
        });
    }
}
