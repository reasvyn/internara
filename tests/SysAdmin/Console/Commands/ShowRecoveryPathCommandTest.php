<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

test('shows the recovery key file path', function () {
    Storage::fake('local');
    Storage::disk('local')->put('.recovery-key', 'test-key');

    $this->artisan('admin:recovery-path')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.recovery_path.info'));
});

test('shows missing status when file does not exist', function () {
    Storage::fake('local');

    $this->artisan('admin:recovery-path')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.recovery_path.missing'));
});
