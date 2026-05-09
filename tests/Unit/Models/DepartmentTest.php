<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Profile;
use App\Models\School;
use Database\Factories\DepartmentFactory;
use Database\Factories\ProfileFactory;
use Database\Factories\SchoolFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $department = DepartmentFactory::new()->create();

    expect($department)->toBeInstanceOf(Department::class)
        ->and($department->id)->toBeUuid();
});

it('belongs to school', function () {
    $school = SchoolFactory::new()->create();
    $department = DepartmentFactory::new()->create(['school_id' => $school->id]);

    expect($department->school)->toBeInstanceOf(School::class)
        ->and($department->school->id)->toBe($school->id);
});

it('has many profiles', function () {
    $department = DepartmentFactory::new()->create();
    ProfileFactory::new()->count(2)->create(['department_id' => $department->id]);

    expect($department->profiles)->toHaveCount(2)
        ->and($department->profiles->first())->toBeInstanceOf(Profile::class);
});
