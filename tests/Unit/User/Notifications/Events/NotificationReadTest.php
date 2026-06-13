<?php

declare(strict_types=1);

use App\User\Notifications\Events\NotificationRead;
use App\User\Notifications\Models\Notification;

test('has event name notification.read', function () {
    $notification = mock(Notification::class);

    $event = new NotificationRead($notification);

    expect($event->eventName())->toBe('notification.read');
});

test('exposes notification publicly', function () {
    $notification = mock(Notification::class);

    $event = new NotificationRead($notification);

    expect($event->notification)->toBe($notification);
});
