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
    expect(true)->toBeTrue(); // Prevent Pest from complaining about no assertions
});
