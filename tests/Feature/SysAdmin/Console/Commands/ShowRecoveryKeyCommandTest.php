<?php

declare(strict_types=1);

use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;

uses(LazilyRefreshDatabase::class);

test('displays recovery key when confirmed', function () {
    File::shouldReceive('exists')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn(true);
    File::shouldReceive('get')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn("# INTERNARA RECOVERY KEY\n\nstored-key");

    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('sysadmin.recovery_show.confirm'), 'yes')
        ->assertExitCode(0)
        ->expectsOutputToContain('stored-key');
});

test('fails when recovery key file does not exist', function () {
    File::shouldReceive('exists')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn(false);

    $this->artisan('admin:recovery-show')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('sysadmin.recovery_path.missing'));
});

test('aborts when confirmation is declined', function () {
    Setting::factory()->create([
        'key' => 'setup.install_recovery_key',
        'value' => 'some-key',
        'type' => 'string',
        'group' => 'setup',
    ]);

    File::shouldReceive('exists')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn(true);
    File::shouldReceive('get')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn('stored-key');

    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('sysadmin.recovery_show.confirm'), 'no')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.recovery_show.aborted'));
});

test('fails when recovery key file is empty', function () {
    File::shouldReceive('exists')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn(true);
    File::shouldReceive('get')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn('');

    $this->artisan('admin:recovery-show')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('sysadmin.recovery_show.no_setup'));
});
