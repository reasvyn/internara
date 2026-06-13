<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('shows the recovery key file path', function () {
    File::shouldReceive('exists')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn(true);

    $this->artisan('admin:recovery-path')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.recovery_path.info'));
});

test('shows missing status when file does not exist', function () {
    File::shouldReceive('exists')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn(false);

    $this->artisan('admin:recovery-path')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.recovery_path.missing'));
});
