<?php

declare(strict_types=1);

use App\Auth\SuperAdmin\Notifications\RecoveryOtpNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

test('recovery OTP notification is sent via mail', function () {
    Notification::fake();

    $notification = new RecoveryOtpNotification('123456');

    expect($notification->otp)->toBe('123456');
    expect($notification->via(notifiable: new stdClass))->toBe(['mail']);
});

test('recovery OTP notification implements should queue', function () {
    $notification = new RecoveryOtpNotification('123456');

    expect($notification)->toBeInstanceOf(ShouldQueue::class);
});

test('recovery OTP notification builds mail message', function () {
    $notification = new RecoveryOtpNotification('654321');
    $notifiable = new class
    {
        public function routeNotificationForMail(): string
        {
            return 'test@example.com';
        }
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->subject)->toBe(__('notifications.recovery_otp.mail_subject'));
    expect($mail->greeting)->toBe(__('notifications.recovery_otp.mail_greeting'));
});

test('recovery OTP notification contains the OTP in mail body', function () {
    $notification = new RecoveryOtpNotification('987654');
    $notifiable = new class
    {
        public function routeNotificationForMail(): string
        {
            return 'test@example.com';
        }
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->introLines)->toContain('987654');
});

test('recovery OTP notification can be built via facade', function () {
    $notification = new RecoveryOtpNotification('000000');

    expect($notification->otp)->toBe('000000');
});
