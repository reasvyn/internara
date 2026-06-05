<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Academics\Department\Models\Department;
use App\Academics\School\Models\School;
use App\Setup\Actions\SetupDepartmentAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('setup department action successfully creates department', function () {
    $school = School::create([
        'name' => 'SMK 1 Test',
        'institutional_code' => 'SCH-TEST',
        'email' => 'smktest@sch.id',
    ]);

    $action = new SetupDepartmentAction;

    $data = [
        'name' => 'Software Engineering',
        'description' => 'Coding and systems development',
    ];

    $department = $action->execute($school->id, $data);

    expect($department)->toBeInstanceOf(Department::class);
    expect($department->name)->toBe('Software Engineering');
    expect($department->school_id)->toBe($school->id);
    expect(Department::count())->toBe(1);
});

test('setup department action throws validation exception on empty name', function () {
    $action = new SetupDepartmentAction;

    expect(fn () => $action->execute((string) Str::uuid(), ['name' => '']))->toThrow(ValidationException::class);
});
