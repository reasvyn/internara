<?php

declare(strict_types=1);

namespace App\Academics\Department\Actions;

use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Models\Department;
use App\Core\Actions\BaseAction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;

final class CreateDepartmentAction extends BaseAction
{
    public function execute(array $data): Department
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->transaction(function () use ($validated) {
            $department = Department::create($validated);

            Event::dispatch(new DepartmentCreated($department));

            $this->log('department_created', $department, $validated);

            return $department;
        });
    }
}
