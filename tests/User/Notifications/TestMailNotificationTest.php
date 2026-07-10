<?php

declare(strict_types=1);

use App\User\Notifications\TestMailNotification;
use Illuminate\Notifications\Messages\MailMessage;

test('via returns only mail channel', function () {
    $notification = new TestMailNotification;

    $channels = $notification->via(new stdClass);

    expect($channels)->toBe(['mail']);
});

test('toMail returns mail message', function () {
    $notification = new TestMailNotification;

    $mail = $notification->toMail(new stdClass);

    expect($mail)->toBeInstanceOf(MailMessage::class);
});
