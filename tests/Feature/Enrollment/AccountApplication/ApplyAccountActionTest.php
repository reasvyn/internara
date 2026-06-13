<?php

declare(strict_types=1);

use App\Academics\Department\Models\Department;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\AccountApplication\Actions\ApplyAccountAction;
use App\Enrollment\AccountApplication\Models\AccountApplication;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates account application with valid data', function () {
    $department = Department::factory()->create();

    $application = app(ApplyAccountAction::class)->execute([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'student_id_number' => 'NISN-12345678',
        'department_id' => $department->id,
        'form_data' => [
            'phone' => '08123456789',
            'address' => 'Jl. Merdeka No. 1',
        ],
    ]);

    expect($application)->toBeInstanceOf(AccountApplication::class);
    $this->assertDatabaseHas('account_applications', ['id' => $application->id]);
    expect($application->status->value)->toBe('pending');
});

test('throws exception when application with same email already exists and is pending', function () {
    $department = Department::factory()->create();
    AccountApplication::factory()->create([
        'email' => 'duplicate@example.com',
        'status' => 'pending',
    ]);

    expect(fn () => app(ApplyAccountAction::class)->execute([
        'name' => 'Jane Doe',
        'email' => 'duplicate@example.com',
        'student_id_number' => 'NISN-87654321',
        'department_id' => $department->id,
        'form_data' => ['phone' => '08123456789'],
    ]))->toThrow(RejectedException::class);
});

test('throws exception when application with same email is already approved', function () {
    $department = Department::factory()->create();
    AccountApplication::factory()->approved()->create([
        'email' => 'approved@example.com',
    ]);

    expect(fn () => app(ApplyAccountAction::class)->execute([
        'name' => 'Jane Doe',
        'email' => 'approved@example.com',
        'student_id_number' => 'NISN-87654321',
        'department_id' => $department->id,
        'form_data' => ['phone' => '08123456789'],
    ]))->toThrow(RejectedException::class);
});

test('allows new application when previous was rejected', function () {
    $department = Department::factory()->create();
    AccountApplication::factory()->create([
        'email' => 'rejected@example.com',
        'status' => 'rejected',
    ]);

    $application = app(ApplyAccountAction::class)->execute([
        'name' => 'Jane Doe',
        'email' => 'rejected@example.com',
        'student_id_number' => 'NISN-87654321',
        'department_id' => $department->id,
        'form_data' => ['phone' => '08123456789'],
    ]);

    expect($application)->toBeInstanceOf(AccountApplication::class);
});
