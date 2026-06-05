<?php

declare(strict_types=1);

use App\User\Enums\AccountStatus;
use App\User\Enums\Role as RoleEnum;
use App\User\Login\Actions\LoginAction;
use App\User\Models\User;
use App\User\Notification\Actions\MarkAsReadAction;
use App\User\Notification\Actions\SendNotificationAction;
use App\User\Notification\Models\Notification;
use App\User\Password\Actions\ConfirmPasswordAction;
use App\User\Password\Actions\UpdateUserPasswordAction;
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

test('LoginAction handles failed attempts and rate limits exponentially starting at 10 attempts without locking account', function () {
    $user = User::factory()->create([
        'username' => 'throttleuser',
        'password' => Hash::make('CorrectPassword123!'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $loginAction = app(LoginAction::class);

    $identifierHash = md5('throttleuser');
    Cache::forget("login:attempts:{$identifierHash}");
    Cache::forget("login:lockout:{$identifierHash}");

    // Attempts 1 to 9: should fail with standard auth.failed
    for ($i = 1; $i <= 9; $i++) {
        expect(fn () => $loginAction->execute('throttleuser', 'wrong_pass'))
            ->toThrow(RuntimeException::class, __('auth.failed'));
        expect($user->fresh()->locked_at)->toBeNull();
    }

    // Attempt 10: fails and triggers a 10s lockout
    try {
        $loginAction->execute('throttleuser', 'wrong_pass');
        $this->fail('Expected exception was not thrown.');
    } catch (RuntimeException $e) {
        // May throw either rate limit error or standard auth.failed depending on implementation
    }

    expect($user->fresh()->locked_at)->toBeNull(); // account must NOT be locked

    // Attempt 11: should be blocked immediately with throttle message (lockout is active)
    try {
        $loginAction->execute('throttleuser', 'CorrectPassword123!');
        $this->fail('Expected exception was not thrown.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain(__('auth.throttle', ['seconds' => 10]) ?? '10');
    }

    // Let's verify exponential duration calculation
    // Bypass lockout time by forgetting the lockout key in cache
    Cache::forget("login:lockout:{$identifierHash}");

    // Attempt 11 fails -> lockout 20 seconds
    try {
        $loginAction->execute('throttleuser', 'wrong_pass');
        $this->fail('Expected exception was not thrown.');
    } catch (RuntimeException $e) {
        // lockout is now 20 seconds
    }

    try {
        $loginAction->execute('throttleuser', 'CorrectPassword123!');
        $this->fail('Expected exception was not thrown.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain(__('auth.throttle', ['seconds' => 20]) ?? '20');
    }
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
