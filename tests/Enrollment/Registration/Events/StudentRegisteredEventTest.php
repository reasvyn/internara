<?php

declare(strict_types=1);

use App\Enrollment\Registration\Actions\RegisterInternshipAction;
use App\Enrollment\Registration\Data\RegistrationData;
use App\Enrollment\Registration\Events\StudentRegistered;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

test('student registered event is dispatched via register action', function () {
    Event::fake([StudentRegistered::class]);

    $internship = Internship::factory()->create();
    $user = User::factory()->create();
    $data = new RegistrationData(internshipId: $internship->id);

    app(RegisterInternshipAction::class)->execute($user, $data);

    Event::assertDispatched(StudentRegistered::class);
});

test('student registered event contains registration', function () {
    $registration = Registration::factory()->create();

    $event = new StudentRegistered($registration);

    expect($event->registration->id)->toBe($registration->id);
    expect($event->eventName())->toBe('student.registered');
});
