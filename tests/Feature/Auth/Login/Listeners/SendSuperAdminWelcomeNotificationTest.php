<?php

declare(strict_types=1);

use App\Auth\Login\Events\LoginSucceeded;
use App\Auth\Login\Listeners\SendSuperAdminWelcomeNotification;
use App\Core\Contracts\SendsNotifications;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
});

test('sends welcome notification on first super admin login', function () {
    $user = User::factory()->create([
        'first_login_at' => null,
    ]);
    $user->assignRole('super_admin');

    $mockSender = mock(SendsNotifications::class);
    $mockSender->shouldReceive('execute')
        ->once()
        ->withArgs(fn ($userId, $type, $title, $message, $data, $link) => true);

    $listener = new SendSuperAdminWelcomeNotification($mockSender);
    $listener->handle(new LoginSucceeded($user, $user->email));

    $user->refresh();
    expect($user->first_login_at)->not->toBeNull();
});

test('does not send notification on subsequent logins', function () {
    $user = User::factory()->create([
        'first_login_at' => now()->subDay(),
    ]);
    $user->assignRole('super_admin');

    $mockSender = mock(SendsNotifications::class);
    $mockSender->shouldReceive('execute')->never();

    $listener = new SendSuperAdminWelcomeNotification($mockSender);
    $listener->handle(new LoginSucceeded($user, $user->email));
});

test('does not send notification for non super admin users', function () {
    $user = User::factory()->create([
        'first_login_at' => null,
    ]);

    $mockSender = mock(SendsNotifications::class);
    $mockSender->shouldReceive('execute')->never();

    $listener = new SendSuperAdminWelcomeNotification($mockSender);
    $listener->handle(new LoginSucceeded($user, $user->email));
});

test('send notification passes correct parameters', function () {
    $user = User::factory()->create([
        'first_login_at' => null,
    ]);
    $user->assignRole('super_admin');

    $mockSender = mock(SendsNotifications::class);
    $mockSender->shouldReceive('execute')
        ->once()
        ->withArgs(fn ($userId, $type, $title, $message) => $userId === $user->id && $type === 'welcome' && $title === __('notifications.welcome_to_dashboard.title') && $message === __('notifications.welcome_to_dashboard.message'));

    $listener = new SendSuperAdminWelcomeNotification($mockSender);
    $listener->handle(new LoginSucceeded($user, $user->email));
});
