<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Modules\Permission\Models\Permission;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;
use Modules\User\Notifications\WelcomeUserNotification;
use Modules\User\Services\Contracts\UserService;

describe('UserService', function () {
    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);
        Permission::create(['name' => 'user.manage', 'guard_name' => 'web']);
    });

    test('it automatically verifies email for admin role [SYRS-NF-501]', function () {
        $userService = app(UserService::class);

        $user = $userService->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'roles' => ['admin'],
        ]);

        expect($user->hasVerifiedEmail())->toBeTrue()->and($user->hasRole('admin'))->toBeTrue();
    });

    test('it assigns student role by default and sends welcome notification', function () {
        Notification::fake();
        $userService = app(UserService::class);

        $user = $userService->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        expect($user->hasRole('student'))->toBeTrue()->and($user->hasVerifiedEmail())->toBeFalse();

        Notification::assertSentTo($user, WelcomeUserNotification::class);
    });

    test('SuperAdmin cannot be deleted via UserService', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $userService = app(UserService::class);

        expect(fn () => $userService->delete($superAdmin->id))->toThrow(
            AuthorizationException::class,
        );
    });

    test('SuperAdmin status cannot be toggled', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $userService = app(UserService::class);

        expect(fn () => $userService->toggleStatus($superAdmin->id))->toThrow(
            AuthorizationException::class,
        );
    });

    test('Standard admins cannot delete SuperAdmin via Policy', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo('user.manage');

        expect($admin->can('delete', $superAdmin))->toBeFalse();
    });
});
