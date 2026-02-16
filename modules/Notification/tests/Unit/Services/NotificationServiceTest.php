<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Modules\Notification\Services\NotificationService;

test('it delegates send to facade', function () {
    $service = new NotificationService();
    $notification = mock(Notification::class);

    NotificationFacade::shouldReceive('send')->once()->with('user', $notification);

    $service->send('user', $notification);
    expect(true)->toBeTrue();
});

test('it delegates sendNow to facade', function () {
    $service = new NotificationService();
    $notification = mock(Notification::class);

    NotificationFacade::shouldReceive('sendNow')->once()->with('user', $notification);

    $service->sendNow('user', $notification);
    expect(true)->toBeTrue();
});
