<?php

declare(strict_types=1);

use App\Core\Channels\CustomDatabaseChannel;
use App\Core\Contracts\SendsNotifications;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

function createNotifiable(string $id): Model
{
    $model = new class extends Model
    {
        protected $guarded = [];

        protected $table = 'test';

        public function getIncrementing(): bool
        {
            return false;
        }

        public function getKeyType(): string
        {
            return 'string';
        }
    };
    $model->setAttribute($model->getKeyName(), $id);
    $model->exists = true;

    return $model;
}

beforeEach(function () {
    $this->sender = Mockery::mock(SendsNotifications::class);
    $this->channel = new CustomDatabaseChannel($this->sender);
    Log::shouldReceive('warning')->byDefault();
    Log::shouldReceive('info')->byDefault();
    Log::shouldReceive('error')->byDefault();
});

test('send calls toCustomDatabase and delegates to sender', function () {
    $notifiable = createNotifiable('uuid-123');

    $notification = new class extends Notification
    {
        public function toCustomDatabase(object $notifiable): array
        {
            return [
                'type' => 'test_type',
                'title' => 'Test Title',
                'message' => 'Test message',
                'data' => ['key' => 'value'],
                'link' => '/test',
            ];
        }
    };

    $this->sender->shouldReceive('execute')
        ->once()
        ->with(
            'uuid-123',
            'test_type',
            'Test Title',
            'Test message',
            ['key' => 'value'],
            '/test',
        );

    $this->channel->send($notifiable, $notification);
});

test('send skips when notification lacks toCustomDatabase method', function () {
    $notifiable = createNotifiable('uuid-456');
    $notification = new class extends Notification {};

    $this->sender->shouldNotReceive('execute');

    $this->channel->send($notifiable, $notification);
});

test('send skips when notifiable has no id', function () {
    $notifiable = new class extends Model
    {
        protected $guarded = [];

        protected $table = 'test';
    };

    $notification = new class extends Notification
    {
        public function toCustomDatabase(object $notifiable): array
        {
            return ['type' => 't', 'title' => 'T'];
        }
    };

    $this->sender->shouldNotReceive('execute');

    $this->channel->send($notifiable, $notification);
});

test('send uses defaults when type and title are missing', function () {
    $notifiable = createNotifiable('uuid-789');

    $notification = new class extends Notification
    {
        public function toCustomDatabase(object $notifiable): array
        {
            return [];
        }
    };

    $this->sender->shouldReceive('execute')
        ->once()
        ->with(
            'uuid-789',
            'general',
            'Notification',
            null,
            null,
            null,
        );

    $this->channel->send($notifiable, $notification);
});
