<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\AbsenceRequest\Actions\SubmitAbsenceAction;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('submits absence request with valid data', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $attendance = app(SubmitAbsenceAction::class)->execute(
        $user,
        $registration->id,
        [
            'start_date' => now()->addDay()->toDateString(),
            'reason_type' => 'sick',
            'reason_description' => 'Feeling unwell.',
        ],
    );

    expect($attendance)->toBeInstanceOf(Attendance::class);
    $this->assertModelExists($attendance);
    expect($attendance->user_id)->toBe($user->id);
    expect($attendance->registration_id)->toBe($registration->id);
    expect($attendance->absence_type)->toBe('sick');
    expect($attendance->absence_reason)->toBe('Feeling unwell.');
    expect($attendance->absence_status)->toBe(AbsenceRequestStatus::PENDING->value);
});

test('submits absence request without description', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $attendance = app(SubmitAbsenceAction::class)->execute(
        $user,
        $registration->id,
        [
            'reason_type' => 'other',
        ],
    );

    expect($attendance->absence_reason)->toBeNull();
});

test('submits absence request with other reason type', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $attendance = app(SubmitAbsenceAction::class)->execute(
        $user,
        $registration->id,
        [
            'reason_type' => 'other',
            'reason_description' => 'Personal matter.',
        ],
    );

    expect($attendance->absence_type)->toBe('other');
});
