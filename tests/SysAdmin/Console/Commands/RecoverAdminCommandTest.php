<?php

declare(strict_types=1);

use App\Auth\SuperAdmin\Actions\RecoverSuperAdminAction;
use Tests\Support\WithSettingsSeed;
use App\User\Models\User;
use App\User\UserManagement\Actions\ReadRecoveryKeyAction;
use App\User\UserManagement\Actions\SaveRecoveryKeyAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);
uses(WithSettingsSeed::class);

beforeEach(function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole('super_admin');

    $this->seedSettings([
        'setup.install_recovery_key' => [
            'value' => Hash::make('valid-key'),
            'group' => 'setup',
            'type' => 'string',
        ],
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
        'setup.updated_at' => [
            'value' => now()->toIso8601String(),
            'group' => 'setup',
            'type' => 'datetime',
        ],
    ]);

    $this->mock(ReadRecoveryKeyAction::class)->shouldReceive('execute')->andReturn('valid-key');

    $this->mock(SaveRecoveryKeyAction::class)
        ->shouldReceive('execute')
        ->andReturn(storage_path('app/private/.recovery-key'));

    $this->mock(RecoverSuperAdminAction::class)->shouldReceive('execute')->andReturn($admin);
});

test('recovers admin with valid key and email', function () {
    $this->artisan('admin:recover', [
        'email' => 'admin@example.com',
        '--key' => 'valid-key',
    ])
        ->expectsQuestion(__('sysadmin.field_new_password'), 'password123')
        ->expectsQuestion(__('sysadmin.field_confirm_password'), 'password123')
        ->expectsQuestion(__('sysadmin.recover.confirm_prompt'), 'admin@example.com')
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
        ->expectsOutputToContain(
            __('sysadmin.recover.not_found', ['email' => 'nonexistent@example.com']),
        );
});
