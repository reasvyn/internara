<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use App\Domain\User\Services\DashboardService;
use Spatie\Permission\Models\Role as RoleModel;

describe('DashboardService', function () {
    beforeEach(function () {
        RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
        RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
        RoleModel::create(['name' => Role::TEACHER->value, 'guard_name' => 'web']);
        RoleModel::create(['name' => Role::SUPERVISOR->value, 'guard_name' => 'web']);
    });

    it('returns admin dashboard for super_admin', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN->value);

        expect(app(DashboardService::class)->getDashboardForUser($user))
            ->toBe('admin.dashboard');
    });

    it('returns student dashboard for student', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::STUDENT->value);

        expect(app(DashboardService::class)->getDashboardForUser($user))
            ->toBe('student.dashboard');
    });

    it('returns teacher dashboard for teacher', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::TEACHER->value);

        expect(app(DashboardService::class)->getDashboardForUser($user))
            ->toBe('teacher.dashboard');
    });

    it('returns supervisor dashboard for supervisor', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPERVISOR->value);

        expect(app(DashboardService::class)->getDashboardForUser($user))
            ->toBe('supervisor.dashboard');
    });
});
