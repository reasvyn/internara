<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Actions\RegisterInternshipAction;
use App\Enrollment\Registration\Data\RegistrationData;
use App\Enrollment\Registration\Events\StudentRegistered;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

test('registers student for internship successfully', function () {
    $user = User::factory()->create();
    $internship = Internship::factory()->create();
    $data = new RegistrationData(
        internshipId: $internship->id,
        academicYear: '2025/2026',
        startDate: '2025-07-01',
        endDate: '2026-06-30',
        proposedCompanyName: 'Tech Corp',
        proposedCompanyAddress: '123 Main St',
    );

    $registration = app(RegisterInternshipAction::class)->execute($user, $data);

    expect($registration)->toBeInstanceOf(Registration::class);
    $this->assertDatabaseHas('registrations', ['id' => $registration->id]);
    expect($registration->status)->toBe('pending');
});

test('throws exception when student already has active or pending registration', function () {
    $user = User::factory()->create();
    $internship = Internship::factory()->create();
    $data = new RegistrationData(internshipId: $internship->id);

    app(RegisterInternshipAction::class)->execute($user, $data);

    expect(fn () => app(RegisterInternshipAction::class)->execute($user, $data))
        ->toThrow(RejectedException::class);
});

test('dispatches student registered event', function () {
    $user = User::factory()->create();
    $internship = Internship::factory()->create();

    Event::fake([StudentRegistered::class]);

    $data = new RegistrationData(internshipId: $internship->id);
    app(RegisterInternshipAction::class)->execute($user, $data);

    Event::assertDispatched(StudentRegistered::class);
});
