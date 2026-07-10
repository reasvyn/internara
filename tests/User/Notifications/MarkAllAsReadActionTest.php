<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Notifications\Actions\MarkAllAsReadAction;
use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('marks all unread notifications as read', function () {
    $user = User::factory()->create();

    Notification::factory()->count(3)->unread()->create(['user_id' => $user->id]);

    $action = app(MarkAllAsReadAction::class);
    $updated = $action->execute($user->id);

    expect($updated)->toBe(3);
    expect(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
});

test('does not modify already read notifications', function () {
    $user = User::factory()->create();

    Notification::factory()->count(2)->unread()->create(['user_id' => $user->id]);
    Notification::factory()->count(3)->read()->create(['user_id' => $user->id]);

    $action = app(MarkAllAsReadAction::class);
    $updated = $action->execute($user->id);

    expect($updated)->toBe(2);
});

test('returns zero when no unread notifications exist', function () {
    $user = User::factory()->create();

    $action = app(MarkAllAsReadAction::class);
    $updated = $action->execute($user->id);

    expect($updated)->toBe(0);
});

test('sets read_at timestamp on marked notifications', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->unread()->create(['user_id' => $user->id]);

    $action = app(MarkAllAsReadAction::class);
    $action->execute($user->id);

    $notification->refresh();
    expect($notification->read_at)->not->toBeNull();
});

test('clears unread notification cache', function () {
    $user = User::factory()->create();
    Notification::factory()->unread()->create(['user_id' => $user->id]);

    Cache::put(config('cache-keys.notification_unread').$user->id, 1, 600);

    $action = app(MarkAllAsReadAction::class);
    $action->execute($user->id);

    expect(Cache::has(config('cache-keys.notification_unread').$user->id))->toBeFalse();
});

test('only marks notifications for the specified user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Notification::factory()->unread()->create(['user_id' => $userA->id]);
    Notification::factory()->unread()->create(['user_id' => $userB->id]);

    $action = app(MarkAllAsReadAction::class);
    $action->execute($userA->id);

    expect(Notification::where('user_id', $userB->id)->where('is_read', false)->count())->toBe(1);
});
