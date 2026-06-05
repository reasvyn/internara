<?php

declare(strict_types=1);

use App\Domain\User\Aggregates\Dashboard\Livewire\StudentDashboard;
use App\Domain\User\Aggregates\Dashboard\Livewire\SupervisorDashboard;
use App\Domain\User\Aggregates\Dashboard\Livewire\TeacherDashboard;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('only student can access student dashboard', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    // Access dashboard as student
    Livewire::actingAs($student)
        ->test(StudentDashboard::class)
        ->assertStatus(200);

    // Access dashboard as non-student returns 403 / forbidden
    Livewire::actingAs($teacher)
        ->test(StudentDashboard::class)
        ->assertStatus(403);
});

test('only teacher can access teacher dashboard', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $student = User::factory()->create();
    $student->assignRole('student');

    Livewire::actingAs($teacher)
        ->test(TeacherDashboard::class)
        ->assertStatus(200);

    Livewire::actingAs($student)
        ->test(TeacherDashboard::class)
        ->assertStatus(403);
});

test('only supervisor can access supervisor dashboard', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');

    $student = User::factory()->create();
    $student->assignRole('student');

    Livewire::actingAs($supervisor)
        ->test(SupervisorDashboard::class)
        ->assertStatus(200);

    Livewire::actingAs($student)
        ->test(SupervisorDashboard::class)
        ->assertStatus(403);
});

test('dashboards verify properties set on mount', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    Livewire::actingAs($student)
        ->test(StudentDashboard::class)
        ->assertSet('attendancePercent', 100.0)
        ->assertSet('assignmentSubmittedCount', 0)
        ->assertSet('assignmentTotalCount', 0)
        ->assertSet('handbookReadCount', 0)
        ->assertSet('handbookTotalCount', 0);

    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    Livewire::actingAs($teacher)
        ->test(TeacherDashboard::class)
        ->assertSet('ungradedSubmissions', 0)
        ->assertSet('supervisionLogsCount', 0)
        ->assertSet('unresolvedIncidents', 0);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');

    Livewire::actingAs($supervisor)
        ->test(SupervisorDashboard::class)
        ->assertSet('pendingJournals', 0)
        ->assertSet('pendingAttendance', 0);
});
