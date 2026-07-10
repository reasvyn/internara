<?php

declare(strict_types=1);

use App\Auth\SuperAdmin\Notifications\SuperAdminRecoveredNotification;
use App\Core\Channels\CustomDatabaseChannel;
use Illuminate\Contracts\Queue\ShouldQueue;

test('super admin recovered notification is sent via mail broadcast and custom database', function () {
    $notification = new SuperAdminRecoveredNotification('admin@test.com');

    expect($notification->via(notifiable: new stdClass))->toBe([
        'mail',
        'broadcast',
        CustomDatabaseChannel::class,
    ]);
});

test('super admin recovered notification implements should queue', function () {
    $notification = new SuperAdminRecoveredNotification('admin@test.com');

    expect($notification)->toBeInstanceOf(ShouldQueue::class);
});

test('super admin recovered notification stores recovered email', function () {
    $notification = new SuperAdminRecoveredNotification('admin@test.com');

    expect($notification->recoveredEmail)->toBe('admin@test.com');
});

test('super admin recovered notification builds mail message', function () {
    $notification = new SuperAdminRecoveredNotification('admin@test.com');
    $notifiable = new class
    {
        public string $name = 'Admin User';

        public function routeNotificationForMail(): string
        {
            return 'admin@test.com';
        }
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->subject)->toBe(__('notifications.super_admin_recovered.mail_subject'));
    expect($mail->greeting)->toBe(
        __('notifications.super_admin_recovered.mail_greeting', ['name' => 'Admin User']),
    );
});

test('super admin recovered notification mail contains recovered email', function () {
    $notification = new SuperAdminRecoveredNotification('recovered@test.com');
    $notifiable = new class
    {
        public string $name = 'Admin';

        public function routeNotificationForMail(): string
        {
            return 'admin@test.com';
        }
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->introLines)->toContain(
        __('notifications.super_admin_recovered.mail_line1', ['email' => 'recovered@test.com']),
    );
});

test('super admin recovered notification mail has action button', function () {
    $notification = new SuperAdminRecoveredNotification('admin@test.com');
    $notifiable = new class
    {
        public string $name = 'Admin';

        public function routeNotificationForMail(): string
        {
            return 'admin@test.com';
        }
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->actionText)->toBe(__('notifications.super_admin_recovered.mail_action'));
    expect($mail->actionUrl)->toBe(url('/admin/users'));
});

test('super admin recovered notification builds broadcast payload', function () {
    $notification = new SuperAdminRecoveredNotification('recovered@test.com');

    $broadcast = $notification->toBroadcast(new stdClass);

    expect($broadcast['title'])->toBe(__('notifications.super_admin_recovered.title'));
    expect($broadcast['message'])->toBe(
        __('notifications.super_admin_recovered.broadcast', ['email' => 'recovered@test.com']),
    );
    expect($broadcast['link'])->toBe('/admin/users');
});

test('super admin recovered notification builds custom database payload', function () {
    $notification = new SuperAdminRecoveredNotification('recovered@test.com');

    $database = $notification->toCustomDatabase(new stdClass);

    expect($database['type'])->toBe('super_admin_recovery');
    expect($database['title'])->toBe(__('notifications.super_admin_recovered.title'));
    expect($database['data']['recovered_email'])->toBe('recovered@test.com');
});
