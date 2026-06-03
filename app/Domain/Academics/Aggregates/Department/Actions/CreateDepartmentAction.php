<?php

declare(strict_types=1);

namespace App\Domain\Academics\Aggregates\Department\Actions;

use App\Domain\Academics\Aggregates\Department\Models\Department;
use App\Domain\Core\Actions\BaseAction;
use Illuminate\Support\Arr;

/**
 * Action to create a new department.
 */
final class CreateDepartmentAction extends BaseAction
{
    public function execute(array $data): Department
    {
        return $this->transaction(function () use ($data) {
            $department = Department::create(Arr::except($data, ['id']));

            $this->log('department_created', $department, $data);

            return $department;
        });
    }
}
