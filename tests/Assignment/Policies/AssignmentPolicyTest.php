<?php

declare(strict_types=1);

use App\Assignment\Models\Assignment;
use App\Assignment\Policies\AssignmentPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {

    $this->policy = app(AssignmentPolicy::class);
});

test('teacher can create assignment', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    expect($this->policy->create($user))->toBeTrue();
});

test('student cannot create assignment', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    expect($this->policy->create($user))->toBeFalse();
});

test('any role can view assignments', function () {
    foreach (['super_admin', 'admin', 'teacher', 'supervisor', 'student'] as $role) {
        $user = User::factory()->create();
        $user->assignRole($role);

        expect($this->policy->viewAny($user))->toBeTrue();
    }
});

test('admin can delete assignment without submissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $assignment = Assignment::factory()->create();

    expect($this->policy->delete($admin, $assignment))->toBeTrue();
});

test('teacher cannot delete assignment', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    $assignment = Assignment::factory()->create();

    expect($this->policy->delete($teacher, $assignment))->toBeFalse();
});
