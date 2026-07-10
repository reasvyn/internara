<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Notifications\Livewire\NotificationBell;
use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    test()->actingAs($user);
});

test('renders the notification bell component', function () {
    Livewire::test(NotificationBell::class)
        ->assertSuccessful();
});

test('shows zero unread count when no notifications', function () {
    Livewire::test(NotificationBell::class)
        ->assertSet('unreadCount', 0);
});

test('shows unread notification count', function () {
    $user = auth()->user();
    Notification::factory()->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);

    Livewire::test(NotificationBell::class)
        ->assertSet('unreadCount', 1);
});

test('updates unread count on notification-read event', function () {
    Livewire::test(NotificationBell::class)
        ->dispatch('notification-read')
        ->assertSet('unreadCount', 0);
});

test('updates unread count on notifications-read event', function () {
    Livewire::test(NotificationBell::class)
        ->dispatch('notifications-read')
        ->assertSet('unreadCount', 0);
});
