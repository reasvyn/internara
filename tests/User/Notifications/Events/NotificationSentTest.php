<?php

declare(strict_types=1);

use App\User\Notifications\Events\NotificationSent;
use App\User\Notifications\Models\Notification;

test('notification sent event name and payload', function () {
    $notification = new class extends Notification {};
    $notification->forceFill(['id' => 'n-1']);

    $event = new NotificationSent($notification);

    expect($event->notification->id)->toBe('n-1');
    expect($event->eventName())->toBe('notification.sent');
    expect($event->toPayload())->toHaveKey('notification_id');
});
