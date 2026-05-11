<?php

declare(strict_types=1);

use App\Actions\Attendance\UpdateAttendanceAction;
use App\Enums\Attendance\AttendanceStatus;
use Database\Factories\AttendanceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates attendance fields', function () {
        $attendance = AttendanceFactory::new()->create();

        $result = app(UpdateAttendanceAction::class)->execute($attendance, [
            'notes' => 'Updated notes',
            'status' => 'sick',
        ]);

        expect($result->notes)->toBe('Updated notes')
            ->and($result->status)->toBe(AttendanceStatus::SICK);
    });
});
