<?php

declare(strict_types=1);

use App\Actions\Attendance\VerifyAttendanceAction;
use Database\Factories\AttendanceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('verifies an attendance record', function () {
        $attendance = AttendanceFactory::new()->create(['is_verified' => false]);

        $result = app(VerifyAttendanceAction::class)->execute($attendance);

        expect($result->is_verified)->toBeTrue()
            ->and($result->verified_at)->not->toBeNull();
    });
});
