<?php

declare(strict_types=1);

use App\Academics\Department\Models\Department;
use App\Academics\Department\Policies\DepartmentPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('viewAny and view return true for any user', function () {
    $policy = new DepartmentPolicy;
    $user = User::factory()->create();
    $department = Department::factory()->make();

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->view($user, $department))->toBeTrue();
});

test('create returns true for admin users', function () {
    $policy = new DepartmentPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($policy->create($admin))->toBeTrue();
});

test('create returns false for non-admin users', function () {
    $policy = new DepartmentPolicy;
    $user = User::factory()->create();
    $user->assignRole('teacher');

    expect($policy->create($user))->toBeFalse();
});

test('forceDelete returns false for non-super-admin', function () {
    $policy = new DepartmentPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $department = Department::factory()->make();

    expect($policy->forceDelete($admin, $department))->toBeFalse();
});
