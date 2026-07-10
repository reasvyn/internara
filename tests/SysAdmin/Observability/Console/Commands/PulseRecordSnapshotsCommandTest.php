<?php

declare(strict_types=1);

use App\SysAdmin\Observability\Recorders\RegistrationRecorder;
use App\SysAdmin\Observability\Recorders\SystemRecorder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('records registration and system snapshots', function () {
    RegistrationRecorder::recordSnapshot();
    SystemRecorder::recordSnapshot();

    $this->artisan('pulse:record-snapshots')
        ->assertExitCode(0);
});

test('displays started and completed messages', function () {
    $this->artisan('pulse:record-snapshots')
        ->expectsOutputToContain(__('sysadmin.pulse_record.started'))
        ->expectsOutputToContain(__('sysadmin.pulse_record.completed'));
});
