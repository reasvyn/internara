<?php

declare(strict_types=1);

use App\Core\Channels\CustomDatabaseChannel;
use App\Program\Notifications\RegistrationNotification;
use App\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('implements should queue', function () {
    $notification = new RegistrationNotification('PKL 2025', 'approved');

    expect($notification)->toBeInstanceOf(ShouldQueue::class);
});

test('via returns expected channels', function () {
    $notification = new RegistrationNotification('PKL 2025', 'pending');
    $user = User::factory()->make();

    $channels = $notification->via($user);

    expect($channels)->toBe(['mail', 'broadcast', CustomDatabaseChannel::class]);
});

test('stores constructor values', function () {
    $notification = new RegistrationNotification('PKL 2025', 'approved');

    expect($notification->internshipName)->toBe('PKL 2025');
    expect($notification->status)->toBe('approved');
});

test('to broadcast returns correct structure', function () {
    $notification = new RegistrationNotification('PKL 2025', 'approved');
    $user = User::factory()->make();

    $broadcast = $notification->toBroadcast($user);

    expect($broadcast['title'])->toBe(__('notifications.internship_registration.title'));
    expect($broadcast['message'])->toBe(
        __('notifications.internship_registration.message', [
            'internship' => 'PKL 2025',
            'status' => 'APPROVED',
        ]),
    );
    expect($broadcast['link'])->toBe('/student/dashboard');
});

test('to mail returns mail message', function () {
    $notification = new RegistrationNotification('PKL 2025', 'pending');
    $notifiable = new class
    {
        public string $name = 'Student';
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->subject)->toBe(__('notifications.internship_registration.mail_subject'));
    expect($mail->greeting)->toBe(
        __('notifications.welcome.mail_greeting', ['name' => 'Student']),
    );
});

test('to mail contains internship name and status', function () {
    $notification = new RegistrationNotification('PKL 2025', 'rejected');
    $notifiable = new class
    {
        public string $name = 'Student';
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->introLines)->toContain(
        __('notifications.internship_registration.mail_line1', [
            'internship' => 'PKL 2025',
            'status' => 'REJECTED',
        ]),
    );
});

test('to custom database returns correct structure', function () {
    $notification = new RegistrationNotification('PKL 2025', 'approved');
    $user = User::factory()->make();

    $database = $notification->toCustomDatabase($user);

    expect($database['type'])->toBe('internship_registration_update');
    expect($database['title'])->toBe(__('notifications.internship_registration.title'));
    expect($database['data']['internship_name'])->toBe('PKL 2025');
    expect($database['data']['status'])->toBe('approved');
    expect($database['link'])->toBe('/student/dashboard');
});
