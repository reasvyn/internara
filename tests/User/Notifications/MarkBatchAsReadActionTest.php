<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Notifications\Actions\MarkBatchAsReadAction;
use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('marks specified unread notifications as read', function () {
    $user = User::factory()->create();
    $notifications = Notification::factory()->count(3)->unread()->create(['user_id' => $user->id]);
    $ids = $notifications->pluck('id')->toArray();

    $action = app(MarkBatchAsReadAction::class);
    $updated = $action->execute($user->id, $ids);

    expect($updated)->toBe(3);
    expect(Notification::whereIn('id', $ids)->where('is_read', true)->count())->toBe(3);
});

test('ignores notifications belonging to other users', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $otherNotification = Notification::factory()->unread()->create(['user_id' => $userB->id]);

    $action = app(MarkBatchAsReadAction::class);
    $updated = $action->execute($userA->id, [$otherNotification->id]);

    expect($updated)->toBe(0);
    expect($otherNotification->fresh()->is_read)->toBeFalse();
});

test('ignores already read notifications', function () {
    $user = User::factory()->create();
    $read = Notification::factory()->read()->create(['user_id' => $user->id]);

    $action = app(MarkBatchAsReadAction::class);
    $updated = $action->execute($user->id, [$read->id]);

    expect($updated)->toBe(0);
});

test('returns zero for empty ids array', function () {
    $user = User::factory()->create();

    $action = app(MarkBatchAsReadAction::class);
    $updated = $action->execute($user->id, []);

    expect($updated)->toBe(0);
});

test('clears unread notification cache', function () {
    $user = User::factory()->create();
    $notifications = Notification::factory()->count(2)->unread()->create(['user_id' => $user->id]);
    $ids = $notifications->pluck('id')->toArray();

    Cache::put(config('cache-keys.notification_unread').$user->id, 2, 600);

    $action = app(MarkBatchAsReadAction::class);
    $action->execute($user->id, $ids);

    expect(Cache::has(config('cache-keys.notification_unread').$user->id))->toBeFalse();
});
