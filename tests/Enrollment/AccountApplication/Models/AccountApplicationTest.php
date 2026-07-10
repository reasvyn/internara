<?php

declare(strict_types=1);

use App\Academics\Department\Models\Department;
use App\Enrollment\AccountApplication\Enums\AccountApplicationStatus;
use App\Enrollment\AccountApplication\Models\AccountApplication;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('account application has fillable attributes', function () {
    $application = new AccountApplication;

    expect($application->getFillable())->toContain('name', 'email', 'student_id_number', 'department_id', 'form_data', 'status', 'processed_by', 'processed_at', 'rejection_reason');
});

test('account application casts status to enum', function () {
    $application = AccountApplication::factory()->create();

    expect($application->status)->toBeInstanceOf(AccountApplicationStatus::class);
});

test('account application casts form_data to array', function () {
    $application = AccountApplication::factory()->create();

    expect($application->form_data)->toBeArray();
});

test('account application defaults to pending status', function () {
    $application = AccountApplication::factory()->create();

    expect($application->status->value)->toBe('pending');
});

test('account application belongs to department', function () {
    $department = Department::factory()->create();
    $application = AccountApplication::factory()->create(['department_id' => $department->id]);

    expect($application->department)->toBeInstanceOf(Department::class);
    expect($application->department->id)->toBe($department->id);
});

test('account application belongs to processor', function () {
    $user = User::factory()->create();
    $application = AccountApplication::factory()->create(['processed_by' => $user->id]);

    expect($application->processor)->toBeInstanceOf(User::class);
    expect($application->processor->id)->toBe($user->id);
});

test('account application status transitions', function () {
    expect(AccountApplicationStatus::PENDING->isTerminal())->toBeFalse();
    expect(AccountApplicationStatus::APPROVED->isTerminal())->toBeTrue();
    expect(AccountApplicationStatus::REJECTED->isTerminal())->toBeTrue();
});
