<?php

declare(strict_types=1);

use App\Assignment\Submission\Models\Submission;
use App\Assignment\Submission\Policies\SubmissionPolicy;
use App\User\Models\User;

beforeEach(function () {
    $this->policy = app(SubmissionPolicy::class);
});

test('student can create submission', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    expect($this->policy->create($user))->toBeTrue();
});

test('teacher cannot create submission', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    expect($this->policy->create($user))->toBeFalse();
});

test('admin can view any submission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $submission = Submission::factory()->create();

    expect($this->policy->view($admin, $submission))->toBeTrue();
});

test('student can view own submission', function () {
    $student = User::factory()->create();
    $submission = Submission::factory()->create(['student_id' => $student->id]);

    expect($this->policy->view($student, $submission))->toBeTrue();
});

test('student cannot view others submission', function () {
    $student = User::factory()->create();
    $other = Submission::factory()->create();

    expect($this->policy->view($student, $other))->toBeFalse();
});

test('admin can verify submission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->verify($admin, Submission::factory()->create()))->toBeTrue();
});

test('student cannot verify submission', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    expect($this->policy->verify($student, Submission::factory()->create()))->toBeFalse();
});
