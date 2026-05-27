<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\CacheKeys;
use App\Domain\Internship\Models\Internship;
use App\Domain\School\Models\Department;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Cache;

class GetAdminDashboardStatsAction extends BaseAction
{
    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        return Cache::remember(CacheKeys::ADMIN_DASHBOARD_STATS, 300, function () {
            return [
                'totalStudents' => User::role(RoleEnum::STUDENT->value)->count(),
                'totalTeachers' => User::role(RoleEnum::TEACHER->value)->count(),
                'totalDepartments' => Department::count(),
                'activeInternships' => Internship::where('status', 'active')->count(),
            ];
        });
    }
}
