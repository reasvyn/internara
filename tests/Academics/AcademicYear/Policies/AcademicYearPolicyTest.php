<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Academics\AcademicYear\Policies\AcademicYearPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('viewAny and view return true for any user', function () {
    $policy = new AcademicYearPolicy;
    $user = User::factory()->create();
    $year = AcademicYear::factory()->make();

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->view($user, $year))->toBeTrue();
});

test('create returns true for admin users', function () {
    $policy = new AcademicYearPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($policy->create($admin))->toBeTrue();
});

test('create returns false for non-admin users', function () {
    $policy = new AcademicYearPolicy;
    $user = User::factory()->create();
    $user->assignRole('teacher');

    expect($policy->create($user))->toBeFalse();
});

test('activate and delete return false for non-super-admin', function () {
    $policy = new AcademicYearPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $year = AcademicYear::factory()->make();

    expect($policy->activate($admin, $year))->toBeFalse();
    expect($policy->delete($admin, $year))->toBeFalse();
});
