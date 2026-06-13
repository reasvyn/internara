<?php

declare(strict_types=1);

use App\Assessment\Models\Assessment;
use App\Assessment\Policies\AssessmentPolicy;
use App\User\Models\User;

beforeEach(function () {
    $this->policy = app(AssessmentPolicy::class);
});

test('super admin can view any assessment', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('teacher can view any assessment', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('student cannot view any assessment list', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    expect($this->policy->viewAny($user))->toBeFalse();
});

test('admin can view any assessment', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $assessment = Assessment::factory()->create();

    expect($this->policy->view($user, $assessment))->toBeTrue();
});

test('teacher can create assessment', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    expect($this->policy->create($user))->toBeTrue();
});

test('student cannot create assessment', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    expect($this->policy->create($user))->toBeFalse();
});

test('only admin can delete non-finalized assessment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $assessment = Assessment::factory()->create();

    expect($this->policy->delete($admin, $assessment))->toBeTrue();
});

test('admin cannot delete finalized assessment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $assessment = Assessment::factory()->finalized()->create();

    expect($this->policy->delete($admin, $assessment))->toBeFalse();
});
