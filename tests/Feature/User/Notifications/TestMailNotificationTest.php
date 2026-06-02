<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Notifications\TestMailNotification;
use Illuminate\Notifications\Messages\MailMessage;

describe('TestMailNotification', function () {
    it('sends via mail channel', function () {
        $notification = new TestMailNotification;

        expect($notification->via(new User))->toBe(['mail']);
    });

    it('builds mail message', function () {
        $notification = new TestMailNotification;
        $user = User::factory()->make();

        $mail = $notification->toMail($user);

        expect($mail)->toBeInstanceOf(MailMessage::class)
            ->and($mail->subject)->not->toBeNull()
            ->and($mail->greeting)->not->toBeNull();
    });
});
