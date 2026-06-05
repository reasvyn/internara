<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\SysAdmin\Setup\Models\Setup;
use App\User\Enums\Role as RoleEnum;
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

test('admin:recovery-show command fails if key file is missing', function () {
    File::delete($this->path);

    $this->artisan('admin:recovery-show')
        ->expectsOutputToContain(__('sysadmin.recovery_path.missing'))
        ->assertFailed();
});

test('admin:recovery-show command fails if setup record lacks recovery key hash', function () {
    File::put($this->path, 'key-content');
    Setup::query()->update(['recovery_key' => null]);

    $this->artisan('admin:recovery-show')
        ->expectsOutputToContain(__('sysadmin.recovery_show.no_setup'))
        ->assertFailed();
});

test('admin:recovery-show command aborts display when confirmation is declined', function () {
    File::put($this->path, 'my-secret-recovery-key');
    Setup::query()->update([
        'recovery_key' => Hash::make('my-secret-recovery-key'),
    ]);

    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('sysadmin.recovery_show.confirm'), 'no')
        ->expectsOutputToContain(__('sysadmin.recovery_show.aborted'))
        ->assertSuccessful();
});

test('admin:recovery-show command prints key when confirmation is accepted', function () {
    File::put($this->path, 'my-secret-recovery-key');
    Setup::query()->update([
        'recovery_key' => Hash::make('my-secret-recovery-key'),
    ]);

    $this->artisan('admin:recovery-show')
        ->expectsConfirmation(__('sysadmin.recovery_show.confirm'), 'yes')
        ->expectsOutput('my-secret-recovery-key')
        ->assertSuccessful();
});
