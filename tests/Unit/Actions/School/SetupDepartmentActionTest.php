<?php

declare(strict_types=1);

use App\Actions\School\SetupDepartmentAction;
use App\Models\Department;
use Database\Factories\SchoolFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a department attached to a school', function () {
        $school = SchoolFactory::new()->create();

        $department = app(SetupDepartmentAction::class)->execute($school->id, [
            'name' => 'Computer Science',
        ]);

        expect($department)->toBeInstanceOf(Department::class)
            ->and($department->name)->toBe('Computer Science')
            ->and($department->school_id)->toBe($school->id);
    });
});
