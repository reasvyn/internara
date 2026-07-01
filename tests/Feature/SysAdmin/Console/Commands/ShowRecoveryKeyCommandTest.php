<?php

declare(strict_types=1);

use App\User\UserManagement\Actions\ReadRecoveryKeyAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->keyPath = storage_path('app/private/.recovery-key');
    $this->keyDir = dirname($this->keyPath);

    if (! is_dir($this->keyDir)) {
        mkdir($this->keyDir, 0755, true);
    }
});

afterEach(function () {
    if (file_exists($this->keyPath)) {
        unlink($this->keyPath);
    }
});

test('displays recovery key when confirmed', function () {
    file_put_contents($this->keyPath, "# INTERNARA RECOVERY KEY\n\nstored-key");

    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('sysadmin.recovery_show.confirm'), 'yes')
        ->assertExitCode(0)
        ->expectsOutputToContain('stored-key');
});

test('fails when recovery key file does not exist', function () {
    $this->artisan('admin:recovery-show')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('sysadmin.recovery_path.missing'));
});

test('aborts when confirmation is declined', function () {
    file_put_contents($this->keyPath, 'stored-key');

    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('sysadmin.recovery_show.confirm'), 'no')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.recovery_show.aborted'));
});

test('fails when recovery key file is empty', function () {
    file_put_contents($this->keyPath, '');

    $this->artisan('admin:recovery-show')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('sysadmin.recovery_show.no_setup'));
});
