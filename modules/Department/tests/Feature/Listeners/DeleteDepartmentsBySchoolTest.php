<?php

declare(strict_types=1);

namespace Modules\Department\Tests\Feature\Listeners;


use Illuminate\Support\Facades\Event;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\School\Events\SchoolDeleted;
use Modules\School\Services\Contracts\SchoolService;
use Modules\User\Models\User;

test('it deletes all departments when a school is deleted', function () {
    // Arrange
    \Modules\Permission\Models\Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $this->actingAs($admin);

    $schoolService = app(SchoolService::class);
    $departmentService = app(DepartmentService::class);

    $school = $schoolService->create(['name' => 'SMK Cleanup Test', 'npsn' => '12345678']);

    // Create departments for this school
    $dept1 = $departmentService->create(['name' => 'RPL', 'school_id' => $school->id]);
    $dept2 = $departmentService->create(['name' => 'TKJ', 'school_id' => $school->id]);

    // Act
    $schoolService->delete($school->id);

    // Assert (Manual check since we are not running it)
    // 1. SchoolDeleted event should have been fired
    // 2. Departments with school_id should be missing from DB
    $this->assertDatabaseMissing('departments', ['id' => $dept1->id]);
    $this->assertDatabaseMissing('departments', ['id' => $dept2->id]);
});

test('it is registered in the EventServiceProvider', function () {
    Event::fake();
    Event::assertListening(
        SchoolDeleted::class,
        \Modules\Department\Listeners\DeleteDepartmentsBySchool::class,
    );
});
