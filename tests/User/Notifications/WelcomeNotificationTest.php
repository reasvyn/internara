<?php

declare(strict_types=1);

use App\Core\Channels\CustomDatabaseChannel;
use App\User\Notifications\WelcomeNotification;

test('via returns mail, broadcast, and database channels', function () {
    $notification = new WelcomeNotification;

    $channels = $notification->via(new stdClass);

    expect($channels)->toContain('mail');
    expect($channels)->toContain('broadcast');
    expect($channels)->toContain(CustomDatabaseChannel::class);
});

test('toBroadcast returns structured array', function () {
    $notification = new WelcomeNotification;

    $result = $notification->toBroadcast(new stdClass);

    expect($result)->toHaveKeys(['title', 'message', 'link']);
    expect($result['link'])->toBe('/profile');
});

test('toCustomDatabase returns structured array', function () {
    $notification = new WelcomeNotification;

    $result = $notification->toCustomDatabase(new stdClass);

    expect($result)->toMatchArray([
        'type' => 'system_welcome',
        'link' => '/profile',
        'data' => [],
    ]);
});

test('stores temporary password', function () {
    $notification = new WelcomeNotification(temporaryPassword: 'temp123');

    expect($notification->temporaryPassword)->toBe('temp123');
});

test('stores empty password by default', function () {
    $notification = new WelcomeNotification;

    expect($notification->temporaryPassword)->toBe('');
});
