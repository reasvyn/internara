<?php

declare(strict_types=1);

use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\SupervisorService;
use Modules\User\Models\User;

beforeEach(function () {
    // Seed permissions and roles
    $this->seed(\Modules\Permission\Database\Seeders\PermissionDatabaseSeeder::class);

    $this->service = app(SupervisorService::class);

    // Setup basic data
    $this->internship = Internship::factory()->create();
    $this->placement = InternshipPlacement::factory()->create([
        'internship_id' => $this->internship->id,
    ]);
    $this->student = User::factory()->create();
    $this->student->assignRole('student');

    $this->registration = InternshipRegistration::create([
        'internship_id' => $this->internship->id,
        'placement_id' => $this->placement->id,
        'student_id' => $this->student->id,
    ]);
});

test('it can assign a teacher to a registration', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $result = $this->service->assignTeacher($this->registration->id, $teacher->id);

    expect($result)->toBeTrue();
    expect($this->registration->fresh()->teacher_id)->toBe($teacher->id);
});

test('it can assign a mentor to a registration', function () {
    $mentor = User::factory()->create();
    $mentor->assignRole('mentor');

    $result = $this->service->assignMentor($this->registration->id, $mentor->id);

    expect($result)->toBeTrue();
    expect($this->registration->fresh()->mentor_id)->toBe($mentor->id);
});

test('it prevents assigning a user without teacher role as a teacher', function () {
    $notTeacher = User::factory()->create();
    $notTeacher->assignRole('student');

    $result = $this->service->assignTeacher($this->registration->id, $notTeacher->id);

    expect($result)->toBeFalse();
    expect($this->registration->fresh()->teacher_id)->toBeNull();
});

test('it prevents assigning a user without mentor role as a mentor', function () {
    $notMentor = User::factory()->create();
    $notMentor->assignRole('teacher');

    $result = $this->service->assignMentor($this->registration->id, $notMentor->id);

    expect($result)->toBeFalse();
    expect($this->registration->fresh()->mentor_id)->toBeNull();
});
