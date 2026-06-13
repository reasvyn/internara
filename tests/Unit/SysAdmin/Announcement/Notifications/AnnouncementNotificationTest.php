<?php

declare(strict_types=1);

use App\Core\Channels\CustomDatabaseChannel;
use App\SysAdmin\Announcement\Notifications\AnnouncementNotification;
use App\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;

uses(LazilyRefreshDatabase::class);

test('implements should queue', function () {
    $notification = new AnnouncementNotification('Title', 'Message');

    expect($notification)->toBeInstanceOf(ShouldQueue::class);
});

test('via returns expected channels', function () {
    $notification = new AnnouncementNotification('Title', 'Message');
    $user = User::factory()->make();

    $channels = $notification->via($user);

    expect($channels)->toBe(['mail', 'broadcast', CustomDatabaseChannel::class]);
});

test('to mail returns mail message with subject', function () {
    $notification = new AnnouncementNotification('Test Subject', 'Test body');
    $user = User::factory()->make();

    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->toBe('Test Subject');
});

test('to mail includes link when provided', function () {
    $notification = new AnnouncementNotification('Subject', 'Body', 'https://example.com');
    $user = User::factory()->make();

    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class);
});

test('to broadcast returns array with notification data', function () {
    $notification = new AnnouncementNotification('Title', 'Message', 'https://example.com');
    $user = User::factory()->make();

    $data = $notification->toBroadcast($user);

    expect($data)->toBe([
        'title' => 'Title',
        'message' => 'Message',
        'link' => 'https://example.com',
    ]);
});

test('to custom database returns array with announcement data', function () {
    $notification = new AnnouncementNotification('Title', 'Message', 'https://example.com');
    $user = User::factory()->make();

    $data = $notification->toCustomDatabase($user);

    expect($data)->toHaveKeys(['type', 'title', 'message', 'link', 'data']);
    expect($data['type'])->toBe('announcement');
});
