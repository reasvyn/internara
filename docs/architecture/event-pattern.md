# Events, Listeners & Notifications Pattern — Dispatch, Listeners & Multi-Channel

> **Last updated:** 2026-07-01 **Changes:** clarify: events are for async communication only — not
> mandatory; only create when a listener exists

## Description

Event dispatch patterns, listener registration, notification channels, ShouldQueue conventions, and
cross-module event communication.

## Design Principle: Events Are for Async Communication

Events exist to decouple **producers** (Actions) from **consumers** (listeners) across module
boundaries. They are **not** a required part of every Command Action.

**Rules:**

- Only create an event class when at least one listener needs to react to it (cache invalidation,
  cross-module notification, logging beyond `$this->log()`).
- Do NOT create events "just in case" a listener might exist in the future. Add the event when the
  listener is implemented.
- Simple CRUD operations that are logged via `$this->log()` do NOT need events.
- Status transitions that only affect the current model (no cross-module side effects) do NOT need
  events.
- Events are registered in `config/event.php`. An event without a listener registration is dead code
  — either register a listener or remove the event class.

**Before adding an event, ask:** "Is there a listener that will react to this?" If no → skip the
event. The Action's `$this->log()` provides the audit trail.

## Table of Contents

1. [Event Architecture (BaseEvent Contract)](#1-event-architecture-baseevent-contract)
2. [Event Naming Conventions](#2-event-naming-conventions)
3. [Event Payload (toPayload)](#3-event-payload-topayload)
4. [Event Dispatch Patterns](#4-event-dispatch-patterns)
5. [Listener Naming and Registration](#5-listener-naming-and-registration)
6. [ShouldQueue for Async Listeners](#6-shouldqueue-for-async-listeners)
7. [Notification Architecture](#7-notification-architecture)
8. [Notification Naming](#8-notification-naming)
9. [CustomDatabaseChannel](#9-customdatabasechannel)
10. [SendsNotifications Contract](#10-sendsnotifications-contract)
11. [SmartLogger Integration](#11-smartlogger-integration)
12. [Testing Events and Listeners](#12-testing-events-and-listeners)

---

## 1. Event Architecture (BaseEvent Contract)

All events **must** extend `BaseEvent`. The base class provides the foundational contract and a set
of built-in capabilities:

| Trait / Method            | Purpose                                                                                       |
| ------------------------- | --------------------------------------------------------------------------------------------- |
| `Dispatchable`            | Static `dispatch()` and instance `dispatch()` via `Illuminate\Foundation\Events\Dispatchable` |
| `InteractsWithSockets`    | Broadcasting scaffold (defaults to no broadcast)                                              |
| `SerializesModels`        | Safe queue serialization for Eloquent models                                                  |
| `eventName(): string`     | **Abstract** — returns a dot-notation key used by SmartLogger for log translation             |
| `toPayload(): array`      | Extracts public properties for logging, converting Models to `{name}_id`                      |
| `broadcastOn(): array`    | Returns `[]` (override to enable broadcasting)                                                |
| `shouldBroadcast(): bool` | Returns `false` (override to enable)                                                          |
| `shouldQueue(): bool`     | Returns `false` (override to queue the event itself)                                          |
| `queue(): string`         | Returns `'default'`                                                                           |

### BaseEvent Source

```php
abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    abstract public function eventName(): string;

    public function toPayload(): array
    {
        /* ... */
    }
    public function broadcastOn(): array
    {
        return [];
    }
    public function broadcastAs(): string
    {
        return $this->eventName();
    }
    public function shouldBroadcast(): bool
    {
        return false;
    }
    public function shouldQueue(): bool
    {
        return false;
    }
    public function queue(): string
    {
        return 'default';
    }
}
```

### Rules

- Events are **`final`** classes.
- Properties are `public` with typed constructor promotion.
- Only scalar types, Model instances, and objects with `toArray()` are allowed as properties.
- Events belong to the **module that emits them** (`app/{Module}/{SubModule}/Events/`).

---

## 2. Event Naming Conventions

**Pattern:** `{Entity}{PastTenseAction}`

The class name reads as a completed fact: `{Entity}Created`, `{Login}Failed`, `{Student}Registered`.

| Convention         | Example             |
| ------------------ | ------------------- |
| Entity + Created   | `{Entity}Created`   |
| Entity + Activated | `{Entity}Activated` |
| Entity + Deleted   | `{Entity}Deleted`   |
| Entity + Updated   | `{Entity}Updated`   |
| Entity + Succeeded | `{Entity}Succeeded` |
| Entity + Failed    | `{Entity}Failed`    |
| Entity + Finalized | `{Entity}Finalized` |
| Entity + Read      | `{Entity}Read`      |
| Entity + Sent      | `{Entity}Sent`      |

The `eventName()` method returns a **dot-notation key** (`{entity}.{action}`) that doubles as the
log translation key for SmartLogger.

### Generic Example

```php
final class {Entity}{Action} extends BaseEvent
{
    public function __construct(
        public Model $subject,
        public ?Model $actor = null,
    ) {}

    public function eventName(): string
    {
        return '{entity}.{action}';
    }
}
```

---

## 3. Event Payload (toPayload)

`toPayload()` extracts public properties into a flat array for SmartLogger payload merging. It
applies three rules:

| Property Type           | Output                                            |
| ----------------------- | ------------------------------------------------- |
| `Model` instance        | Converted to `{name}_id` using `$value->getKey()` |
| Object with `toArray()` | Converted via `$value->toArray()`                 |
| Scalar / array          | Passed as-is                                      |

Hidden properties: `socket` and any key starting with `__` are skipped.

### Payload Conversion

```php
$event = new {Entity}{Action}(
    subject: $model,       // Model → 'subject_id' => 'uuid-...'
    actor: $user,          // Model → 'actor_id' => 'uuid-...'
);

$event->toPayload();
// [
//   'subject_id' => '0195abc...',
//   'actor_id' => '0195def...',
// ]
```

For events with only scalar properties, the payload is a direct 1:1 map. Objects that do not
implement `toArray()` (e.g. `DateTimeImmutable`) are silently skipped. Override `toPayload()` if
needed.

---

## 4. Event Dispatch Patterns

There are three ways to dispatch events:

### 4a. Static `Event::dispatch()` / `::dispatch()`

Use when dispatching from a non-Action context or when you need the event immediately:

```php
use Illuminate\Support\Facades\Event;

Event::dispatch(new {Entity}{Action}($subject));
{Entity}{Action}::dispatch($subject);
```

### 4b. `BaseAction::dispatchEvent()` (Deferred Dispatch)

Inside Command or Process Actions, use `$this->dispatchEvent()` to **defer** dispatch until the
transaction commits. This prevents listeners from seeing uncommitted data:

```php
class {Entity}Action extends BaseAction
{
    public function execute(Model $entry, array $data): Model
    {
        return $this->transaction(function () use ($entry, $data) {
            $entry->update($data);

            $this->log('{entity}_{action}', $entry);
            $this->dispatchEvent(new {Entity}{Action}($entry));

            return $entry;
        });
    }
}
```

`BaseAction` collects deferred events in `$this->pendingEvents[]` and dispatches them via the global
`event()` helper after the `transaction()` callback completes (or after the inner closure if already
inside a transaction).

### 4c. SmartLogger `->event($baseEvent)->save()` (Auto-Dispatch + Log)

When `SmartLogger::event()` receives a `BaseEvent` instance instead of a string, `save()` will:

1. Call `event($baseEvent)` to dispatch it to listeners.
2. Merge `$event->toPayload()` into the log payload.

```php
SmartLogger::success('{Entity} {Action}')
    ->event(new {Entity}{Action}($subject))
    ->for($user)
    ->save();
```

This is equivalent to:

```php
event(new {Entity}{Action}($subject));

SmartLogger::success('{Entity} {Action}')
    ->event('{entity}_{action}')
    ->withPayload((new {Entity}{Action}($subject))->toPayload())
    ->for($user)
    ->save();
```

---

## 5. Listener Naming and Registration

### Naming

Listeners are named by what they **do**, not what event they handle:

| Name               | Event              | Action                                                |
| ------------------ | ------------------ | ----------------------------------------------------- |
| `{Action}{Entity}` | `{Entity}{Action}` | Sends notification, clears cache, logs activity, etc. |
| `{Action}{Entity}` | `{Entity}{Action}` | Clears cached settings                                |
| `{Action}{Entity}` | `{Entity}{Action}` | Invalidates per-user cache                            |

### Registration

Listeners are registered in `config/event.php` under the `listen` key:

```php
// config/event.php
return [
    'listen' => [
        {Entity}{Action}::class => [
            {Listener}::class,
        ],
    ],
];
```

The `EventServiceProvider` reads this config in `boot()`:

```php
class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $listeners = config('event.listen', []);

        foreach ($listeners as $event => $listenersArray) {
            foreach ($listenersArray as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    public static function registerListener(string $event, string $listener): void
    {
        Event::listen($event, $listener);
    }
}
```

This centralised config makes the entire event-listener mapping discoverable in one place.

---

## 6. ShouldQueue for Async Listeners

Listeners performing I/O (email, cache clear, API calls) **must** implement `ShouldQueue`. This
pushes the listener onto the queue worker, keeping the Action response fast.

```php
class {Listener} implements ShouldQueue
{
    public function handle({Entity}{Action} $event): void
    {
        // I/O-bound work (email, API calls, cache warmup)
    }
}
```

Listeners should use union types in `handle()` when they respond to multiple event types:

```php
public function handle({Entity}{Action}|{OtherEntity}{Action} $event): void
{
    // Shared side effect
}
```

**Rule of thumb:** If the listener does anything slower than a cache `forget()`, it should be
queued. Synchronous listeners are acceptable only for microsecond operations (cache forget,
in-memory state).

---

## 7. Notification Architecture

Notifications extend `Illuminate\Notifications\Notification` directly (no Core base class). They
implement the standard Laravel notification contract with a custom channel extension.

### Channel Strategy

Every notification defines its channels via `via($notifiable)`:

```php
public function via($notifiable): array
{
    return ['mail', 'broadcast', CustomDatabaseChannel::class];
}
```

| Channel                        | Delivery                     | When                                     |
| ------------------------------ | ---------------------------- | ---------------------------------------- |
| `mail`                         | Email via `MailMessage`      | Always for important notifications       |
| `broadcast`                    | Realtime via Laravel Echo    | When the user is online                  |
| `CustomDatabaseChannel::class` | In-app database notification | Always (stored in `notifications` table) |

### Notification Structure

```php
class {Entity}{Type}Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subjectName,
        public ?string $actorName = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toMail($notifiable): MailMessage { /* ... */ }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.{entity}_{type}.title'),
            'message' => __('notifications.{entity}_{type}.broadcast', [
                'name' => $this->subjectName,
            ]),
            'link' => '/{route}',
        ];
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => '{entity}_{type}',
            'title' => __('notifications.{entity}_{type}.title'),
            'message' => __('notifications.{entity}_{type}.database', [
                'name' => $this->subjectName,
            ]),
            'link' => '/{route}',
            'data' => [
                'subject_name' => $this->subjectName,
                'actor' => $this->actorName,
            ],
        ];
    }
}
```

### Sending Notifications

Notifications are sent via `Notification::send()` or `$notifiable->notify()`:

```php
Notification::send(
    $recipients,
    new {Entity}{Type}Notification(
        subjectName: $event->subject->name,
        actorName: $event->actor?->name,
    ),
);
```

### ShouldQueue for Notifications

Every notification must implement `ShouldQueue` + `use Queueable` to deliver channels through the
queue worker, except trivial synchronous notifications.

---

## 8. Notification Naming

**Pattern:** `{Entity}{NotificationType}Notification`

The class name combines the subject entity with the notification type, suffixed with `Notification`:

| Pattern                       | Entity | Type    |
| ----------------------------- | ------ | ------- |
| `{Entity}CreatedNotification` | Entity | Created |
| `{Entity}{Type}Notification`  | Entity | Type    |
| `WelcomeNotification`         | (none) | Welcome |
| `{Entity}{Type}Notification`  | Entity | Type    |

---

## 9. CustomDatabaseChannel

The `CustomDatabaseChannel` is the internal notification delivery mechanism. It writes to the
`notifications` table via `SendsNotifications`.

### toCustomDatabase Contract

Each notification must implement `toCustomDatabase($notifiable): array` returning:

| Key       | Required | Description                                               |
| --------- | -------- | --------------------------------------------------------- |
| `type`    | ✅ Yes   | Machine-readable type string (e.g. `'{entity}_{action}'`) |
| `title`   | ✅ Yes   | Human-readable title (use `__()`)                         |
| `message` | ❌ No    | Human-readable body text                                  |
| `link`    | ❌ No    | URL to navigate to                                        |
| `data`    | ❌ No    | Arbitrary metadata array                                  |

### Missing Key Warnings

If `toCustomDatabase()` omits `type` or `title`, `CustomDatabaseChannel` writes a warning via
SmartLogger (system-only) to aid debugging:

```php
if (!isset($data['type'])) {
    SmartLogger::warning('Notification missing type key')
        ->withPayload(['notification_class' => get_class($notification)])
        ->systemOnly()
        ->save();
}
```

---

## 10. SendsNotifications Contract

```php
interface SendsNotifications
{
    public function execute(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $link = null,
    ): mixed;
}
```

An action implementing this interface validates the input, creates the `Notification` model record,
and dispatches `{Entity}Sent`.

---

## 11. SmartLogger Integration

Events extending `BaseEvent` integrate with SmartLogger in two ways.

### 11a. BaseAction::log() — Event Name as Log Key

The `BaseAction::log()` method accepts a string action key that doubles as a log event name:

```php
protected function log(string $action, ?Model $subject = null, array $payload = []): void
{
    SmartLogger::info($action)
        ->event($action)
        ->module($this->moduleName())
        ->about($subject)
        ->withPayload($payload)
        ->withPiiMasking()
        ->both()
        ->save();
}
```

This writes a system log and activity log entry with the action key as the event name. It does
**not** dispatch a `BaseEvent` — use `dispatchEvent()` for that.

### 11b. SmartLogger::event() with BaseEvent Instance

When `SmartLogger::event()` receives a `BaseEvent` instance:

1. **Dispatches** the event to all registered listeners via `event($baseEvent)`.
2. **Merges** `$event->toPayload()` into the log payload (explicit `withPayload()` overrides).
3. **Resolves** the event name from `$event->eventName()` for log translation.

```php
SmartLogger::success('{Entity} {Action}')
    ->event(new {Entity}{Action}('uuid-123', 'example@test.com'))
    ->module('{Module}')
    ->activityOnly()
    ->save();
```

---

## 12. Testing Events and Listeners

### 12a. Event Dispatch Tests

Use `Event::fake()` to capture dispatched events without running listeners:

```php
use Illuminate\Support\Facades\Event;

test('{entity} {action} event is dispatched via action', function () {
    Event::fake([{Entity}{Action}::class]);

    // Execute the action that should dispatch the event

    Event::assertDispatched({Entity}{Action}::class);
});
```

### 12b. Payload Assertions

Assert the event was dispatched with specific properties:

```php
test('{entity} {action} event contains correct data', function () {
    Event::fake([{Entity}{Action}::class]);

    // Execute the action

    Event::assertDispatched({Entity}{Action}::class, function ({Entity}{Action} $event) {
        return $event->property === 'expected_value';
    });
});
```

### 12c. Direct Event Dispatch Test

```php
test('{entity} {action} event properties', function () {
    Event::fake();

    Event::dispatch(
        new {Entity}{Action}(property: 'value', other: 'value2'),
    );

    Event::assertDispatched({Entity}{Action}::class, function ({Entity}{Action} $event) {
        return $event->property === 'value'
            && $event->other === 'value2';
    });
});
```

### 12d. SmartLogger + BaseEvent Integration Test

Test that SmartLogger dispatches the `BaseEvent` and merges its payload:

```php
test('smart logger dispatches base event and writes activity log', function () {
    Event::fake();

    $event = new {Entity}{Action}('uuid-123', 'test@example.com');

    SmartLogger::info('{Entity} {Action}')
        ->event($event)
        ->module('{Module}')
        ->activityOnly()
        ->save();

    Event::assertDispatched({Entity}{Action}::class);

    $log = ActivityLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->event)->toBe('{entity}_{action}');
    expect($log->description)->toBe('{Entity} {Action}');
});
```

### 12e. BaseEvent Unit Tests

The `BaseEvent` itself is tested for contract compliance:

```php
test('base event to payload extracts model as id', function () {
    $model = createTestModel('uuid-123');
    $event = new {Event}('update', $model);

    $payload = $event->toPayload();

    expect($payload)->toHaveKey('action');
    expect($payload)->toHaveKey('subject_id');
    expect($payload['action'])->toBe('update');
    expect($payload['subject_id'])->toBe('uuid-123');
    expect($payload)->not->toHaveKey('subject');
});

test('base event is dispatchable', function () {
    expect(method_exists({Event}::class, 'dispatch'))->toBeTrue();
});
```

### 12f. Listener Behaviour Test

To test listener behaviour end-to-end, allow the event to dispatch and assert the side effect:

```php
test('{side effect} occurs on {event}', function () {
    // Set up preconditions

    Event::dispatch(new {Entity}{Action}($subject));

    // Assert side effect (cache cleared, notification sent, etc.)
});
```

### Testing Summary

| What                          | How                                             | Tool        |
| ----------------------------- | ----------------------------------------------- | ----------- |
| Event was dispatched          | `Event::fake([...])` + `assertDispatched()`     | Laravel     |
| Payload correctness           | Closure in `assertDispatched()`                 | Laravel     |
| Listener side effect          | Dispatch event + assert state change            | Manual      |
| SmartLogger event integration | `Event::fake()` + assert both event and log     | SmartLogger |
| BaseEvent contract            | Module unit tests on `toPayload()`, `dispatch()`, etc. | Pest        |
