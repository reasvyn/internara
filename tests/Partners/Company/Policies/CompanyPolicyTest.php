<?php

declare(strict_types=1);

use App\Partners\Company\Models\Company;
use App\Partners\Company\Policies\CompanyPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('viewAny and view return true for any user', function () {
    $policy = new CompanyPolicy;
    $user = User::factory()->create();
    $company = Company::factory()->make();

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->view($user, $company))->toBeTrue();
});

test('create returns true for admin users', function () {
    $policy = new CompanyPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($policy->create($admin))->toBeTrue();
});

test('create returns false for non-admin users', function () {
    $policy = new CompanyPolicy;
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    expect($policy->create($teacher))->toBeFalse();
});

test('update returns true for admin users', function () {
    $policy = new CompanyPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $company = Company::factory()->make();

    expect($policy->update($admin, $company))->toBeTrue();
});

test('update returns false for non-admin users', function () {
    $policy = new CompanyPolicy;
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    $company = Company::factory()->make();

    expect($policy->update($teacher, $company))->toBeFalse();
});

test('delete returns false for company with placements', function () {
    $policy = new CompanyPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $company = Company::factory()->hasPlacements(1)->create();

    expect($policy->delete($admin, $company))->toBeFalse();
});
