<?php

declare(strict_types=1);

use App\Core\Channels\CustomDatabaseChannel;
use App\Program\Internship\Notifications\InternshipCreatedNotification;
use App\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('implements should queue', function () {
    $notification = new InternshipCreatedNotification('PKL 2025', 'Admin');

    expect($notification)->toBeInstanceOf(ShouldQueue::class);
});

test('via returns expected channels', function () {
    $notification = new InternshipCreatedNotification('PKL 2025');
    $user = User::factory()->make();

    $channels = $notification->via($user);

    expect($channels)->toBe(['mail', 'broadcast', CustomDatabaseChannel::class]);
});

test('stores constructor values', function () {
    $notification = new InternshipCreatedNotification('PKL 2025', 'Admin');

    expect($notification->internshipName)->toBe('PKL 2025');
    expect($notification->createdByName)->toBe('Admin');
});

test('created by defaults to null', function () {
    $notification = new InternshipCreatedNotification('PKL 2025');

    expect($notification->createdByName)->toBeNull();
});

test('to mail returns mail message', function () {
    $notification = new InternshipCreatedNotification('PKL 2025', 'Admin');
    $notifiable = new class
    {
        public string $name = 'User';
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->subject)->toBe(__('notifications.internship_created.mail_subject'));
    expect($mail->greeting)->toBe(
        __('notifications.welcome.mail_greeting', ['name' => 'User']),
    );
});

test('to mail contains internship name', function () {
    $notification = new InternshipCreatedNotification('PKL 2025');
    $notifiable = new class
    {
        public string $name = 'User';
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->introLines)->toContain(
        __('notifications.internship_created.mail_line1', ['name' => 'PKL 2025']),
    );
});

test('to mail has action button', function () {
    $notification = new InternshipCreatedNotification('PKL 2025');
    $notifiable = new class
    {
        public string $name = 'User';
    };

    $mail = $notification->toMail($notifiable);

    expect($mail->actionText)->toBe(__('notifications.internship_created.mail_action'));
    expect($mail->actionUrl)->toBe(url('/admin/internships'));
});

test('to broadcast returns correct structure', function () {
    $notification = new InternshipCreatedNotification('PKL 2025');
    $user = User::factory()->make();

    $broadcast = $notification->toBroadcast($user);

    expect($broadcast['title'])->toBe(__('notifications.internship_created.title'));
    expect($broadcast['message'])->toBe(
        __('notifications.internship_created.broadcast', ['name' => 'PKL 2025']),
    );
    expect($broadcast['link'])->toBe('/admin/internships');
});

test('to custom database returns correct structure', function () {
    $notification = new InternshipCreatedNotification('PKL 2025', 'Admin');
    $user = User::factory()->make();

    $database = $notification->toCustomDatabase($user);

    expect($database['type'])->toBe('internship_created');
    expect($database['title'])->toBe(__('notifications.internship_created.title'));
    expect($database['data']['internship_name'])->toBe('PKL 2025');
    expect($database['data']['created_by'])->toBe('Admin');
    expect($database['link'])->toBe('/admin/internships');
});
