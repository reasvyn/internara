<?php

declare(strict_types=1);

use App\Setup\Entities\SetupEntity;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::shouldReceive('exists')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn(true);
});

test('displays recovery key when confirmed', function () {
    $recoveryKey = 'test-recovery-key-value';
    $entity = Mockery::mock(SetupEntity::class);
    $entity->shouldReceive('recoveryKey')->andReturn($recoveryKey);
    SetupEntity::shouldReceive('get')->andReturn($entity);

    File::shouldReceive('get')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn('stored-key');

    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('sysadmin.recovery_show.confirm'), 'yes')
        ->assertExitCode(0)
        ->expectsOutputToContain($recoveryKey);
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
    $entity = Mockery::mock(SetupEntity::class);
    $entity->shouldReceive('recoveryKey')->andReturn('some-key');
    SetupEntity::shouldReceive('get')->andReturn($entity);

    File::shouldReceive('get')
        ->with(storage_path('app/private/.recovery-key'))
        ->andReturn('stored-key');

    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('sysadmin.recovery_show.confirm'), 'no')
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.recovery_show.aborted'));
});

test('fails when setup has no recovery key', function () {
    $entity = Mockery::mock(SetupEntity::class);
    $entity->shouldReceive('recoveryKey')->andReturnNull();
    SetupEntity::shouldReceive('get')->andReturn($entity);

    $this->artisan('admin:recovery-show')
        ->assertExitCode(1)
        ->expectsOutputToContain(__('sysadmin.recovery_show.no_setup'));
});
