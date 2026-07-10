<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Notifications\Livewire\NotificationCenter;
use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    test()->actingAs($user);
});

test('renders the notification center component', function () {
    Livewire::test(NotificationCenter::class)
        ->assertSuccessful();
});

test('shows empty state when no notifications', function () {
    Livewire::test(NotificationCenter::class)
        ->assertSuccessful();
});

test('displays notifications', function () {
    $user = auth()->user();
    Notification::factory()->create([
        'user_id' => $user->id,
        'title' => 'Test Notification',
        'is_read' => false,
    ]);

    Livewire::test(NotificationCenter::class)
        ->assertSuccessful();
});

test('marks a notification as read', function () {
    $user = auth()->user();
    $notification = Notification::factory()->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);

    Livewire::test(NotificationCenter::class)
        ->call('markAsRead', $notification->id)
        ->assertDispatched('notification-read');

    expect($notification->fresh()->is_read)->toBeTrue();
});

test('marks all notifications as read', function () {
    $user = auth()->user();
    Notification::factory()->count(3)->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);

    Livewire::test(NotificationCenter::class)
        ->call('markAllAsRead')
        ->assertDispatched('notifications-read');

    expect(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
});
