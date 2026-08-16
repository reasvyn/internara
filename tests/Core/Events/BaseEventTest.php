<?php

declare(strict_types=1);

use App\Core\Events\BaseEvent;
use Illuminate\Database\Eloquent\Model;

final class TestEventSubject extends Model
{
    protected $table = 'test_subjects';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';
}

final class TestSerializablePayload
{
    public function toArray(): array
    {
        return ['nested' => 'value'];
    }
}

final class TestEvent extends BaseEvent
{
    public function __construct(
        public TestEventSubject $subject,
        public string $action,
        public ?string $note = null,
        public TestSerializablePayload $extra = new TestSerializablePayload,
    ) {}

    public function eventName(): string
    {
        return 'test.subject_updated';
    }
}

test('NUCY3-FR-EV3: eventName() returns a dot-notation {entity}.{past_tense_action} string', function () {
    $event = new TestEvent(new TestEventSubject, 'updated');

    expect($event->eventName())->toBeString();
    expect($event->eventName())->toMatch('/^[a-z]+(\.[a-z_]+)+$/');
});

test('NUCY3-FR-EV4: toPayload() converts model properties to {name}_id keys', function () {
    $subject = new TestEventSubject;
    $subject->setAttribute('id', 'uuid-1');

    $payload = (new TestEvent($subject, 'updated'))->toPayload();

    expect($payload['subject_id'])->toBe('uuid-1');
    expect($payload)->not->toHaveKey('subject');
});

test('NUCY3-FR-EV11: toPayload() keeps scalars as-is and skips nulls', function () {
    $subject = new TestEventSubject;
    $subject->setAttribute('id', 'uuid-1');

    $payload = (new TestEvent($subject, 'updated', note: null))->toPayload();

    expect($payload['action'])->toBe('updated');
    expect($payload)->not->toHaveKey('note');
});

test('NUCY3-FR-EV11: toPayload() serializes objects that expose toArray()', function () {
    $subject = new TestEventSubject;
    $subject->setAttribute('id', 'uuid-1');

    $payload = (new TestEvent($subject, 'updated'))->toPayload();

    expect($payload['extra'])->toBe(['nested' => 'value']);
});

test('NUCY3-FR-EV2: event constructor uses typed promoted properties', function () {
    $event = new TestEvent(new TestEventSubject, 'updated');

    expect((new ReflectionClass($event))->getConstructor())->not->toBeNull();
    expect($event->action)->toBe('updated');
});
