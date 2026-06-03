<?php

declare(strict_types=1);

use App\Domain\User\Aggregates\Login\Actions\LoginAction;
use App\Domain\User\Aggregates\Notification\Actions\MarkAsReadAction;
use App\Domain\User\Aggregates\Notification\Actions\SendNotificationAction;
use App\Domain\User\Aggregates\Notification\Models\Notification;
use App\Domain\User\Aggregates\Password\Actions\ConfirmPasswordAction;
use App\Domain\User\Aggregates\Password\Actions\UpdateUserPasswordAction;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed Spatie roles
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('User model initials generation', function () {
    $user1 = User::factory()->make(['name' => 'Alice']);
    expect($user1->initials())->toBe('AL');

    $user2 = User::factory()->make(['name' => 'John Doe']);
    expect($user2->initials())->toBe('JD');

    $user3 = User::factory()->make(['name' => 'Mark John Smith']);
    expect($user3->initials())->toBe('MS');
});

test('User model delete protection for super_admin role', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $normalUser = User::factory()->create();
    $normalUser->assignRole('student');

    // Super admin delete throws RuntimeException in delete() and booted hook
    expect(fn () => $superAdmin->delete())->toThrow(RuntimeException::class, 'Super administrator accounts cannot be deleted.');

    // Normal user delete succeeds
    expect($normalUser->delete())->toBeTrue();
});

test('User model locked and active scopes', function () {
    $unlockedUser = User::factory()->create([
        'locked_at' => null,
        'setup_required' => false,
    ]);

    $lockedUser = User::factory()->create([
        'locked_at' => now(),
        'locked_reason' => 'test_lock',
        'setup_required' => false,
    ]);

    $setupUser = User::factory()->create([
        'locked_at' => null,
        'setup_required' => true,
    ]);

    expect(User::locked()->count())->toBe(1);
    expect(User::unlocked()->count())->toBe(2);
    expect(User::active()->count())->toBe(1);
});

test('LoginAction authenticates with email or username', function () {
    $user = User::factory()->create([
        'username' => 'testlogin',
        'email' => 'login@example.com',
        'password' => Hash::make('NewPassword123!'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $loginAction = app(LoginAction::class);

    // Login via username
    $auth1 = $loginAction->execute('testlogin', 'NewPassword123!');
    expect($auth1->id)->toBe($user->id);

    // Login via email
    $auth2 = $loginAction->execute('login@example.com', 'NewPassword123!');
    expect($auth2->id)->toBe($user->id);
});

test('LoginAction handles wrong password and locks account on threshold', function () {
    // Default threshold is 10 failed attempts
    $user = User::factory()->create([
        'username' => 'lockeduser',
        'password' => Hash::make('CorrectPassword123!'),
    ]);

    // Attach ACTIVATED status to allow login
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $loginAction = app(LoginAction::class);

    // Attempts 1-9: Failed but not locked yet
    for ($i = 1; $i < 10; $i++) {
        expect(fn () => $loginAction->execute('lockeduser', 'wrong_pass'))->toThrow(RuntimeException::class);
        expect($user->fresh()->locked_at)->toBeNull();
    }

    // Attempt 10: Failed (reaches limit of 10) - account locked
    expect(fn () => $loginAction->execute('lockeduser', 'wrong_pass'))->toThrow(RuntimeException::class);

    $user = $user->fresh();
    expect($user->locked_at)->not->toBeNull();
    expect($user->locked_reason)->toBe('too_many_failed_attempts');

    // Locked user login attempts are blocked immediately
    expect(fn () => $loginAction->execute('lockeduser', 'CorrectPassword123!'))->toThrow(RuntimeException::class, __('auth.blocked'));
});

test('ConfirmPasswordAction stores time in session on success', function () {
    $user = User::factory()->create([
        'password' => Hash::make('SecretPassword123!'),
    ]);

    $action = app(ConfirmPasswordAction::class);

    // Success
    $action->execute($user, 'SecretPassword123!');
    expect(session('auth.password_confirmed_at'))->not->toBeNull();

    // Failure
    expect(fn () => $action->execute($user, 'wrong'))->toThrow(RuntimeException::class);
});

test('UpdateUserPasswordAction updates user password if compliant', function () {
    $user = User::factory()->create();
    $action = app(UpdateUserPasswordAction::class);

    // Fails with weak password
    expect(fn () => $action->execute($user, 'weak'))->toThrow(ValidationException::class);

    // Succeeds with strong password
    $action->execute($user, 'StrongNewPassword123!');
    expect(Hash::check('StrongNewPassword123!', $user->fresh()->password))->toBeTrue();
});

test('In-app Notification sending and marking as read', function () {
    $user = User::factory()->create();

    $sendAction = app(SendNotificationAction::class);
    $markAction = app(MarkAsReadAction::class);

    $notif = $sendAction->execute(
        userId: $user->id,
        type: 'test_notification',
        title: 'New Announcement',
        message: 'This is a test announcement'
    );

    expect($notif)->toBeInstanceOf(Notification::class);
    expect($notif->user_id)->toBe($user->id);
    expect($notif->is_read)->toBeFalse();

    $readNotif = $markAction->execute($notif);
    expect($readNotif->is_read)->toBeTrue();
    expect($readNotif->read_at)->not->toBeNull();
});
