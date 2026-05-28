<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\School\Models\Department;
use Illuminate\Support\Facades\Validator;

final class SetupDepartmentAction extends BaseAction
{
    public function execute(string $schoolId, array $data): Department
    {
        Validator::validate($data, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return $this->transaction(function () use ($schoolId, $data) {
            $department = Department::updateOrCreate(
                ['school_id' => $schoolId, 'name' => $data['name']],
                [...$data, 'school_id' => $schoolId],
            );

            $this->log('department_setup_completed', $department, array_merge($data, ['school_id' => $schoolId]));

            return $department;
        });
    }
}
