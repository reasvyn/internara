<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\Attendance\Livewire\StudentClockIn;
use App\Journals\Attendance\Models\Attendance;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

test('1KSWL-FR-AT1: clock-in page renders clock-in time without format() error', function () {
    Carbon::setTestNow('2026-08-17 09:30:00');

    $student = User::factory()->create();
    $student->assignRole('student');

    $internship = Internship::factory()->create(['status' => 'published']);
    $registration = Registration::factory()->active()->create([
        'student_id' => $student->id,
        'internship_id' => $internship->id,
    ]);

    Attendance::create([
        'user_id' => $student->id,
        'registration_id' => $registration->id,
        'date' => '2026-08-17',
        'clock_in' => '09:15:00',
        'status' => 'present',
    ]);

    test()->actingAs($student);

    $html = Livewire::test(StudentClockIn::class)
        ->html();

    expect($html)->toContain('09:15');
    expect($html)->not->toContain('Call to a member function format() on string');
});
