<?php

declare(strict_types=1);

use App\Academics\Department\Actions\CreateDepartmentAction;
use App\Academics\Department\Models\Department;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('creates department with valid data', function () {
    $action = app(CreateDepartmentAction::class);

    $department = $action->execute([
        'name' => 'Rekayasa Perangkat Lunak',
    ]);

    expect($department)->toBeInstanceOf(Department::class);
    $this->assertModelExists($department);
    expect($department->name)->toBe('Rekayasa Perangkat Lunak');
});

test('creates department with description', function () {
    $action = app(CreateDepartmentAction::class);

    $department = $action->execute([
        'name' => 'Teknik Komputer dan Jaringan',
        'description' => 'Network engineering program',
    ]);

    expect($department->description)->toBe('Network engineering program');
});

test('rejects duplicate department name', function () {
    Department::factory()->create(['name' => 'RPL']);
    $action = app(CreateDepartmentAction::class);

    expect(fn () => $action->execute(['name' => 'RPL']))
        ->toThrow(ValidationException::class);
});

test('rejects empty name', function () {
    $action = app(CreateDepartmentAction::class);

    expect(fn () => $action->execute(['name' => '']))
        ->toThrow(ValidationException::class);
});
