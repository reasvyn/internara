<?php

declare(strict_types=1);

use App\Actions\School\CreateDepartmentAction;
use App\Models\Department;
use Database\Factories\SchoolFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a department', function () {
        $school = SchoolFactory::new()->create();

        $department = app(CreateDepartmentAction::class)->execute([
            'name' => 'Mathematics',
            'school_id' => $school->id,
        ]);

        expect($department)->toBeInstanceOf(Department::class)
            ->and($department->name)->toBe('Mathematics');
    });
});
