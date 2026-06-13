<?php

declare(strict_types=1);

use App\SysAdmin\Observability\Recorders\RegistrationRecorder;
use App\SysAdmin\Observability\Recorders\SystemRecorder;

test('records registration and system snapshots', function () {
    $this->partialMock(RegistrationRecorder::class, function ($mock) {
        $mock->shouldReceive('recordSnapshot')->once();
    });
    $this->partialMock(SystemRecorder::class, function ($mock) {
        $mock->shouldReceive('recordSnapshot')->once();
    });

    $this->artisan('pulse:record-snapshots')
        ->assertExitCode(0);
});

test('displays started and completed messages', function () {
    $this->artisan('pulse:record-snapshots')
        ->expectsOutputToContain(__('sysadmin.pulse_record.started'))
        ->expectsOutputToContain(__('sysadmin.pulse_record.completed'));
});
