<?php

declare(strict_types=1);

use App\Enums\Auth\AccountStatus;
use App\Models\Setup;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);

    Setup::factory()->installed()->withRecoveryKey()->create();
});

afterEach(function () {
    if (File::exists(base_path('.installed'))) {
        File::delete(base_path('.installed'));
    }
});

it('fails when system is not installed', function () {
    File::delete(base_path('.installed'));
    Setup::query()->delete();

    artisan('setup:recover-admin', ['email' => 'admin@test.com'])
        ->assertFailed();
});

it('fails when recovery key is missing', function () {
    artisan('setup:recover-admin', [
        'email' => 'admin@test.com',
    ])->assertFailed();
});

it('fails when recovery key is invalid', function () {
    artisan('setup:recover-admin', [
        'email' => 'admin@test.com',
        '--key' => 'wrong-key',
    ])->assertFailed();
});

it('fails when creating admin but the email already exists', function () {
    User::factory()->create(['email' => 'existing@internara.test']);

    artisan('setup:recover-admin', [
        'email' => 'existing@internara.test',
        '--key' => 'admin-recovery-key-2026',
    ])->assertFailed();
});

it('fails when resetting a non-existent user', function () {
    artisan('setup:recover-admin', [
        'email' => 'ghost@internara.test',
        '--reset' => true,
        '--key' => 'admin-recovery-key-2026',
    ])->assertFailed();
});

it('creates a new admin user via CLI', function () {
    $email = 'newadmin@internara.test';

    artisan('setup:recover-admin', [
        'email' => $email,
        '--key' => 'admin-recovery-key-2026',
    ])
        ->expectsQuestion(__('setup.cli.admin.password'), 'password123')
        ->expectsQuestion(__('setup.cli.admin.confirm_password'), 'password123')
        ->expectsQuestion(__('setup.cli.recovery_confirmation_prompt'), $email)
        ->assertSuccessful();

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Recovery Admin');
    expect($user->username)->toStartWith('admin_');
    expect($user->hasRole('super_admin'))->toBeTrue();
    expect($user->latestStatus()->name)->toBe(AccountStatus::PROTECTED->value);
});

it('resets an existing admin user via CLI', function () {
    $existing = User::factory()
        ->withPassword('old-password')
        ->create(['locked_at' => now(), 'locked_reason' => 'manual_lock']);
    $existing->setStatus(AccountStatus::SUSPENDED);

    artisan('setup:recover-admin', [
        'email' => $existing->email,
        '--reset' => true,
        '--key' => 'admin-recovery-key-2026',
    ])
        ->expectsQuestion(__('setup.cli.admin.new_password'), 'new-password')
        ->expectsQuestion(__('setup.cli.admin.confirm_password'), 'new-password')
        ->expectsQuestion(__('setup.cli.recovery_confirmation_prompt'), $existing->email)
        ->assertSuccessful();

    $user = $existing->fresh();
    expect($user->locked_at)->toBeNull();
    expect($user->locked_reason)->toBeNull();
    expect($user->hasRole('super_admin'))->toBeTrue();
    expect($user->latestStatus()->name)->toBe(AccountStatus::VERIFIED->value);
});

it('creates admin with custom role via CLI', function () {
    $email = 'customrole@internara.test';

    artisan('setup:recover-admin', [
        'email' => $email,
        '--role' => 'admin',
        '--key' => 'admin-recovery-key-2026',
    ])
        ->expectsQuestion(__('setup.cli.admin.password'), 'password123')
        ->expectsQuestion(__('setup.cli.admin.confirm_password'), 'password123')
        ->expectsQuestion(__('setup.cli.recovery_confirmation_prompt'), $email)
        ->assertSuccessful();

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('super_admin'))->toBeFalse();
    expect($user->hasRole('admin'))->toBeTrue();
});

it('fails when passwords do not match', function () {
    artisan('setup:recover-admin', [
        'email' => 'mismatch@internara.test',
        '--key' => 'admin-recovery-key-2026',
    ])
        ->expectsQuestion(__('setup.cli.admin.password'), 'password123')
        ->expectsQuestion(__('setup.cli.admin.confirm_password'), 'different-password')
        ->assertFailed();
});

it('fails when confirmation email does not match', function () {
    artisan('setup:recover-admin', [
        'email' => 'confirm@internara.test',
        '--key' => 'admin-recovery-key-2026',
    ])
        ->expectsQuestion(__('setup.cli.admin.password'), 'password123')
        ->expectsQuestion(__('setup.cli.admin.confirm_password'), 'password123')
        ->expectsQuestion(__('setup.cli.recovery_confirmation_prompt'), 'wrong-email@test.com')
        ->assertFailed();
});
