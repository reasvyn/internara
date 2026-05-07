<?php

declare(strict_types=1);

use App\Entities\Notification\NotificationStatus;

it('detects unread notification', function () {
    $entity = new NotificationStatus(false);

    expect($entity->isUnread())->toBeTrue();
});

it('detects read notification', function () {
    $entity = new NotificationStatus(true);

    expect($entity->isUnread())->toBeFalse();
});
