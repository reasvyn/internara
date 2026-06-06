<?php

declare(strict_types=1);

use App\Exceptions\RejectedException;
use App\User\Models\User;
use App\User\SuperAdmin\Actions\SetupSuperAdminAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
});

test('creates superadmin with permanent name and username from config', function () {
    $action = app(SetupSuperAdminAction::class);

    $user = $action->execute('admin@internara.test', 'SecurePass123!');

    expect($user->name)->toBe('Administrator');
    expect($user->username)->toBe('superadmin');
    expect($user->email)->toBe('admin@internara.test');
});

test('creates superadmin with protected status', function () {
    $action = app(SetupSuperAdminAction::class);

    $user = $action->execute('admin@internara.test', 'SecurePass123!');

    expect($user->status->value)->toBe('protected');
    expect($user->hasRole('superadmin'))->toBeTrue();
});

test('creates superadmin with verified email', function () {
    $action = app(SetupSuperAdminAction::class);

    $user = $action->execute('admin@internara.test', 'SecurePass123!');

    expect($user->email_verified_at)->not->toBeNull();
});

test('prevents creating duplicate superadmin', function () {
    $action = app(SetupSuperAdminAction::class);
    $action->execute('admin@internara.test', 'SecurePass123!');

    expect(fn () => $action->execute('another@internara.test', 'SecurePass456!'))
        ->toThrow(RejectedException::class, 'Super admin already exists');
});

test('updates email on existing superadmin if mutable', function () {
    $user = User::factory()->create([
        'name' => 'Administrator',
        'username' => 'superadmin',
        'email' => 'old@internara.test',
        'status' => 'activated',
    ]);
    $user->assignRole('superadmin');

    $action = app(SetupSuperAdminAction::class);
    $updated = $action->execute('new@internara.test', 'SecurePass123!');

    expect($updated->email)->toBe('new@internara.test');
});

test('creates superadmin with setup_required false', function () {
    $action = app(SetupSuperAdminAction::class);

    $user = $action->execute('admin@internara.test', 'SecurePass123!');

    expect($user->setup_required)->toBeFalse();
});
