<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Services\DashboardService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {});

test('admin and super admin get sysadmin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $service = new DashboardService;
    $result = $service->getDashboardForUser($user);

    expect($result)->toBe('sysadmin.dashboard');
});

test('super admin gets sysadmin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    $service = new DashboardService;
    $result = $service->getDashboardForUser($user);

    expect($result)->toBe('sysadmin.dashboard');
});

test('student gets student dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $service = new DashboardService;
    $result = $service->getDashboardForUser($user);

    expect($result)->toBe('student.dashboard');
});

test('teacher gets teacher dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    $service = new DashboardService;
    $result = $service->getDashboardForUser($user);

    expect($result)->toBe('teacher.dashboard');
});

test('supervisor gets supervisor dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('supervisor');

    $service = new DashboardService;
    $result = $service->getDashboardForUser($user);

    expect($result)->toBe('supervisor.dashboard');
});

test('unknown role falls back to user dashboard', function () {
    $user = User::factory()->create();

    $service = new DashboardService;
    $result = $service->getDashboardForUser($user);

    expect($result)->toBe('user.dashboard');
});
