<?php

declare(strict_types=1);

use App\Core\Events\BaseEvent;

class MockLogEvent extends BaseEvent
{
    public function __construct(
        public readonly string $name,
        public readonly int $count,
    ) {}

    public function eventName(): string
    {
        return 'internship_created';
    }
}

test('base event returns event name', function () {
    $event = new MockLogEvent('Test', 5);

    expect($event->eventName())->toBe('internship_created');
});

test('base event to payload extracts public properties', function () {
    $event = new MockLogEvent('Test', 5);

    expect($event->toPayload())->toBe([
        'name' => 'Test',
        'count' => 5,
    ]);
});

test('base event is dispatchable', function () {
    expect(method_exists(MockLogEvent::class, 'dispatch'))->toBeTrue();
});
