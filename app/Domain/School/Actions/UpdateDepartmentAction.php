<?php

declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\School\Models\Department;
use Illuminate\Support\Arr;

/**
 * Action to update an existing department.
 */
class UpdateDepartmentAction extends BaseAction
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
