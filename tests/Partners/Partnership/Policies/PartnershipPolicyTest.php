<?php

declare(strict_types=1);

use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Models\Partnership;
use App\Partners\Partnership\Policies\PartnershipPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('viewAny returns true for admin, teacher, and super admin', function () {
    $policy = new PartnershipPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($policy->viewAny($admin))->toBeTrue();
});

test('viewAny returns false for student', function () {
    $policy = new PartnershipPolicy;
    $student = User::factory()->create();
    $student->assignRole('student');

    expect($policy->viewAny($student))->toBeFalse();
});

test('create returns true for admin users', function () {
    $policy = new PartnershipPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($policy->create($admin))->toBeTrue();
});

test('create returns false for teacher', function () {
    $policy = new PartnershipPolicy;
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    expect($policy->create($teacher))->toBeFalse();
});

test('update and delete return true for admin', function () {
    $policy = new PartnershipPolicy;
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->make(['company_id' => $company->id]);

    expect($policy->update($admin, $partnership))->toBeTrue();
    expect($policy->delete($admin, $partnership))->toBeTrue();
});
