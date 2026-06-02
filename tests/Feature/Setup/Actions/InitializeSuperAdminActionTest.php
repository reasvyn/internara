<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Setup\Actions\InitializeSuperAdminAction;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
});

describe('InitializeSuperAdminAction', function () {
    it('creates a super admin with email and password', function () {
        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'cli-admin@test.com',
            password: 'SecurePass123!',
        );

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->email)->toBe('cli-admin@test.com');
    });

    it('uses defaults for name and username when omitted', function () {
        config(['setup.defaults.admin_name' => 'Administrator']);

        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'defaults@test.com',
            password: 'SecurePass123!',
        );

        expect($user->name)->toBe('Administrator')
            ->and($user->username)->not->toBeNull();
    });

    it('accepts custom name and username', function () {
        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'custom@test.com',
            password: 'SecurePass123!',
            name: 'Custom Admin',
            username: 'customadmin',
        );

        expect($user->name)->toBe('Custom Admin')
            ->and($user->username)->toBe('customadmin');
    });

    it('creates a profile for the user', function () {
        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'with-profile@test.com',
            password: 'SecurePass123!',
        );

        expect($user->profile)->not->toBeNull();
    });

    it('assigns SUPER_ADMIN role', function () {
        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'role-cli@test.com',
            password: 'SecurePass123!',
        );

        expect($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
    });

    it('sets PROTECTED status', function () {
        $user = app(InitializeSuperAdminAction::class)->execute(
            email: 'status-cli@test.com',
            password: 'SecurePass123!',
        );

        expect((string) $user->status)->toBe(AccountStatus::PROTECTED->value);
    });
});
