<?php

declare(strict_types=1);

namespace App\Academics\Department\Actions;

use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Models\Department;
use App\Core\Actions\BaseCommandAction;
use Illuminate\Support\Facades\Validator;

final class CreateDepartmentAction extends BaseCommandAction
{
    public function execute(array $data): Department
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->transaction(function () use ($validated) {
            $department = Department::create($validated);

            $this->dispatchEvent(new DepartmentCreated($department));

            $this->log('department_created', $department, $validated);

            return $department;
        });
    }
}
