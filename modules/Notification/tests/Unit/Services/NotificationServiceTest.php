<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Notification\Services\NotificationService;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

test('it can send notifications to recipients', function () {
    Notification::fake();

    $user = User::factory()->create();
    $service = new NotificationService();
    $notification = new \Modules\User\Notifications\WelcomeUserNotification('password123');

    $service->send($user, $notification);

    Notification::assertSentTo($user, \Modules\User\Notifications\WelcomeUserNotification::class);
});

test('it can send notifications immediately', function () {
    Notification::fake();

    $user = User::factory()->create();
    $service = new NotificationService();
    $notification = new \Modules\User\Notifications\WelcomeUserNotification('password123');

    $service->sendNow($user, $notification);

    Notification::assertSentTo($user, \Modules\User\Notifications\WelcomeUserNotification::class);
});
