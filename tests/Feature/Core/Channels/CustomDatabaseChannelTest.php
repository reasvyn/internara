<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Channels;

use App\Core\Channels\CustomDatabaseChannel;
use App\Core\Contracts\SendsNotifications;
use App\User\Models\User;
use Illuminate\Notifications\Notification;
use Mockery;

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

test('it sends notifications via custom database channel', function () {
    $senderMock = Mockery::mock(SendsNotifications::class);
    $senderMock->shouldReceive('execute')
        ->once()
        ->with('123', 'test_notif', 'Test Notification', 'Notification content', ['foo' => 'bar'], '/test-link');

    $notifiable = Mockery::mock(User::class);
    $notifiable->shouldReceive('getKey')->andReturn('123');

    $channel = new CustomDatabaseChannel($senderMock);
    $notification = new MockNotification;

    $channel->send($notifiable, $notification);
    expect(true)->toBeTrue();
});

test('it skips notification without to custom database method', function () {
    $senderMock = Mockery::mock(SendsNotifications::class);
    $senderMock->shouldNotReceive('execute');

    $notifiable = Mockery::mock(User::class);
    $notifiable->shouldReceive('getKey')->andReturn('123');

    $channel = new CustomDatabaseChannel($senderMock);
    $notification = new MockNotificationNoToCustomDb;

    $channel->send($notifiable, $notification);
    expect(true)->toBeTrue();
});

test('it skips notification when notifiable has no id', function () {
    $senderMock = Mockery::mock(SendsNotifications::class);
    $senderMock->shouldNotReceive('execute');

    $notifiable = Mockery::mock(User::class);
    $notifiable->shouldReceive('getKey')->andReturn(null);

    $channel = new CustomDatabaseChannel($senderMock);
    $notification = new MockNotification;

    $channel->send($notifiable, $notification);
    expect(true)->toBeTrue();
});

test('it skips notification when notifiable id is empty string', function () {
    $senderMock = Mockery::mock(SendsNotifications::class);
    $senderMock->shouldNotReceive('execute');

    $notifiable = Mockery::mock(User::class);
    $notifiable->shouldReceive('getKey')->andReturn('');

    $channel = new CustomDatabaseChannel($senderMock);
    $notification = new MockNotification;

    $channel->send($notifiable, $notification);
    expect(true)->toBeTrue();
});

test('it uses defaults for missing type and title keys', function () {
    $senderMock = Mockery::mock(SendsNotifications::class);
    $senderMock->shouldReceive('execute')
        ->once()
        ->with('123', 'general', 'Notification', 'No type or title', null, null);

    $notifiable = Mockery::mock(User::class);
    $notifiable->shouldReceive('getKey')->andReturn('123');

    $channel = new CustomDatabaseChannel($senderMock);
    $notification = new MockNotificationMissingKeys;

    $channel->send($notifiable, $notification);
    expect(true)->toBeTrue();
});

test('it sends to plain object notifiable with id property', function () {
    $senderMock = Mockery::mock(SendsNotifications::class);
    $senderMock->shouldReceive('execute')
        ->once()
        ->with('42', 'test_notif', 'Test Notification', 'Notification content', ['foo' => 'bar'], '/test-link');

    $notifiable = (object) ['id' => '42'];

    $channel = new CustomDatabaseChannel($senderMock);
    $notification = new MockNotification;

    $channel->send($notifiable, $notification);
    expect(true)->toBeTrue();
});
