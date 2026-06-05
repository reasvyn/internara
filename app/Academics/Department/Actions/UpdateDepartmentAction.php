<?php

declare(strict_types=1);

namespace App\Academics\Department\Actions;

use App\Academics\Department\Models\Department;
use App\Core\Actions\BaseAction;
use Illuminate\Support\Arr;

/**
 * Action to update an existing department.
 */
final class UpdateDepartmentAction extends BaseAction
{
    public function execute(Department $department, array $data): Department
    {
        return $this->transaction(function () use ($department, $data) {
            $department->update(Arr::except($data, ['id']));

            $this->log('department_updated', $department, $data);

            return $department;
        });
    }
}
