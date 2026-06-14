<?php

declare(strict_types=1);

use App\Academics\Department\Actions\DeleteDepartmentAction;
use App\Academics\Department\Models\Department;
use App\Core\Exceptions\RejectedException;
use App\User\Profile\Models\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes department without profiles', function () {
    $department = Department::factory()->create();
    $action = app(DeleteDepartmentAction::class);

    $action->execute($department);

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});

test('cannot delete department with profiles', function () {
    $department = Department::factory()->create();
    Profile::factory()->create(['department_id' => $department->id]);
    $action = app(DeleteDepartmentAction::class);

    expect(fn () => $action->execute($department))->toThrow(RejectedException::class);

    $this->assertModelExists($department);
});
