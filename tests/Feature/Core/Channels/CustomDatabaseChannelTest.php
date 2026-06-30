<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Channels;

use App\Core\Channels\CustomDatabaseChannel;
use App\Core\Contracts\SendsNotifications;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Notification;

// ─── Test Doubles ────────────────────────────────────────────────────────────────────────────

class RecordingNotificationSender implements SendsNotifications
{
    public array $calls = [];

    public function execute(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $link = null,
    ): mixed {
        $this->calls[] = compact('userId', 'type', 'title', 'message', 'data', 'link');

        return null;
    }
}

class MockNotification extends Notification
{
    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'test_notif',
            'title' => 'Test Notification',
            'message' => 'Notification content',
            'data' => ['foo' => 'bar'],
            'link' => '/test-link',
        ];
    }
}

class MockNotificationNoToCustomDb extends Notification {}

class MockNotificationMissingKeys extends Notification
{
    public function toCustomDatabase($notifiable): array
    {
        return [
            'message' => 'No type or title',
        ];
    }
}

// ─── Tests ───────────────────────────────────────────────────────────────────────────────────

uses(LazilyRefreshDatabase::class);

test('it sends notifications via custom database channel', function () {
    $sender = new RecordingNotificationSender;
    $user = User::factory()->create();
    $channel = new CustomDatabaseChannel($sender);
    $notification = new MockNotification;

    $channel->send($user, $notification);

    expect($sender->calls)->toHaveCount(1);
    expect($sender->calls[0])->toMatchArray([
        'userId' => $user->id,
        'type' => 'test_notif',
        'title' => 'Test Notification',
        'message' => 'Notification content',
        'data' => ['foo' => 'bar'],
        'link' => '/test-link',
    ]);
});

test('it skips notification without to custom database method', function () {
    $sender = new RecordingNotificationSender;
    $user = User::factory()->create();
    $channel = new CustomDatabaseChannel($sender);
    $notification = new MockNotificationNoToCustomDb;

    $channel->send($user, $notification);

    expect($sender->calls)->toBeEmpty();
});

test('it skips notification when notifiable has no id', function () {
    $sender = new RecordingNotificationSender;
    $user = User::factory()->make();
    $user->setAttribute('id', null);
    $channel = new CustomDatabaseChannel($sender);
    $notification = new MockNotification;

    $channel->send($user, $notification);

    expect($sender->calls)->toBeEmpty();
});

test('it skips notification when notifiable id is empty string', function () {
    $sender = new RecordingNotificationSender;
    $user = User::factory()->make();
    $user->setAttribute('id', '');
    $channel = new CustomDatabaseChannel($sender);
    $notification = new MockNotification;

    $channel->send($user, $notification);

    expect($sender->calls)->toBeEmpty();
});

test('it uses defaults for missing type and title keys', function () {
    $sender = new RecordingNotificationSender;
    $user = User::factory()->create();
    $channel = new CustomDatabaseChannel($sender);
    $notification = new MockNotificationMissingKeys;

    $channel->send($user, $notification);

    expect($sender->calls)->toHaveCount(1);
    expect($sender->calls[0])->toMatchArray([
        'userId' => $user->id,
        'type' => 'general',
        'title' => 'Notification',
        'message' => 'No type or title',
        'data' => null,
        'link' => null,
    ]);
});

test('it sends to plain object notifiable with id property', function () {
    $sender = new RecordingNotificationSender;
    $channel = new CustomDatabaseChannel($sender);
    $notification = new MockNotification;
    $notifiable = (object) ['id' => '42'];

    $channel->send($notifiable, $notification);

    expect($sender->calls)->toHaveCount(1);
    expect($sender->calls[0]['userId'])->toBe('42');
    expect($sender->calls[0]['type'])->toBe('test_notif');
});
