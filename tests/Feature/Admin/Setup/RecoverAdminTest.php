<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Setup;

use App\Domain\Admin\Aggregates\Setup\Models\Setup;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed Spatie roles
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }

    // Ensure target private folder exists
    File::ensureDirectoryExists(storage_path('app/private'));
});

afterEach(function () {
    // Clean up key file if it exists
    File::delete(storage_path('app/private/.recovery-key'));
});

test('admin:recovery-path command displays path and existence status', function () {
    $path = storage_path('app/private/.recovery-key');
    File::delete($path);

    $this->artisan('admin:recovery-path')
        ->expectsOutputToContain($path)
        ->expectsOutputToContain(__('admin.recovery_path.missing'))
        ->assertSuccessful();

    File::put($path, 'test-key');

    $this->artisan('admin:recovery-path')
        ->expectsOutputToContain($path)
        ->expectsOutputToContain(__('admin.recovery_path.exists'))
        ->assertSuccessful();
});

test('admin:recovery-show command warns, asks confirmation, and displays key', function () {
    $path = storage_path('app/private/.recovery-key');
    File::delete($path);

    // Fails if file missing
    $this->artisan('admin:recovery-show')
        ->expectsOutputToContain(__('admin.recovery_path.missing'))
        ->assertFailed();

    // Setup record and file
    File::put($path, 'my-secret-recovery-key');
    Setup::query()->update([
        'recovery_key' => Hash::make('my-secret-recovery-key'),
    ]);

    // Aborts if user declines
    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('admin.recovery_show.confirm'), 'no')
        ->expectsOutputToContain(__('admin.recovery_show.aborted'))
        ->assertSuccessful();

    // Succeeds if user accepts
    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('admin.recovery_show.confirm'), 'yes')
        ->expectsOutput('my-secret-recovery-key')
        ->assertSuccessful();
});

test('admin:recover command resets superadmin password with valid key', function () {
    $path = storage_path('app/private/.recovery-key');
    File::put($path, 'my-secret-recovery-key');

    Setup::query()->update([
        'recovery_key' => Hash::make('my-secret-recovery-key'),
    ]);

    $superadmin = User::factory()->create([
        'email' => 'super@example.com',
        'username' => 'superadmin',
    ]);
    $superadmin->assignRole('superadmin');
    $superadmin->setStatus(AccountStatus::PROTECTED->value);

    // Run password reset
    $this->artisan('admin:recover super@example.com --reset --key=my-secret-recovery-key')
        ->expectsQuestion(__('admin.field_new_password'), 'newpassword123')
        ->expectsQuestion(__('admin.field_confirm_password'), 'newpassword123')
        ->expectsQuestion(__('admin.recover.confirm_prompt'), 'super@example.com')
        ->assertSuccessful();

    expect(Hash::check('newpassword123', $superadmin->fresh()->password))->toBeTrue();
});

test('admin:recover command fails with invalid key', function () {
    Setup::query()->update([
        'recovery_key' => Hash::make('correct-key'),
    ]);

    $this->artisan('admin:recover super@example.com --key=wrong-key')
        ->expectsOutputToContain(__('admin.recover.key_invalid'))
        ->assertFailed();
});
