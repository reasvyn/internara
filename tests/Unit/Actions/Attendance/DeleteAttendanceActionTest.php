<?php

declare(strict_types=1);

use App\Actions\Attendance\DeleteAttendanceAction;
use App\Models\Attendance;
use Database\Factories\AttendanceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes an attendance record', function () {
        $attendance = AttendanceFactory::new()->create();
        $id = $attendance->id;

        app(DeleteAttendanceAction::class)->execute($attendance);

        expect(Attendance::find($id))->toBeNull();
    });
});
