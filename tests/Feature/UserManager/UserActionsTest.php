<?php

declare(strict_types=1);

use App\Actions\User\CreateUserAction;
use App\Actions\User\DeleteUserAction;
use App\Actions\User\UpdateProfileAction;
use App\Actions\User\UpdateUserAction;
use App\Models\User;
use App\Notifications\Auth\WelcomeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
});

describe('CreateUserAction', function () {

    it('creates a user with minimal data', function () {
        $user = app(CreateUserAction::class)->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
        expect($user->username)->toStartWith('u');
        expect(strlen($user->username))->toBe(9);
    });

    it('assigns roles to the created user', function () {
        $user = app(CreateUserAction::class)->execute(
            userData: ['name' => 'Admin User', 'email' => 'admin@example.com'],
            roles: ['admin'],
        );

        expect($user->hasRole('admin'))->toBeTrue();
    });

    it('creates profile when profile data is provided', function () {
        $user = app(CreateUserAction::class)->execute(
            userData: ['name' => 'Profile User', 'email' => 'profile@example.com'],
            profileData: ['phone' => '08123456789'],
        );

        expect($user->profile)->not->toBeNull();
        expect($user->profile->phone)->toBe('08123456789');
    });

    it('sends welcome notification with auto-generated password', function () {
        Notification::fake();

        $user = app(CreateUserAction::class)->execute([
            'name' => 'Notify User',
            'email' => 'notify@example.com',
        ]);

        Notification::assertSentTo($user, WelcomeNotification::class);
    });

    it('does not send welcome notification when password is provided', function () {
        Notification::fake();

        app(CreateUserAction::class)->execute([
            'name' => 'No Notify',
            'email' => 'nonotify@example.com',
            'password' => 'explicit-password',
        ]);

        Notification::assertNothingSent();
    });

    it('uses provided username when given', function () {
        $user = app(CreateUserAction::class)->execute([
            'name' => 'Custom Username',
            'email' => 'custom-user@example.com',
            'username' => 'mycustomuser',
        ]);

        expect($user->username)->toBe('mycustomuser');
    });

    it('hashes the password', function () {
        $user = app(CreateUserAction::class)->execute([
            'name' => 'Hash Test',
            'email' => 'hash@example.com',
            'password' => 'plain-text-password',
        ]);

        expect($user->password)->not->toBe('plain-text-password');
        expect(Hash::check('plain-text-password', $user->password))->toBeTrue();
    });

    it('persists user to database', function () {
        app(CreateUserAction::class)->execute([
            'name' => 'Persist Test',
            'email' => 'persist@example.com',
        ]);

        assertDatabaseHas('users', ['email' => 'persist@example.com']);
    });

});

describe('UpdateUserAction', function () {

    it('updates user name and email', function () {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        app(UpdateUserAction::class)->execute($user, [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        expect($user->fresh()->name)->toBe('New Name');
        expect($user->fresh()->email)->toBe('new@example.com');
    });

    it('syncs roles when provided', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        app(UpdateUserAction::class)->execute($user, ['name' => 'Role Sync'], roles: ['admin', 'teacher']);

        expect($user->fresh()->hasRole('admin'))->toBeTrue();
        expect($user->fresh()->hasRole('teacher'))->toBeTrue();
        expect($user->fresh()->hasRole('student'))->toBeFalse();
    });

    it('updates or creates profile when profile data is provided', function () {
        $user = User::factory()->create();
        $user->profile()->create(['phone' => '08111111111']);

        app(UpdateUserAction::class)->execute($user, ['name' => 'Profile Update'], profileData: ['phone' => '08222222222']);

        expect($user->fresh()->profile->phone)->toBe('08222222222');
    });

    it('persists changes to database', function () {
        $user = User::factory()->create(['name' => 'Before']);

        app(UpdateUserAction::class)->execute($user, ['name' => 'After']);

        assertDatabaseHas('users', ['id' => $user->id, 'name' => 'After']);
    });

});

describe('DeleteUserAction', function () {

    it('deletes a user', function () {
        $user = User::factory()->create();

        app(DeleteUserAction::class)->execute($user);

        assertDatabaseMissing('users', ['id' => $user->id]);
    });

    it('throws when deleting the last super_admin', function () {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        app(DeleteUserAction::class)->execute($admin);
    })->throws(RuntimeException::class, 'Cannot delete the last administrator account.');

    it('allows deleting super_admin when others exist', function () {
        $admin1 = User::factory()->create();
        $admin1->assignRole('super_admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('super_admin');

        app(DeleteUserAction::class)->execute($admin1);

        assertDatabaseMissing('users', ['id' => $admin1->id]);
    });

});

describe('UpdateProfileAction', function () {

    it('creates and updates profile for user', function () {
        $user = User::factory()->create();

        $profile = app(UpdateProfileAction::class)->execute($user, [
            'phone' => '08123456789',
            'address' => '123 Main St',
        ]);

        expect($profile->phone)->toBe('08123456789');
        expect($profile->address)->toBe('123 Main St');
    });

    it('updates existing profile', function () {
        $user = User::factory()->create();
        $user->profile()->create(['phone' => 'old-phone']);

        app(UpdateProfileAction::class)->execute($user, ['phone' => 'new-phone']);

        expect($user->fresh()->profile->phone)->toBe('new-phone');
    });

    it('returns existing profile when no data provided', function () {
        $user = User::factory()->create();
        $profile = $user->profile()->create(['phone' => '123']);

        $result = app(UpdateProfileAction::class)->execute($user, []);

        expect($result->id)->toBe($profile->id);
    });

});
