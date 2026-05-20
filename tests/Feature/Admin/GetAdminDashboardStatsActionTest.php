<?php

declare(strict_types=1);

use App\Domain\Admin\Actions\GetAdminDashboardStatsAction;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

describe('GetAdminDashboardStatsAction', function () {
    beforeEach(function () {
        collect(['super_admin', 'student', 'teacher'])->each(fn ($r) => RoleModel::create(['name' => $r, 'guard_name' => 'web'])
        );
    });

    it('returns dashboard stats', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN->value);

        $stats = app(GetAdminDashboardStatsAction::class)->execute();

        expect($stats)->toHaveKeys(['totalStudents', 'totalTeachers', 'totalDepartments', 'activeInternships']);
    });
});
