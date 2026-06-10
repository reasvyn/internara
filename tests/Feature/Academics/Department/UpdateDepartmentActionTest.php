<?php

declare(strict_types=1);

use App\Academics\Department\Actions\UpdateDepartmentAction;
use App\Academics\Department\Models\Department;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('updates department name', function () {
    $department = Department::factory()->create(['name' => 'Old Name']);
    $action = app(UpdateDepartmentAction::class);

    $action->execute($department, ['name' => 'New Name']);

    expect($department->fresh()->name)->toBe('New Name');
});

test('updates department description', function () {
    $department = Department::factory()->create();
    $action = app(UpdateDepartmentAction::class);

    $action->execute($department, ['name' => $department->name, 'description' => 'Updated description']);

    expect($department->fresh()->description)->toBe('Updated description');
});

test('rejects duplicate name on update', function () {
    Department::factory()->create(['name' => 'Existing']);
    $department = Department::factory()->create(['name' => 'Original']);
    $action = app(UpdateDepartmentAction::class);

    expect(fn () => $action->execute($department, ['name' => 'Existing']))
        ->toThrow(ValidationException::class);
});

test('allows same name on update', function () {
    $department = Department::factory()->create(['name' => 'Same Name']);
    $action = app(UpdateDepartmentAction::class);

    $action->execute($department, ['name' => 'Same Name']);

    expect($department->fresh()->name)->toBe('Same Name');
});
