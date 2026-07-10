<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Notifications\Actions\DeleteNotificationAction;
use App\User\Notifications\Models\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes a notification', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $user->id]);

    $action = app(DeleteNotificationAction::class);
    $action->execute($notification);

    $this->assertModelMissing($notification);
});

test('clears unread notification cache after deletion', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->unread()->create(['user_id' => $user->id]);

    Cache::put(config('cache-keys.notification_unread').$user->id, 1, 600);

    $action = app(DeleteNotificationAction::class);
    $action->execute($notification);

    expect(Cache::has(config('cache-keys.notification_unread').$user->id))->toBeFalse();
});
