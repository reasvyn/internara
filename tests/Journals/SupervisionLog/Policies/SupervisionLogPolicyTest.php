<?php

declare(strict_types=1);

use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\Journals\SupervisionLog\Policies\SupervisionLogPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->policy = app(SupervisionLogPolicy::class);
});

test('admin can view any log', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->viewAny($admin))->toBeTrue();
});

test('student can view any log', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    expect($this->policy->viewAny($student))->toBeTrue();
});

test('only student can create log', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    expect($this->policy->create($student))->toBeTrue();

    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    expect($this->policy->create($teacher))->toBeFalse();

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    expect($this->policy->create($supervisor))->toBeFalse();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    expect($this->policy->create($admin))->toBeFalse();
});

test('admin can delete log', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->delete($admin, SupervisionLog::factory()->create()))->toBeTrue();
});
