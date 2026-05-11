<?php

declare(strict_types=1);

use App\Actions\Attendance\ClockOutAction;
use Database\Factories\AttendanceFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('clocks out a user who has clocked in', function () {
        $user = UserFactory::new()->create();
        $attendance = AttendanceFactory::new()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_out' => null,
        ]);

        $result = app(ClockOutAction::class)->execute($user, []);

        expect($result->clock_out)->not->toBeNull();
    });

    it('throws if no clock-in record for today', function () {
        $user = UserFactory::new()->create();

        expect(fn () => app(ClockOutAction::class)->execute($user, []))
            ->toThrow(RuntimeException::class, 'You must clock in first');
    });

    it('throws if already clocked out', function () {
        $user = UserFactory::new()->create();
        AttendanceFactory::new()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_out' => now(),
        ]);

        expect(fn () => app(ClockOutAction::class)->execute($user, []))
            ->toThrow(RuntimeException::class, 'Already clocked out for today');
    });
});
