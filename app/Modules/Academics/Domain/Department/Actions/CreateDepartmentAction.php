<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\Department\Actions;

use App\Modules\Academics\Domain\Department\Events\DepartmentCreated;
use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Core\Actions\BaseCommandAction;
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
