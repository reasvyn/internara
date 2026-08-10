<?php

declare(strict_types=1);

use App\Core\Channels\CustomDatabaseChannel;
use App\Core\Contracts\SendsNotifications;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Notification;

final class CustomDatabaseChannelTestSendsNotifications implements SendsNotifications
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

final class CustomDatabaseChannelTestNotification extends Notification
{
    public function __construct(public array $payload) {}

    public function via(mixed $notifiable): array
    {
        return [CustomDatabaseChannel::class];
    }

    public function toCustomDatabase(mixed $notifiable): array
    {
        return $this->payload;
    }
}

final class CustomDatabaseChannelTestPlainNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return [CustomDatabaseChannel::class];
    }
}

uses(LazilyRefreshDatabase::class);

it('FR-EV*: send forwards full notification payload to SendsNotifications', function () {
    $sender = new CustomDatabaseChannelTestSendsNotifications;
    $channel = new CustomDatabaseChannel($sender);
    $user = User::factory()->create();

    $channel->send($user, new CustomDatabaseChannelTestNotification([
        'type' => 'info',
        'title' => 'Welcome',
        'message' => 'Hello there',
        'data' => ['key' => 'value'],
        'link' => '/dashboard',
    ]));

    expect($sender->calls)->toHaveCount(1);
    expect($sender->calls[0])->toBe([
        'userId' => (string) $user->id,
        'type' => 'info',
        'title' => 'Welcome',
        'message' => 'Hello there',
        'data' => ['key' => 'value'],
        'link' => '/dashboard',
    ]);
});

it('FR-EV*: defaults type and title when missing and logs warnings', function () {
    $logs = captureLogs();
    $sender = new CustomDatabaseChannelTestSendsNotifications;
    $channel = new CustomDatabaseChannel($sender);
    $user = User::factory()->create();

    $channel->send($user, new CustomDatabaseChannelTestNotification(['message' => 'No metadata']));

    expect($sender->calls)->toHaveCount(1);
    expect($sender->calls[0]['type'])->toBe('general');
    expect($sender->calls[0]['title'])->toBe('Notification');

    $warnings = $logs->where('level', 'warning')->pluck('message');
    expect($warnings)->toContain('Notification missing type key');
    expect($warnings)->toContain('Notification missing title key');
});

it('FR-EV*: is a no-op when the notification has no toCustomDatabase method', function () {
    $sender = new CustomDatabaseChannelTestSendsNotifications;
    $channel = new CustomDatabaseChannel($sender);
    $user = User::factory()->create();

    $channel->send($user, new CustomDatabaseChannelTestPlainNotification);

    expect($sender->calls)->toBe([]);
});

it('FR-EV*: is a no-op when the notifiable has no id', function () {
    $sender = new CustomDatabaseChannelTestSendsNotifications;
    $channel = new CustomDatabaseChannel($sender);

    $channel->send(new User, new CustomDatabaseChannelTestNotification(['type' => 'info']));

    expect($sender->calls)->toBe([]);
});
