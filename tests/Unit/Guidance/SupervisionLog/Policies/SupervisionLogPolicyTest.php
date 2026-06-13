<?php

declare(strict_types=1);

use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\Guidance\SupervisionLog\Policies\SupervisionLogPolicy;
use App\User\Models\User;

beforeEach(function () {
    $this->policy = app(SupervisionLogPolicy::class);
});

test('admin can view any log', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->viewAny($admin))->toBeTrue();
});

test('student cannot view any log', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    expect($this->policy->viewAny($student))->toBeFalse();
});

test('teacher can create log', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    expect($this->policy->create($teacher))->toBeTrue();
});

test('student cannot create log', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    expect($this->policy->create($student))->toBeFalse();
});

test('supervisor can create log', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');

    expect($this->policy->create($supervisor))->toBeTrue();
});

test('admin can verify log', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->verify($admin, SupervisionLog::factory()->create()))->toBeTrue();
});

test('supervisor cannot verify log', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');

    expect($this->policy->verify($supervisor, SupervisionLog::factory()->create()))->toBeFalse();
});
