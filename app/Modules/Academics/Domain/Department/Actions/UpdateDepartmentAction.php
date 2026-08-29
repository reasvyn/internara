<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\Department\Actions;

use App\Modules\Academics\Domain\Department\Events\DepartmentUpdated;
use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Core\Actions\BaseCommandAction;
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

            event(new DepartmentUpdated($department));

            return $department;
        });
    }
}
