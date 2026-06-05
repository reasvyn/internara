<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\User\Enums\Role as RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

test('admin:recovery-path command displays missing warning when key file is absent', function () {
    File::delete($this->path);

    $this->artisan('admin:recovery-path')
        ->expectsOutputToContain($this->path)
        ->expectsOutputToContain(__('sysadmin.recovery_path.missing'))
        ->assertSuccessful();
});

test('admin:recovery-path command displays success message when key file exists', function () {
    File::put($this->path, 'test-key');

    $this->artisan('admin:recovery-path')
        ->expectsOutputToContain($this->path)
        ->expectsOutputToContain(__('sysadmin.recovery_path.exists'))
        ->assertSuccessful();
});
