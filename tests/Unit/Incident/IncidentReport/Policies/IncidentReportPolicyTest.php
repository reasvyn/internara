<?php

declare(strict_types=1);

use App\Incident\IncidentReport\Models\IncidentReport;
use App\Incident\IncidentReport\Policies\IncidentReportPolicy;
use App\User\Models\User;

beforeEach(function () {
    $this->policy = app(IncidentReportPolicy::class);
});

test('anyone can create incident report', function () {
    $user = User::factory()->create();

    expect($this->policy->create($user))->toBeTrue();
});

test('admin can view any report', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->viewAny($admin))->toBeTrue();
});

test('teacher can view any report', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    expect($this->policy->viewAny($teacher))->toBeTrue();
});

test('student cannot view any report', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    expect($this->policy->viewAny($student))->toBeFalse();
});

test('reporter can view their own report', function () {
    $reporter = User::factory()->create();
    $report = IncidentReport::factory()->create(['reported_by' => $reporter->id]);

    expect($this->policy->view($reporter, $report))->toBeTrue();
});

test('admin can update incident', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->update($admin, IncidentReport::factory()->create()))->toBeTrue();
});

test('admin can delete incident', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect($this->policy->delete($admin, IncidentReport::factory()->create()))->toBeTrue();
});
