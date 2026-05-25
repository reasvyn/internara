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

describe('AdminPromoteCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('admin:promote');
    });

    it('fails when user not found', function () {
        $this->artisan('admin:promote', ['identifier' => 'nonexistent@test.com'])
            ->assertExitCode(1);
    });

    it('fails with invalid role option', function () {
        $user = User::factory()->create();

        $this->artisan('admin:promote', ['identifier' => $user->email, '--role' => 'superuser'])
            ->assertExitCode(1);
    });

    it('succeeds in promoting user to admin', function () {
        $user = User::factory()->create();

        $this->artisan('admin:promote', ['identifier' => $user->email, '--role' => 'admin'])
            ->assertExitCode(0);

        expect($user->fresh()->hasRole(Role::ADMIN->value))->toBeTrue();
    });

    it('succeeds in promoting user to super_admin', function () {
        $user = User::factory()->create();

        $this->artisan('admin:promote', ['identifier' => $user->email, '--role' => 'super_admin'])
            ->assertExitCode(0);

        expect($user->fresh()->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
    });

    it('finds user by username', function () {
        $user = User::factory()->create(['username' => 'johndoe']);

        $this->artisan('admin:promote', ['identifier' => 'johndoe', '--role' => 'admin'])
            ->assertExitCode(0);

        expect($user->fresh()->hasRole(Role::ADMIN->value))->toBeTrue();
    });

    it('warns when user already has the role', function () {
        $user = User::factory()->create()->assignRole(Role::ADMIN->value);

        $this->artisan('admin:promote', ['identifier' => $user->email, '--role' => 'admin'])
            ->assertExitCode(0);
    });
});
