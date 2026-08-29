<?php

declare(strict_types=1);

namespace App\Modules\Setup\Domain\SetupWizard\Actions;

use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Core\Actions\BaseCommandAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

final class SetupDepartmentAction extends BaseCommandAction
{
    public function execute(array $data): Department
    {
        Validator::validate($data, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return $this->transaction(function () use ($data) {
            $department = Department::updateOrCreate(
                ['name' => $data['name']],
                Arr::only($data, ['name', 'description']),
            );

            $this->log('department_setup_completed', $department, $data);

            return $department;
        });
    }
}
