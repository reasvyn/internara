<?php

declare(strict_types=1);

use App\Journals\Attendance\Actions\DeleteAttendanceAction;
use App\Journals\Attendance\Models\Attendance;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes attendance log', function () {
    $log = Attendance::factory()->create();

    app(DeleteAttendanceAction::class)->execute($log);

    $this->assertModelMissing($log);
});

test('deletes attendance log removes from database', function () {
    $log = Attendance::factory()->create();

    app(DeleteAttendanceAction::class)->execute($log);

    $this->assertDatabaseMissing('attendances', ['id' => $log->id]);
});
