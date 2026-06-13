<?php

declare(strict_types=1);

use App\Certification\Certificate\Models\Certificate;
use App\Certification\Certificate\Policies\CertificatePolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {

    $this->policy = app(CertificatePolicy::class);
});

test('admin can view any certificate', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->viewAny($admin))->toBeTrue();
});

test('student can view own certificate', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    expect($this->policy->viewAny($student))->toBeTrue();
});

test('teacher cannot view certificates', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    expect($this->policy->viewAny($teacher))->toBeFalse();
});

test('admin can create certificate', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->create($admin))->toBeTrue();
});

test('student cannot create certificate', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    expect($this->policy->create($student))->toBeFalse();
});

test('nobody can update certificate', function () {
    $certificate = Certificate::factory()->create();

    expect($this->policy->update(User::factory()->create(), $certificate))->toBeFalse();
});

test('nobody can delete certificate', function () {
    $certificate = Certificate::factory()->create();

    expect($this->policy->delete(User::factory()->create(), $certificate))->toBeFalse();
});

test('admin can revoke certificate', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->revoke($admin, Certificate::factory()->create()))->toBeTrue();
});
