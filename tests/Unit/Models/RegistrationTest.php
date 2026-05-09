<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\Internship;
use App\Models\Logbook;
use App\Models\Placement;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $registration = Registration::factory()->create();

    expect($registration)->toBeInstanceOf(Registration::class)
        ->and($registration->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $registration = Registration::factory()->create([
        'start_date' => '2025-09-01',
        'end_date' => '2026-06-30',
    ]);

    expect($registration->start_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($registration->start_date->format('Y-m-d'))->toBe('2025-09-01')
        ->and($registration->end_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($registration->end_date->format('Y-m-d'))->toBe('2026-06-30');
});

it('belongs to student', function () {
    $student = User::factory()->create();
    $registration = Registration::factory()->create(['student_id' => $student->id]);

    expect($registration->student)->toBeInstanceOf(User::class)
        ->and($registration->student->id)->toBe($student->id);
});

it('belongs to internship', function () {
    $internship = Internship::factory()->create();
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);

    expect($registration->internship)->toBeInstanceOf(Internship::class)
        ->and($registration->internship->id)->toBe($internship->id);
});

it('belongs to placement', function () {
    $placement = Placement::factory()->create();
    $registration = Registration::factory()->create(['placement_id' => $placement->id]);

    expect($registration->placement)->toBeInstanceOf(Placement::class)
        ->and($registration->placement->id)->toBe($placement->id);
});

it('belongs to teacher', function () {
    $teacher = User::factory()->create();
    $registration = Registration::factory()->create(['teacher_id' => $teacher->id]);

    expect($registration->teacher)->toBeInstanceOf(User::class)
        ->and($registration->teacher->id)->toBe($teacher->id);
});

it('has many journal entries', function () {
    $registration = Registration::factory()->create();
    Logbook::factory()->count(2)->create(['registration_id' => $registration->id]);

    expect($registration->journalEntries)->toHaveCount(2)
        ->and($registration->journalEntries->first())->toBeInstanceOf(Logbook::class);
});

it('has many attendance logs', function () {
    $registration = Registration::factory()->create();
    Attendance::factory()->count(2)->create(['registration_id' => $registration->id]);

    expect($registration->attendanceLogs)->toHaveCount(2)
        ->and($registration->attendanceLogs->first())->toBeInstanceOf(Attendance::class);
});

it('delegates status checks to entity', function () {
    $registration = Registration::factory()->create(['status' => 'active']);
    expect($registration->asRegistrationState()->isActive())->toBeTrue();

    $registration->update(['status' => 'pending']);
    expect($registration->asRegistrationState()->isPending())->toBeTrue();
});
