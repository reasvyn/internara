<?php

declare(strict_types=1);

use App\Core\Contracts\SendsNotifications;

class MockNotificationSender implements SendsNotifications
{
    public function __construct(public mixed $lastResult = null) {}

    public function execute(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $link = null,
    ): mixed {
        return $this->lastResult ?? true;
    }
}

test('sends notifications contract can be implemented', function () {
    $sender = new MockNotificationSender;

    expect($sender)->toBeInstanceOf(SendsNotifications::class);
});

test('sends notifications execute returns result', function () {
    $sender = new MockNotificationSender(lastResult: 'sent-123');

    $result = $sender->execute('user-1', 'info', 'Hello', 'World');

    expect($result)->toBe('sent-123');
});

test('sends notifications execute accepts all parameters', function () {
    $sender = new class implements SendsNotifications
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
            $this->calls[] = [
                'userId' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'link' => $link,
            ];

            return true;
        }
    };

    $sender->execute('uid', 'alert', 'Alert!', 'Message body', ['key' => 'val'], '/link');
    $sender->execute('uid2', 'info', 'Info');

    expect($sender->calls)->toHaveCount(2);
    expect($sender->calls[0])->toBe([
        'userId' => 'uid',
        'type' => 'alert',
        'title' => 'Alert!',
        'message' => 'Message body',
        'data' => ['key' => 'val'],
        'link' => '/link',
    ]);
    expect($sender->calls[1])->toBe([
        'userId' => 'uid2',
        'type' => 'info',
        'title' => 'Info',
        'message' => null,
        'data' => null,
        'link' => null,
    ]);
});

test('sends notifications execute handles null optional parameters', function () {
    $sender = new MockNotificationSender;

    $result = $sender->execute(userId: 'user-1', type: 'warning', title: 'Warning!');

    expect($result)->toBeTrue();
});
