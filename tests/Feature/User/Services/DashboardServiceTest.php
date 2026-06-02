<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use App\Domain\User\Services\DashboardService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('DashboardService', function () {
    it('returns admin dashboard for super admin', function () {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $route = (new DashboardService)->getDashboardForUser($user);

        expect($route)->toBe('admin.dashboard');
    });

    it('returns admin dashboard for admin', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $route = (new DashboardService)->getDashboardForUser($user);

        expect($route)->toBe('admin.dashboard');
    });

    it('returns student dashboard for student', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        $route = (new DashboardService)->getDashboardForUser($user);

        expect($route)->toBe('student.dashboard');
    });

    it('returns teacher dashboard for teacher', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $route = (new DashboardService)->getDashboardForUser($user);

        expect($route)->toBe('teacher.dashboard');
    });

    it('returns supervisor dashboard for supervisor', function () {
        $user = User::factory()->create();
        $user->assignRole('supervisor');

        $route = (new DashboardService)->getDashboardForUser($user);

        expect($route)->toBe('supervisor.dashboard');
    });

    it('returns default user dashboard for unknown role', function () {
        $user = User::factory()->create();

        $route = (new DashboardService)->getDashboardForUser($user);

        expect($route)->toBe('user.dashboard');
    });
});
