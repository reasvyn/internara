<?php

declare(strict_types=1);

use App\Actions\Attendance\CreateAttendanceAction;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\Attendance;
use Database\Factories\RegistrationFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a manual attendance record', function () {
        $user = UserFactory::new()->create();
        $registration = RegistrationFactory::new()->create();

        $result = app(CreateAttendanceAction::class)->execute($user, [
            'registration_id' => $registration->id,
            'date' => '2026-05-01',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'status' => 'present',
            'notes' => 'On time',
        ]);

        expect($result)->toBeInstanceOf(Attendance::class)
            ->and($result->user_id)->toBe($user->id)
            ->and($result->registration_id)->toBe($registration->id)
            ->and($result->status)->toBe(AttendanceStatus::PRESENT)
            ->and($result->notes)->toBe('On time');
    });

    it('creates record with minimal required data', function () {
        $user = UserFactory::new()->create();
        $registration = RegistrationFactory::new()->create();

        $result = app(CreateAttendanceAction::class)->execute($user, [
            'registration_id' => $registration->id,
            'date' => '2026-05-01',
        ]);

        expect($result->status)->toBe(AttendanceStatus::PRESENT);
    });
});
