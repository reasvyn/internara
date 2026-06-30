<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\SetupWizard\Actions;

use App\Academics\Department\Models\Department;
use App\Setup\SetupWizard\Actions\SetupDepartmentAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('setup department action successfully creates department', function () {
    $action = new SetupDepartmentAction;

    $data = [
        'name' => 'Software Engineering',
        'description' => 'Coding and systems development',
    ];

    $department = $action->execute($data);

    expect($department)->toBeInstanceOf(Department::class);
    expect($department->name)->toBe('Software Engineering');
    expect(Department::count())->toBe(1);
});

test('setup department action throws validation exception on empty name', function () {
    $action = new SetupDepartmentAction;

    expect(fn () => $action->execute(['name' => '']))->toThrow(ValidationException::class);
});
