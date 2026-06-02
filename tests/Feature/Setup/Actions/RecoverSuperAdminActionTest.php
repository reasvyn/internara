<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Setup\Actions\RecoverSuperAdminAction;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
});

describe('RecoverSuperAdminAction', function () {
    it('creates a new super admin when not in reset mode', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'new-admin@test.com',
            password: 'SecurePass123!',
            isReset: false,
        );

        expect($user->email)->toBe('new-admin@test.com')
            ->and($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue()
            ->and((string) $user->status)->toBe(AccountStatus::PROTECTED->value);
    });

    it('creates profile for new admin', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'profile@test.com',
            password: 'SecurePass123!',
        );

        expect($user->profile)->not->toBeNull();
    });

    it('resets existing super admin password when isReset is true', function () {
        $original = User::factory()->create(['email' => 'existing@test.com']);
        $original->assignRole(Role::SUPER_ADMIN->value);
        $original->setStatus(AccountStatus::PROTECTED);

        $oldPassword = $original->password;

        $recovered = app(RecoverSuperAdminAction::class)->execute(
            email: 'existing@test.com',
            password: 'NewSecurePass456!',
            isReset: true,
        );

        expect($recovered->id)->toBe($original->id)
            ->and($recovered->password)->not->toBe($oldPassword)
            ->and($recovered->locked_at)->toBeNull();
    });

    it('rate limits recovery attempts', function () {
        $email = 'ratelimit@test.com';

        for ($i = 0; $i < 3; $i++) {
            try {
                app(RecoverSuperAdminAction::class)->execute(
                    email: $email,
                    password: 'WrongPass!',
                    isReset: true,
                );
            } catch (\Throwable) {
            }
        }

        app(RecoverSuperAdminAction::class)->execute(
            email: $email,
            password: 'AnotherWrong!',
            isReset: true,
        );
    })->throws(RuntimeException::class, 'Too many recovery attempts');

    it('clears rate limit cache on successful recovery', function () {
        $email = 'success-clear@test.com';
        $cacheKey = 'recover_admin_attempts_'.md5($email);

        app(RecoverSuperAdminAction::class)->execute(
            email: $email,
            password: 'SecurePass123!',
        );

        expect(Cache::get($cacheKey))->toBeNull();
    });
});
