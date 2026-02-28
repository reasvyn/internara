<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Identity;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;
use Modules\User\Notifications\WelcomeUserNotification;
use Modules\User\Services\Contracts\UserService;

describe('User Lifecycle Feature Test (BP-ID-01 & BP-ID-02)', function () {
    
    beforeEach(function () {
        $this->seed(\Modules\Permission\Database\Seeders\PermissionDatabaseSeeder::class);
        setting(['app_installed' => true]); 
        $this->userService = app(UserService::class);
        Notification::fake();
    });

    test('creating a user correctly triggers the atomic creation of its corresponding profile', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($admin);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'roles' => [Role::STUDENT->value],
        ];

        $user = $this->userService->create($userData);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->profile)->not->toBeNull();
        expect($user->profile->user_id)->toBe($user->id);
    });

    test('it sends a welcome notification with credentials upon creation', function () {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($admin);

        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'roles' => [Role::TEACHER->value],
        ];

        $user = $this->userService->create($userData);

        Notification::assertSentTo($user, WelcomeUserNotification::class);
    });

    describe('Hierarchical Authorization', function () {
        test('it enforces hierarchical authority on user creation', function () {
            // Simulate an Admin user
            $admin = User::factory()->create();
            $admin->assignRole(Role::ADMIN->value);
            $this->actingAs($admin);

            // Admin should be able to create a Teacher
            $userData = [
                'name' => 'Teacher User',
                'email' => 'teacher@example.com',
                'roles' => [Role::TEACHER->value],
            ];

            expect(fn() => $this->userService->create($userData))->not->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
        });

        test('students are prohibited from creating other users', function () {
            $student = User::factory()->create();
            $student->assignRole(Role::STUDENT->value);
            $this->actingAs($student);

            $userData = [
                'name' => 'Another Student',
                'email' => 'other@example.com',
                'roles' => [Role::STUDENT->value],
            ];

            // Gate should throw AuthorizationException
            $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
            $this->userService->create($userData);
        });
    });
});
