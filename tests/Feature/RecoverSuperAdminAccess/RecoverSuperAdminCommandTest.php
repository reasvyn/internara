<?php

declare(strict_types=1);

use App\Models\Setup;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);

    Setup::query()->forceDelete();
    Setup::factory()->installed()->withRecoveryKey()->create();
});

it('fails when system is not installed', function () {
    Setup::query()->delete();

    artisan('setup:recover-super-admin', ['email' => 'admin@test.com'])
        ->assertFailed();
});

it('fails when recovery key is missing', function () {
    artisan('setup:recover-super-admin', [
        'email' => 'admin@test.com',
    ])->assertFailed();
});

it('fails when recovery key is invalid', function () {
    artisan('setup:recover-super-admin', [
        'email' => 'admin@test.com',
        '--key' => 'wrong-key',
    ])->assertFailed();
});

it('fails when creating admin but the email already exists', function () {
    User::factory()->create(['email' => 'existing@internara.test']);

    artisan('setup:recover-super-admin', [
        'email' => 'existing@internara.test',
        '--key' => 'admin-recovery-key-2026',
    ])->assertFailed();
});

it('fails when resetting a non-existent user', function () {
    artisan('setup:recover-super-admin', [
        'email' => 'ghost@internara.test',
        '--reset' => true,
        '--key' => 'admin-recovery-key-2026',
    ])->assertFailed();
});
