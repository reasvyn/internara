<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\Domain\SysAdmin\Aggregates\Setup\Models\Setup;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
    File::ensureDirectoryExists(storage_path('app/private'));
    $this->path = storage_path('app/private/.recovery-key');
});

afterEach(function () {
    File::delete($this->path);
});

test('admin:recover command fails if recovery key is not provided and file is absent', function () {
    File::delete($this->path);
    Setup::query()->update(['recovery_key' => null]);

    $this->artisan('admin:recover super@example.com --reset')
        ->expectsOutputToContain(__('sysadmin.recover.key_required'))
        ->assertFailed();
});

test('admin:recover command fails when key is invalid', function () {
    Setup::query()->update([
        'recovery_key' => Hash::make('correct-key'),
    ]);

    $this->artisan('admin:recover super@example.com --key=wrong-key')
        ->expectsOutputToContain(__('sysadmin.recover.key_invalid'))
        ->assertFailed();
});

test('admin:recover command fails if passwords mismatch', function () {
    File::put($this->path, 'my-secret-recovery-key');
    Setup::query()->update([
        'recovery_key' => Hash::make('my-secret-recovery-key'),
    ]);

    $superadmin = User::factory()->create([
        'email' => 'super@example.com',
        'username' => 'superadmin',
    ]);
    $superadmin->assignRole('superadmin');
    $superadmin->setStatus(AccountStatus::PROTECTED->value);

    $this->artisan('admin:recover super@example.com --reset --key=my-secret-recovery-key')
        ->expectsQuestion(__('sysadmin.field_new_password'), 'password123')
        ->expectsQuestion(__('sysadmin.field_confirm_password'), 'differentpassword')
        ->expectsOutputToContain(__('sysadmin.recover.password_mismatch'))
        ->assertFailed();
});

test('admin:recover command successfully resets superadmin password with valid key', function () {
    File::put($this->path, 'my-secret-recovery-key');
    Setup::query()->update([
        'recovery_key' => Hash::make('my-secret-recovery-key'),
    ]);

    $superadmin = User::factory()->create([
        'email' => 'super@example.com',
        'username' => 'superadmin',
    ]);
    $superadmin->assignRole('superadmin');
    $superadmin->setStatus(AccountStatus::PROTECTED->value);

    $this->artisan('admin:recover super@example.com --reset --key=my-secret-recovery-key')
        ->expectsQuestion(__('sysadmin.field_new_password'), 'newpassword123')
        ->expectsQuestion(__('sysadmin.field_confirm_password'), 'newpassword123')
        ->expectsQuestion(__('sysadmin.recover.confirm_prompt'), 'super@example.com')
        ->assertSuccessful();

    expect(Hash::check('newpassword123', $superadmin->fresh()->password))->toBeTrue();
});
