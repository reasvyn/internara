<?php

declare(strict_types=1);

use App\Services\Dashboard\DashboardService;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
});

it('returns admin dashboard for super admin', function () {
    $user = UserFactory::new()->create();
    $user->assignRole('super_admin');

    $result = (new DashboardService)->getDashboardForUser($user);

    expect($result)->toBe('dashboard.admin-dashboard');
});

it('returns admin dashboard for admin', function () {
    $user = UserFactory::new()->create();
    $user->assignRole('admin');

    $result = (new DashboardService)->getDashboardForUser($user);

    expect($result)->toBe('dashboard.admin-dashboard');
});

it('returns student dashboard for student', function () {
    $user = UserFactory::new()->create();
    $user->assignRole('student');

    $result = (new DashboardService)->getDashboardForUser($user);

    expect($result)->toBe('dashboard.student-dashboard');
});

it('returns teacher dashboard for teacher', function () {
    $user = UserFactory::new()->create();
    $user->assignRole('teacher');

    $result = (new DashboardService)->getDashboardForUser($user);

    expect($result)->toBe('dashboard.teacher-dashboard');
});

it('returns supervisor dashboard for supervisor', function () {
    $user = UserFactory::new()->create();
    $user->assignRole('supervisor');

    $result = (new DashboardService)->getDashboardForUser($user);

    expect($result)->toBe('dashboard.supervisor-dashboard');
});

it('returns user dashboard for unknown role', function () {
    $user = UserFactory::new()->create();

    $result = (new DashboardService)->getDashboardForUser($user);

    expect($result)->toBe('dashboard.user-dashboard');
});

it('returns shared stats for authenticated user', function () {
    $user = UserFactory::new()->create();
    $user->assignRole('student');
    $this->actingAs($user);

    $stats = (new DashboardService)->getSharedStats();

    expect($stats)->toHaveKeys(['user_name', 'user_role', 'last_login'])
        ->and($stats['user_name'])->toBe($user->name)
        ->and($stats['user_role'])->toBe('student');
});

it('returns null shared stats when unauthenticated', function () {
    $stats = (new DashboardService)->getSharedStats();

    expect($stats)->toHaveKeys(['user_name', 'user_role', 'last_login'])
        ->and($stats['user_name'])->toBeNull()
        ->and($stats['user_role'])->toBeNull();
});
