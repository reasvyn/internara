<?php

declare(strict_types=1);

use App\Auth\Login\Events\LoginSucceeded;
use App\Auth\Login\Listeners\SendRoleWelcomeNotification;
use App\Auth\Permissions\Enums\Role;
use App\Core\Contracts\SendsNotifications;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {});

test('sends welcome notification on first super admin login', function () {
    $user = User::factory()->create([
        'first_login_at' => null,
    ]);
    $user->assignRole(Role::SUPER_ADMIN->value);

    $mockSender = mock(SendsNotifications::class);
    $mockSender->shouldReceive('execute')
        ->once()
        ->withArgs(fn ($userId, $type, $title, $message, $data, $link) => true);

    $listener = new SendRoleWelcomeNotification($mockSender);
    $listener->handle(new LoginSucceeded($user, $user->email));

    $user->refresh();
    expect($user->first_login_at)->not->toBeNull();
});

test('does not send notification on subsequent logins', function () {
    $user = User::factory()->create([
        'first_login_at' => now()->subDay(),
    ]);
    $user->assignRole(Role::SUPER_ADMIN->value);

    $mockSender = mock(SendsNotifications::class);
    $mockSender->shouldReceive('execute')->never();

    $listener = new SendRoleWelcomeNotification($mockSender);
    $listener->handle(new LoginSucceeded($user, $user->email));
});

test('does not send notification for non super admin users', function () {
    $user = User::factory()->create([
        'first_login_at' => null,
    ]);

    $mockSender = mock(SendsNotifications::class);
    $mockSender->shouldReceive('execute')->never();

    $listener = new SendRoleWelcomeNotification($mockSender);
    $listener->handle(new LoginSucceeded($user, $user->email));
});

test('send notification passes correct parameters', function () {
    $user = User::factory()->create([
        'first_login_at' => null,
    ]);
    $user->assignRole(Role::SUPER_ADMIN->value);

    $mockSender = mock(SendsNotifications::class);
    $mockSender->shouldReceive('execute')
        ->once()
        ->withArgs(fn ($userId, $type, $title, $message) => $userId === $user->id && $type === 'welcome' && $title === __('notifications.welcome_to_dashboard.title') && $message === __('notifications.welcome_to_dashboard.super_admin'));

    $listener = new SendRoleWelcomeNotification($mockSender);
    $listener->handle(new LoginSucceeded($user, $user->email));
});
