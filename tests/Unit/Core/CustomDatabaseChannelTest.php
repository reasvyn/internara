<?php

declare(strict_types=1);

use App\Domain\Core\Channels\CustomDatabaseChannel;
use App\Domain\Core\Contracts\SendsNotifications;
use Illuminate\Notifications\Notification;

class TestCustomDatabaseNotification extends Notification
{
    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'test_event',
            'title' => 'Test Title',
            'message' => 'Test message body',
            'data' => ['key' => 'value'],
            'link' => '/test',
        ];
    }
}

class TestPlainNotification extends Notification {}

describe('CustomDatabaseChannel', function () {
    it('sends notification via SendsNotifications contract', function () {
        $sender = Mockery::mock(SendsNotifications::class);
        $sender->shouldReceive('execute')
            ->with(
                'user-1',
                'test_event',
                'Test Title',
                'Test message body',
                ['key' => 'value'],
                '/test',
            )
            ->once();

        $channel = new CustomDatabaseChannel($sender);
        $notifiable = new stdClass;
        $notifiable->id = 'user-1';

        $channel->send($notifiable, new TestCustomDatabaseNotification);
    });

    it('does nothing when notification lacks toCustomDatabase method', function () {
        $sender = Mockery::mock(SendsNotifications::class);
        $sender->shouldNotReceive('execute');

        $channel = new CustomDatabaseChannel($sender);
        $notifiable = new stdClass;
        $notifiable->id = 'user-1';

        $channel->send($notifiable, new TestPlainNotification);

        expect(true)->toBeTrue();
    });

    it('passes empty user id to sender when id is empty', function () {
        $sender = Mockery::mock(SendsNotifications::class);
        $sender->shouldReceive('execute')
            ->with('', 'test_event', 'Test Title', 'Test message body', ['key' => 'value'], '/test')
            ->once();

        $channel = new CustomDatabaseChannel($sender);
        $notifiable = new stdClass;
        $notifiable->id = '';

        $channel->send($notifiable, new TestCustomDatabaseNotification);
    });
});
