<?php

declare(strict_types=1);

use App\Auth\SuperAdmin\Actions\RecoverSuperAdminAction;
use App\Setup\Entities\SetupEntity;
use App\User\Models\User;
use App\User\UserManagement\Actions\ReadRecoveryKeyAction;
use App\User\UserManagement\Actions\SaveRecoveryKeyAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('super_admin');

    $entity = Mockery::mock(SetupEntity::class);
    $entity->shouldReceive('recoveryKey')->andReturn(Hash::make('valid-key'));
    SetupEntity::shouldReceive('get')->andReturn($entity);
    SetupEntity::shouldReceive('update')->andReturnTrue();

    $this->mock(ReadRecoveryKeyAction::class)
        ->shouldReceive('execute')
        ->andReturn('valid-key');

    $this->mock(SaveRecoveryKeyAction::class)
        ->shouldReceive('execute')
        ->andReturn(storage_path('app/private/.recovery-key'));

    $this->mock(RecoverSuperAdminAction::class)
        ->shouldReceive('execute')
        ->andReturn($admin);
});

test('recovers admin with valid key and email', function () {
    $this->artisan('admin:recover', [
        'email' => 'admin@example.com',
        '--key' => 'valid-key',
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain(__('sysadmin.recover.success_reset'));
});

test('fails with invalid recovery key', function () {
    $this->artisan('admin:recover', [
        'email' => 'admin@example.com',
        '--key' => 'invalid-key',
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain(__('sysadmin.recover.key_invalid'));
});

test('fails when user email does not exist', function () {
    $this->artisan('admin:recover', [
        'email' => 'nonexistent@example.com',
        '--key' => 'valid-key',
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain(__('sysadmin.recover.not_found', ['email' => 'nonexistent@example.com']));
});
