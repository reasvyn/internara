<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
});

describe('CreateAdminCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('admin:create');
    });

    it('fails when super admin already exists', function () {
        User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);

        $this->artisan('admin:create', ['email' => 'admin@test.com', 'password' => 'password123'])
            ->assertExitCode(1);
    });

    it('succeeds when no super admin exists', function () {
        $this->artisan('admin:create', ['email' => 'admin@test.com', 'password' => 'password123'])
            ->assertExitCode(0);

        expect(User::role(Role::SUPER_ADMIN->value)->count())->toBe(1);
    });

    it('creates user with provided email', function () {
        $this->artisan('admin:create', ['email' => 'custom@test.com', 'password' => 'password123'])
            ->assertExitCode(0);

        expect(User::where('email', 'custom@test.com')->exists())->toBeTrue();
    });
});
