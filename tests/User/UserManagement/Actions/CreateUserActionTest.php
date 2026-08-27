<?php

declare(strict_types=1);

use App\Core\Actions\BaseCommandAction;
use App\User\Models\User;
use App\User\UserManagement\Actions\CreateUserAction;
use App\User\UserManagement\Data\CreateUserData;
use App\User\UserManagement\Events\UserCreated;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 95EVB — User CRUD and Status — CreateUserAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('95EVB: CreateUserAction', function (): void {
    beforeEach(function (): void {
        // Ensure roles exist for syncRoles
        Role::findOrCreate('student', 'web');
        Role::findOrCreate('teacher', 'web');
    });

    test('95EVB-FR-UC11: extends BaseCommandAction', function (): void {
        expect(new CreateUserAction)->toBeInstanceOf(BaseCommandAction::class);
    });

    test('95EVB-FR-UC1: validates name, username, email', function (): void {
        $data = CreateUserData::from([
            'user' => ['name' => '', 'email' => 'invalid-email', 'username' => 'ab'],
            'profile' => [],
            'roles' => [],
        ]);

        expect(fn () => app(CreateUserAction::class)->execute($data))
            ->toThrow(Illuminate\Validation\ValidationException::class);
    });

    test('95EVB-FR-UC4: creates User + Profile + syncs roles in transaction', function (): void {
        Notification::fake();
        Event::fake([UserCreated::class]);

        $data = CreateUserData::from([
            'user' => [
                'name' => 'Test User',
                'email' => 'testuser@example.test',
                'username' => 'testuser123',
                'password' => 'Password123!',
            ],
            'profile' => ['nis' => '12345'],
            'roles' => ['student'],
            'sendNotification' => false,
        ]);

        $user = app(CreateUserAction::class)->execute($data);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('Test User')
            ->and($user->email)->toBe('testuser@example.test')
            ->and($user->profile)->not->toBeNull()
            ->and($user->hasRole('student'))->toBeTrue();

        Event::assertDispatched(UserCreated::class);
    });

    test('95EVB-FR-UC12: dispatches UserCreated event', function (): void {
        Event::fake([UserCreated::class]);
        Notification::fake();

        $data = CreateUserData::from([
            'user' => ['name' => 'Event Test', 'email' => 'event@test.test', 'username' => 'eventtest', 'password' => 'Password123!'],
            'profile' => [],
            'roles' => [],
            'sendNotification' => false,
        ]);

        app(CreateUserAction::class)->execute($data);

        Event::assertDispatched(UserCreated::class);
    });

    test('95EVB-FR-UC2: auto-generates username from email when not provided', function (): void {
        Notification::fake();

        $data = CreateUserData::from([
            'user' => ['name' => 'Auto User', 'email' => 'autouser@example.test', 'password' => 'Password123!'],
            'profile' => [],
            'roles' => [],
            'sendNotification' => false,
        ]);

        $user = app(CreateUserAction::class)->execute($data);

        expect($user->username)->not->toBeEmpty()
            ->and($user->username)->toContain('autouser');
    });
});
