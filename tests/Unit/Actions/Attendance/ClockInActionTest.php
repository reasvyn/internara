<?php

declare(strict_types=1);

use App\Actions\Attendance\ClockInAction;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\Attendance;
use Database\Factories\AttendanceFactory;
use Database\Factories\MenteeFactory;
use Database\Factories\RegistrationFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('clocks in a user with active registration', function () {
        $user = UserFactory::new()->create();
        $mentee = MenteeFactory::new()->create(['user_id' => $user->id]);

        $registration = RegistrationFactory::new()->create([
            'mentee_id' => $mentee->id,
        ]);
        $registration->setStatus('active');

        $result = app(ClockInAction::class)->execute($user, []);

        expect($result)->toBeInstanceOf(Attendance::class)
            ->and($result->user_id)->toBe($user->id)
            ->and($result->status)->toBe(AttendanceStatus::PRESENT)
            ->and($result->clock_in)->not->toBeNull();
    });

    it('throws if already clocked in today', function () {
        $user = UserFactory::new()->create();
        $mentee = MenteeFactory::new()->create(['user_id' => $user->id]);

        $registration = RegistrationFactory::new()->create([
            'mentee_id' => $mentee->id,
        ]);
        $registration->setStatus('active');

        AttendanceFactory::new()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);

        expect(fn () => app(ClockInAction::class)->execute($user, []))
            ->toThrow(RuntimeException::class, 'Already clocked in for today');
    });

    it('stores location data when provided', function () {
        $user = UserFactory::new()->create();
        $mentee = MenteeFactory::new()->create(['user_id' => $user->id]);

        $registration = RegistrationFactory::new()->create([
            'mentee_id' => $mentee->id,
        ]);
        $registration->setStatus('active');

        $result = app(ClockInAction::class)->execute($user, [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        expect($result->clock_in_latitude)->toBe(-6.2088)
            ->and($result->clock_in_longitude)->toBe(106.8456);
    });

    it('throws if no active registration found', function () {
        $user = UserFactory::new()->create();

        expect(fn () => app(ClockInAction::class)->execute($user, []))
            ->toThrow(RuntimeException::class, 'No active internship registration found');
    });
});
