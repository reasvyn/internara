<?php

declare(strict_types=1);

use Modules\Department\Models\Department;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Profile\Models\Profile;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a profile can belong to a department', function () {
    $department = app(DepartmentService::class)->factory()->create();

    $profile = Profile::factory()->create([
        'department_id' => $department->id,
    ]);

    expect($profile->department)
        ->toBeInstanceOf(Department::class)
        ->and($profile->department->id)
        ->toBe($department->id);
});

test('a profile department relation returns null if no department is assigned', function () {
    $profile = Profile::factory()->create([
        'department_id' => null,
    ]);

    expect($profile->department)->toBeNull();
});
