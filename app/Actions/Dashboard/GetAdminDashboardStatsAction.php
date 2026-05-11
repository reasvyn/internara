<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Enums\Auth\Role as RoleEnum;
use App\Models\Department;
use App\Models\Internship;
use App\Models\User;

class GetAdminDashboardStatsAction
{
    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        return [
            'totalStudents' => User::role(RoleEnum::STUDENT->value)->count(),
            'totalTeachers' => User::role(RoleEnum::TEACHER->value)->count(),
            'totalDepartments' => Department::count(),
            'activeInternships' => Internship::where('status', 'active')->count(),
        ];
    }
}
