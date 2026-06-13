<?php

declare(strict_types=1);

use App\Core\Channels\CustomDatabaseChannel;
use App\User\AccountStatus\Notifications\AccountStatusNotification;
use Illuminate\Notifications\Messages\MailMessage;

test('via returns mail, broadcast, and database channels', function () {
    $notification = new AccountStatusNotification(status: 'verified');

    $channels = $notification->via(new stdClass);

    expect($channels)->toContain('mail');
    expect($channels)->toContain('broadcast');
    expect($channels)->toContain(CustomDatabaseChannel::class);
});

test('toBroadcast returns structured array with uppercase status', function () {
    $notification = new AccountStatusNotification(status: 'suspended');

    $result = $notification->toBroadcast(new stdClass);

    expect($result)->toHaveKeys(['title', 'message', 'link']);
    expect($result['link'])->toBe('/profile');
    expect($result['message'])->toContain('SUSPENDED');
});

test('toCustomDatabase returns structured array', function () {
    $notification = new AccountStatusNotification(status: 'verified');

    $result = $notification->toCustomDatabase(new stdClass);

    expect($result)->toMatchArray([
        'type' => 'account_status_change',
        'link' => '/profile',
    ]);
    expect($result['data'])->toMatchArray([
        'status' => 'verified',
        'reason' => null,
    ]);
});

test('toCustomDatabase includes reason when provided', function () {
    $notification = new AccountStatusNotification(status: 'suspended', reason: 'Policy violation');

    $result = $notification->toCustomDatabase(new stdClass);

    expect($result['data']['reason'])->toBe('Policy violation');
});

test('toMail returns mail message with reason when provided', function () {
    $notification = new AccountStatusNotification(status: 'suspended', reason: 'Policy violation');
    $notifiable = (object) ['name' => 'Test User'];

    $mail = $notification->toMail($notifiable);

    expect($mail)->toBeInstanceOf(MailMessage::class);
});

test('toMail returns mail message without reason when not provided', function () {
    $notification = new AccountStatusNotification(status: 'verified');
    $notifiable = (object) ['name' => 'Test User'];

    $mail = $notification->toMail($notifiable);

    expect($mail)->toBeInstanceOf(MailMessage::class);
});
