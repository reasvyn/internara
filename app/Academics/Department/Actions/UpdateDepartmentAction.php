<?php

declare(strict_types=1);

namespace App\Academics\Department\Actions;

use App\Academics\Department\Models\Department;
use App\Core\Actions\BaseCommandAction;
use Illuminate\Support\Facades\Validator;

final class UpdateDepartmentAction extends BaseCommandAction
{
    public function execute(Department $department, array $data): Department
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:100', 'unique:departments,name,'.$department->id],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->transaction(function () use ($department, $validated) {
            $department->update($validated);

            $this->log('department_updated', $department, $validated);

            return $department;
        });
    }
}
