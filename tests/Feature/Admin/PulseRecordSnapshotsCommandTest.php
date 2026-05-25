<?php

declare(strict_types=1);

use App\Domain\Admin\Recorders\RegistrationRecorder;
use App\Domain\Admin\Recorders\SystemRecorder;

describe('PulseRecordSnapshotsCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('pulse:record-snapshots');
    });

    it('runs successfully', function () {
        $this->artisan('pulse:record-snapshots')
            ->assertExitCode(0);
    });
});
