<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Setup\Actions\SetupSuperAdminAction;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
});

describe('SetupSuperAdminAction', function () {
    it('creates a super admin user', function () {
        $user = app(SetupSuperAdminAction::class)->execute(
            email: 'admin@internara.test',
            password: 'SecurePass123!',
        );

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->exists)->toBeTrue()
            ->and($user->email)->toBe('admin@internara.test')
            ->and($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
    });

    it('uses configured admin name and username', function () {
        config(['setup.defaults.admin_name' => 'Administrator']);
        config(['setup.defaults.admin_username' => 'superadmin']);

        $user = app(SetupSuperAdminAction::class)->execute(
            email: 'admin@test.com',
            password: 'SecurePass123!',
        );

        expect($user->name)->toBe('Administrator')
            ->and($user->username)->toBe('superadmin')
            ->and($user->setup_required)->toBeFalse();
    });

    it('marks email as verified', function () {
        $user = app(SetupSuperAdminAction::class)->execute(
            email: 'verified@test.com',
            password: 'SecurePass123!',
        );

        expect($user->hasVerifiedEmail())->toBeTrue();
    });

    it('assigns SUPER_ADMIN role', function () {
        $user = app(SetupSuperAdminAction::class)->execute(
            email: 'role@test.com',
            password: 'SecurePass123!',
        );

        expect($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
    });

    it('sets PROTECTED account status', function () {
        $user = app(SetupSuperAdminAction::class)->execute(
            email: 'status@test.com',
            password: 'SecurePass123!',
        );

        expect((string) $user->status)->toBe(AccountStatus::PROTECTED->value);
    });

    it('validates email format', function () {
        app(SetupSuperAdminAction::class)->execute(
            email: 'not-email',
            password: 'SecurePass123!',
        );
    })->throws(ValidationException::class);

    it('validates password rules', function () {
        app(SetupSuperAdminAction::class)->execute(
            email: 'weak@test.com',
            password: '123',
        );
    })->throws(ValidationException::class);

    it('throws RejectedException if immutable super admin exists', function () {
        app(SetupSuperAdminAction::class)->execute(
            email: 'first@test.com',
            password: 'SecurePass123!',
        );

        app(SetupSuperAdminAction::class)->execute(
            email: 'second@test.com',
            password: 'SecurePass123!',
        );
    })->throws(RejectedException::class);
});
