<?php

declare(strict_types=1);

use App\Core\Channels\CustomDatabaseChannel;
use App\User\Notifications\GeneralNotification;
use Illuminate\Notifications\Messages\MailMessage;

test('via returns database and mail channels by default', function () {
    $notification = new GeneralNotification(
        type: 'info',
        title: 'Test Title',
        message: 'Test message',
    );

    $channels = $notification->via(new stdClass);

    expect($channels)->toContain(CustomDatabaseChannel::class);
    expect($channels)->toContain('mail');
});

test('via omits mail when sendEmail is false', function () {
    $notification = new GeneralNotification(
        type: 'info',
        title: 'Test Title',
        message: 'Test message',
        sendEmail: false,
    );

    $channels = $notification->via(new stdClass);

    expect($channels)->toContain(CustomDatabaseChannel::class);
    expect($channels)->not->toContain('mail');
});

test('toCustomDatabase returns structured array', function () {
    $notification = new GeneralNotification(
        type: 'warning',
        title: 'Warning Title',
        message: 'Warning message',
        link: '/settings',
        data: ['key' => 'value'],
    );

    $result = $notification->toCustomDatabase(new stdClass);

    expect($result)->toMatchArray([
        'type' => 'warning',
        'title' => 'Warning Title',
        'message' => 'Warning message',
        'link' => '/settings',
        'data' => ['key' => 'value'],
    ]);
});

test('toCustomDatabase uses empty array for null data', function () {
    $notification = new GeneralNotification(
        type: 'info',
        title: 'Test',
        message: 'Test',
    );

    $result = $notification->toCustomDatabase(new stdClass);

    expect($result['data'])->toBe([]);
});

test('toMail returns mail message with link when provided', function () {
    $notification = new GeneralNotification(
        type: 'info',
        title: 'Test Title',
        message: 'Test message',
        link: '/dashboard',
    );

    $mail = $notification->toMail(new stdClass);

    expect($mail)->toBeInstanceOf(MailMessage::class);
});

test('toMail returns mail message without link when not provided', function () {
    $notification = new GeneralNotification(
        type: 'info',
        title: 'Test Title',
        message: 'Test message',
    );

    $mail = $notification->toMail(new stdClass);

    expect($mail)->toBeInstanceOf(MailMessage::class);
});
