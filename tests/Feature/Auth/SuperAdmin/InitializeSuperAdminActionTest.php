<?php

declare(strict_types=1);

use App\Auth\SuperAdmin\Actions\InitializeSuperAdminAction;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    SpatieRole::create(['name' => 'superadmin', 'guard_name' => 'web']);
});

test('creates super admin user with given email and password', function () {
    $user = app(InitializeSuperAdminAction::class)->execute(
        email: 'admin@test.com',
        password: 'secure-password-123',
    );

    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBe('admin@test.com');
    expect($user->name)->toBe(config('setup.defaults.admin_name', 'Administrator'));
    expect($user->username)->toBe(config('setup.defaults.admin_username', 'superadmin'));
});

test('assigns super admin role to created user', function () {
    $user = app(InitializeSuperAdminAction::class)->execute(
        email: 'admin@test.com',
        password: 'secure-password-123',
    );

    expect($user->hasRole('super_admin'))->toBeTrue();
});

test('sets account status to protected', function () {
    $user = app(InitializeSuperAdminAction::class)->execute(
        email: 'admin@test.com',
        password: 'secure-password-123',
    );

    expect($user->status->value)->toBe(AccountStatus::PROTECTED->value);
});

test('creates profile for super admin', function () {
    $user = app(InitializeSuperAdminAction::class)->execute(
        email: 'admin@test.com',
        password: 'secure-password-123',
    );

    expect($user->profile)->not->toBeNull();
});

test('password is hashed', function () {
    $user = app(InitializeSuperAdminAction::class)->execute(
        email: 'admin@test.com',
        password: 'secure-password-123',
    );

    expect($user->password)->not->toBe('secure-password-123');
    expect(Hash::check('secure-password-123', $user->password))->toBeTrue();
});

test('fails when creating duplicate super admin with same username', function () {
    app(InitializeSuperAdminAction::class)->execute(
        email: 'first@test.com',
        password: 'password-123',
    );

    app(InitializeSuperAdminAction::class)->execute(
        email: 'second@test.com',
        password: 'password-456',
    );
})->throws(UniqueConstraintViolationException::class);
