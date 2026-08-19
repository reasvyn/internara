<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Attendance\Livewire\AttendanceManager;
use App\Journals\Attendance\Models\Attendance;
use App\Program\Internship\Models\Internship;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('1KSWL-FR-AT10: attendance manager scopes records to the user supervised registrations', function () {
    $teacher = User::factory()->create();

    $internship = Internship::factory()->create(['status' => 'published']);

    $studentA = User::factory()->create();
    $registrationA = Registration::factory()->active()->create([
        'student_id' => $studentA->id,
        'internship_id' => $internship->id,
    ]);

    $studentB = User::factory()->create();
    $registrationB = Registration::factory()->active()->create([
        'student_id' => $studentB->id,
        'internship_id' => $internship->id,
    ]);

    InternshipGroupMember::factory()->create([
        'registration_id' => $registrationA->id,
        'user_id' => $teacher->id,
    ]);

    Attendance::create([
        'user_id' => $studentA->id,
        'registration_id' => $registrationA->id,
        'date' => '2026-08-19',
        'status' => 'present',
    ]);
    Attendance::create([
        'user_id' => $studentB->id,
        'registration_id' => $registrationB->id,
        'date' => '2026-08-19',
        'status' => 'present',
    ]);

    $this->actingAs($teacher);

    Livewire::test(AttendanceManager::class)
        ->assertViewHas('existing', function (Collection $existing) use ($registrationA, $registrationB) {
            return $existing->has($registrationA->id) && ! $existing->has($registrationB->id);
        });
});
