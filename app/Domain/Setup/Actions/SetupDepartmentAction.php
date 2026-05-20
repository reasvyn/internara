<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\School\Models\Department;

class SetupDepartmentAction extends BaseAction
{
    public function execute(string $schoolId, array $data): Department
    {
        return $this->withErrorHandling(function () use ($schoolId, $data) {
            return $this->transaction(function () use ($schoolId, $data) {
                $department = Department::updateOrCreate(
                    ['school_id' => $schoolId, 'name' => $data['name']],
                    [...$data, 'school_id' => $schoolId],
                );

                $this->log('department_setup_completed', $department, array_merge($data, ['school_id' => $schoolId]));

                return $department;
            });
        }, 'Failed to setup department');
    }
}
