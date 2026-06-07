<?php

declare(strict_types=1);

use App\Core\Events\BaseEvent;
use Illuminate\Database\Eloquent\Model;

class MockLogEvent extends BaseEvent
{
    public function __construct(public readonly string $name, public readonly int $count) {}

    public function eventName(): string
    {
        return 'internship_created';
    }
}

class MockEventWithModel extends BaseEvent
{
    public function __construct(
        public readonly string $action,
        public readonly Model $subject,
        public readonly array $extra = [],
    ) {}

    public function eventName(): string
    {
        return 'user_updated';
    }
}

class MockEventWithObjectToArray extends BaseEvent
{
    public function __construct(
        public readonly string $event,
        public readonly MockMetadata $metadata,
    ) {}

    public function eventName(): string
    {
        return 'file_uploaded';
    }
}

class MockMetadata
{
    public function __construct(public string $fileName, public int $size) {}

    public function toArray(): array
    {
        return [
            'file_name' => $this->fileName,
            'size' => $this->size,
        ];
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

test('base event to payload extracts model as id', function () {
    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getKey')->andReturn('uuid-123');

    $event = new MockEventWithModel('update', $model);

    $payload = $event->toPayload();

    expect($payload)->toHaveKey('action');
    expect($payload)->toHaveKey('subject_id');
    expect($payload['action'])->toBe('update');
    expect($payload['subject_id'])->toBe('uuid-123');
    expect($payload)->not->toHaveKey('subject');
});

test('base event to payload extracts object with to array', function () {
    $metadata = new MockMetadata('doc.pdf', 1024);
    $event = new MockEventWithObjectToArray('upload', $metadata);

    $payload = $event->toPayload();

    expect($payload)->toHaveKey('event');
    expect($payload)->toHaveKey('metadata');
    expect($payload['event'])->toBe('upload');
    expect($payload['metadata'])->toBe([
        'file_name' => 'doc.pdf',
        'size' => 1024,
    ]);
});

test('base event to payload includes extra array properties', function () {
    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getKey')->andReturn('abc');

    $event = new MockEventWithModel('update', $model, ['reason' => 'test']);

    $payload = $event->toPayload();

    expect($payload)->toHaveKey('extra');
    expect($payload['extra'])->toBe(['reason' => 'test']);
});
