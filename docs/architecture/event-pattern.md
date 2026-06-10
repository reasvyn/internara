# Events, Listeners & Notifications Pattern

> **Last updated:** 2026-06-10
>
> **Audience:** Developers working with event-driven side effects and multi-channel notifications.
> **Prerequisites:** [12-Layer Architecture](architecture.md), [Action Pattern](action-pattern.md),
> [Logging Pattern](logging-pattern.md).

---

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
10. [SmartLogger Integration](#10-smartlogger-integration)
11. [Event Flow Diagram](#11-event-flow-diagram)
12. [Complete Event Inventory](#12-complete-event-inventory)
13. [Complete Notification Inventory](#13-complete-notification-inventory)
14. [Testing Events and Listeners](#14-testing-events-and-listeners)

---

## 1. Event Architecture (BaseEvent Contract)

All events **must** extend `App\Core\Events\BaseEvent`. The base class provides the foundational
contract and a set of built-in capabilities:

| Trait / Method | Purpose |
|---|---|
| `Dispatchable` | Static `dispatch()` and instance `dispatch()` via `Illuminate\Foundation\Events\Dispatchable` |
| `InteractsWithSockets` | Broadcasting scaffold (defaults to no broadcast) |
| `SerializesModels` | Safe queue serialization for Eloquent models |
| `eventName(): string` | **Abstract** — returns a dot-notation key used by SmartLogger for log translation |
| `toPayload(): array` | Extracts public properties for logging, converting Models to `{name}_id` |
| `broadcastOn(): array` | Returns `[]` (override to enable broadcasting) |
| `shouldBroadcast(): bool` | Returns `false` (override to enable) |
| `shouldQueue(): bool` | Returns `false` (override to queue the event itself) |
| `queue(): string` | Returns `'default'` |

### BaseEvent Source

```php
abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    abstract public function eventName(): string;

    public function toPayload(): array { /* ... */ }
    public function broadcastOn(): array { return []; }
    public function broadcastAs(): string { return $this->eventName(); }
    public function shouldBroadcast(): bool { return false; }
    public function shouldQueue(): bool { return false; }
    public function queue(): string { return 'default'; }
}
```

### Rules

- Events are **`final readonly`** classes.
- Properties are `public` with typed constructor promotion.
- Only scalar types, Model instances, and objects with `toArray()` are allowed as properties.
- Events belong to the **module that emits them** (`app/{Module}/{SubModule}/Events/`).

---

## 2. Event Naming Conventions

**Pattern:** `{Entity}{PastTenseAction}`

The class name reads as a completed fact: `InternshipCreated`, `LoginFailed`, `StudentRegistered`.

| Convention | Example |
|---|---|
| Entity + Created | `InternshipCreated`, `AcademicYearCreated` |
| Entity + Activated | `AcademicYearActivated` |
| Entity + Deleted | `DepartmentDeleted` |
| Entity + Updated | `ProfileUpdated`, `PasswordUpdated`, `SettingUpdated` |
| Entity + Succeeded | `LoginSucceeded` |
| Entity + Failed | `LoginFailed` |
| Entity + Finalized | `SetupFinalized` |
| Entity + Read | `NotificationRead` |
| Entity + Sent | `NotificationSent` |

The `eventName()` method returns a **dot-notation key** (`internship.created`, `login.failed`)
that doubles as the log translation key for SmartLogger.

### Examples

```php
// app/Program/Internship/Events/InternshipCreated.php
final class InternshipCreated extends BaseEvent
{
    public function __construct(
        public Internship $internship,
        public ?User $createdBy = null,
    ) {}

    public function eventName(): string
    {
        return 'internship.created';
    }
}
```

```php
// app/Auth/Login/Events/LoginFailed.php
final class LoginFailed extends BaseEvent
{
    public function __construct(
        public string $identifier,
        public string $reason,
    ) {}

    public function eventName(): string
    {
        return 'login.failed';
    }
}
```

```php
// app/Setup/SetupWizard/Events/SetupFinalized.php
final class SetupFinalized extends BaseEvent
{
    public function __construct(
        public ?string $departmentId,
        public DateTimeImmutable $installedAt,
    ) {}

    public function eventName(): string
    {
        return 'setup.finalized';
    }
}
```

---

## 3. Event Payload (toPayload)

`toPayload()` extracts public properties into a flat array for SmartLogger payload merging. It
applies three rules:

| Property Type | Output |
|---|---|
| `Model` instance | Converted to `{name}_id` using `$value->getKey()` |
| Object with `toArray()` | Converted via `$value->toArray()` |
| Scalar / array | Passed as-is |

Hidden properties: `socket` and any key starting with `__` are skipped.

### Payload Conversion Example

```php
$event = new InternshipCreated(
    internship: $internship,       // Model → 'internship_id' => 'uuid-...'
    createdBy: $user,              // Model → 'created_by_id' => 'uuid-...'
);

$event->toPayload();
// [
//   'internship_id' => '0195abc...',
//   'created_by_id' => '0195def...',
// ]
```

For events with only scalar properties (e.g. `SetupFinalized`), the payload is a direct 1:1 map:

```php
$event = new SetupFinalized(
    departmentId: '0195abc...',
    installedAt: new DateTimeImmutable('2026-06-10 12:00:00'),
);

$event->toPayload();
// [
//   'departmentId' => '0195abc...',
//   'installedAt' => '2026-06-10 12:00:00',  // DateTimeImmutable has no toArray(), skipped
// ]
```

> DateTimeImmutable has no `toArray()`, so it is silently skipped. If you need it in the payload,
> pass it as a formatted string or override `toPayload()`.

---

## 4. Event Dispatch Patterns

There are three ways to dispatch events in the codebase:

### 4a. Static `Event::dispatch()` / `::dispatch()`

Use when dispatching from a non-Action context or when you need the event immediately:

```php
use Illuminate\Support\Facades\Event;

Event::dispatch(new LoginSucceeded($user, $data->identifier));
Event::dispatch(new LoginFailed($data->identifier, 'user_not_found'));
```

### 4b. `BaseAction::dispatchEvent()` (Deferred Dispatch)

Inside Command or Process Actions, use `$this->dispatchEvent()` to **defer** dispatch until the
transaction commits. This prevents listeners from seeing uncommitted data:

```php
class SubmitLogbookAction extends BaseAction
{
    public function execute(Logbook $entry, array $data): Logbook
    {
        return $this->transaction(function () use ($entry, $data) {
            $entry->update(['status' => LogbookStatus::SUBMITTED->value]);

            $this->log('logbook_submitted', $entry);
            $this->dispatchEvent(new LogbookSubmitted($entry));

            return $entry;
        });
    }
}
```

`BaseAction` collects deferred events in `$this->pendingEvents[]` and dispatches them via the
global `event()` helper after the `transaction()` callback completes (or after the inner closure
if already inside a transaction).

### 4c. SmartLogger `->event($baseEvent)->save()` (Auto-Dispatch + Log)

When `SmartLogger::event()` receives a `BaseEvent` instance instead of a string, `save()` will:

1. Call `event($baseEvent)` to dispatch it to listeners.
2. Merge `$event->toPayload()` into the log payload.

```php
SmartLogger::success('User registered')
    ->event(new UserRegistered($user))
    ->for($admin)
    ->save();
```

This is equivalent to:

```php
event(new UserRegistered($user));

SmartLogger::success('User registered')
    ->event('user_registered')
    ->withPayload((new UserRegistered($user))->toPayload())
    ->for($admin)
    ->save();
```

---

## 5. Listener Naming and Registration

### Naming

Listeners are named by what they **do**, not what event they handle:

| Name | Event | Action |
|---|---|---|
| `NotifyAdminsInternshipCreated` | `InternshipCreated` | Sends notification to admin users |
| `InvalidateSettingsCache` | `SettingUpdated` | Clears cached settings |
| `ClearUnreadNotificationCache` | `NotificationSent` / `NotificationRead` | Clears per-user unread count |
| `LogSetupFinalized` | `SetupFinalized` | Writes setup completion to activity log |
| `ClearDashboardCacheOnYearChange` | `AcademicYearCreated` / `AcademicYearActivated` | Invalidates dashboard stats |

### Registration

Listeners are registered in `config/event.php` under the `listen` key:

```php
// config/event.php
return [
    'listen' => [
        App\Setup\SetupWizard\Events\SetupFinalized::class => [
            App\Setup\SetupWizard\Listeners\LogSetupFinalized::class,
        ],

        App\Program\Internship\Events\InternshipCreated::class => [
            App\Program\Internship\Listeners\NotifyAdminsInternshipCreated::class,
        ],

        // A single listener can handle multiple events:
        App\User\Notifications\Events\NotificationSent::class => [
            App\User\Notifications\Listeners\ClearUnreadNotificationCache::class,
        ],
        App\User\Notifications\Events\NotificationRead::class => [
            App\User\Notifications\Listeners\ClearUnreadNotificationCache::class,
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
class NotifyAdminsInternshipCreated implements ShouldQueue
{
    public function handle(InternshipCreated $event): void
    {
        $admins = User::role(['super_admin', 'admin'])->get();

        Notification::send(
            $admins,
            new InternshipCreatedNotification(
                internshipName: $event->internship->name,
                createdByName: $event->createdBy?->name,
            ),
        );
    }
}
```

### Listener ShouldQueue Inventory

| Listener | Implements ShouldQueue | Reason |
|---|---|---|
| `NotifyAdminsInternshipCreated` | ✅ Yes | Sends email/broadcast to multiple users |
| `InvalidateSettingsCache` | ❌ No | Synchronous cache forget (microseconds) |
| `ClearUnreadNotificationCache` | ❌ No | Synchronous cache forget (microseconds) |
| `ClearDashboardCacheOnYearChange` | ❌ No | Synchronous cache forget (microseconds) |
| `ClearDashboardCacheOnDepartmentChange` | ❌ No | Synchronous cache forget (microseconds) |
| `ClearDashboardOnRegistration` | ❌ No | Synchronous cache forget (microseconds) |
| `ClearDashboardOnCompanyChange` | ❌ No | Synchronous cache forget (microseconds) |
| `LogSetupFinalized` | ❌ No | Synchronous activity log write |

Listeners should use union types in `handle()` when they respond to multiple event types:

```php
public function handle(NotificationSent|NotificationRead $event): void
{
    Cache::forget(
        config('cache-keys.notification_unread').$event->notification->user_id,
    );
}
```

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

| Channel | Delivery | When |
|---|---|---|
| `mail` | Email via `MailMessage` | Always for important notifications |
| `broadcast` | Realtime via Laravel Echo | When the user is online |
| `CustomDatabaseChannel::class` | In-app database notification | Always (stored in `notifications` table) |

### Notification Structure

```php
class InternshipCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $internshipName,
        public ?string $createdByName = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toMail($notifiable): MailMessage { /* ... */ }

    public function toBroadcast($notifiable): array
    {
        return [
            'title' => __('notifications.internship_created.title'),
            'message' => __('notifications.internship_created.broadcast', [
                'name' => $this->internshipName,
            ]),
            'link' => '/admin/internships',
        ];
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'internship_created',
            'title' => __('notifications.internship_created.title'),
            'message' => __('notifications.internship_created.database', [
                'name' => $this->internshipName,
            ]),
            'link' => '/admin/internships',
            'data' => [
                'internship_name' => $this->internshipName,
                'created_by' => $this->createdByName,
            ],
        ];
    }
}
```

### Sending Notifications

Notifications are sent via `Notification::send()` or `$notifiable->notify()`:

```php
Notification::send(
    $admins,
    new InternshipCreatedNotification(
        internshipName: $event->internship->name,
        createdByName: $event->createdBy?->name,
    ),
);
```

### ShouldQueue for Notifications

Every notification must implement `ShouldQueue` + `use Queueable` to deliver channels through the
queue worker, except trivial synchronous notifications:

```php
class ActivationCodeNotification extends Notification  // No ShouldQueue
{
    // Email + database only, sent synchronously during activation
}
```

---

## 8. Notification Naming

**Pattern:** `{Entity}{NotificationType}Notification`

The class name combines the subject entity with the notification type, suffixed with `Notification`:

| Class | Entity | Type | Module |
|---|---|---|---|
| `InternshipCreatedNotification` | Internship | Created | Program |
| `WelcomeNotification` | (none) | Welcome | User |
| `AccountStatusNotification` | Account | Status | User |
| `IncidentReportedNotification` | Incident | Reported | Incident |
| `AssignmentNotification` | Assignment | General | Assignment |
| `SubmissionFeedbackNotification` | Submission | Feedback | Assignment |
| `RegistrationNotification` | Registration | General | Program |
| `SuperAdminRecoveredNotification` | SuperAdmin | Recovered | Auth |
| `AnnouncementNotification` | (none) | Announcement | SysAdmin |
| `ActivationCodeNotification` | (none) | Activation | SysAdmin |
| `GeneralNotification` | (none) | General | User |

---

## 9. CustomDatabaseChannel

The `CustomDatabaseChannel` is the internal notification delivery mechanism. It writes to the
`notifications` table via `SendsNotifications` (implemented by `SendNotificationAction`).

### Flow

```
      Notification::send()
              │
              ▼
    CustomDatabaseChannel::send()
              │
    ┌─────────┴─────────┐
    ▼                   ▼
  toCustomDatabase()   SmartLogger warning
  exists?              if type/title missing
    │
    ▼
  SendNotificationAction::execute()
    │
    ▼
  Notification::create({user_id, type, title, message, data, link})
    │
    ▼
  Event::dispatch(new NotificationSent($notification))
```

### toCustomDatabase Contract

Each notification must implement `toCustomDatabase($notifiable): array` returning:

| Key | Required | Description |
|---|---|---|
| `type` | ✅ Yes | Machine-readable type string (e.g. `'internship_created'`) |
| `title` | ✅ Yes | Human-readable title (use `__()`) |
| `message` | ❌ No | Human-readable body text |
| `link` | ❌ No | URL to navigate to |
| `data` | ❌ No | Arbitrary metadata array |

### SendsNotifications Contract

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

`SendNotificationAction` implements this interface, validates the input, creates the `Notification`
model record, and dispatches `NotificationSent`.

### Missing Key Warnings

If `toCustomDatabase()` omits `type` or `title`, `CustomDatabaseChannel` writes a warning via
SmartLogger (system-only) to aid debugging:

```php
if (! isset($data['type'])) {
    SmartLogger::warning('Notification missing type key')
        ->withPayload(['notification_class' => get_class($notification)])
        ->systemOnly()
        ->save();
}
```

---

## 10. SmartLogger Integration

Events extending `BaseEvent` integrate with SmartLogger in two ways.

### 10a. BaseAction::log() — Event Name as Log Key

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

### 10b. SmartLogger::event() with BaseEvent Instance

When `SmartLogger::event()` receives a `BaseEvent` instance:

1. **Dispatches** the event to all registered listeners via `event($baseEvent)`.
2. **Merges** `$event->toPayload()` into the log payload (explicit `withPayload()` overrides).
3. **Resolves** the event name from `$event->eventName()` for log translation.

```php
SmartLogger::success('User created')
    ->event(new TestUserCreatedEvent('uuid-123', 'test@example.com'))
    ->module('TestModule')
    ->activityOnly()
    ->save();
```

This produces:
- A dispatched `TestUserCreatedEvent` caught by any registered listeners.
- An activity log entry with `event = 'user_created'` and payload containing
  `['userId' => 'uuid-123', 'email' => 'test@example.com']`.

---

## 11. Event Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                      Command / Process Action                    │
│                                                                  │
│  public function execute(Data $data): Model                      │
│  {                                                               │
│      return $this->transaction(function () use ($data) {         │
│          // 1. Persist                                           │
│          $model = $this->repo->create($data);                    │
│                                                                  │
│          // 2. Log (SmartLogger writes to system + activity log) │
│          $this->log('entity_action', $model);                    │
│                                                                  │
│          // 3. Defer event dispatch until transaction commits    │
│          $this->dispatchEvent(new EntityActioned($model));       │
│                                                                  │
│          return $model;                                          │
│      });                                                         │
│  }                                                               │
└───────────────────────┬─────────────────────────────────────────┘
                        │
                        ▼  (after transaction commits)
              ┌───────────────────┐
              │  event() global   │
              │  helper fires     │
              │  BaseEvent        │
              └────────┬──────────┘
                       │
          ┌────────────┴──────────────┐
          ▼                           ▼
  ┌─────────────────┐       ┌───────────────────────┐
  │  Listener #1     │       │  Listener #2           │
  │  (sync)          │       │  (ShouldQueue)         │
  │                  │       │                        │
  │  e.g.            │       │  e.g.                  │
  │  InvalidateCache │       │  NotifyAdmins...       │
  └────────┬─────────┘       └───────────┬───────────┘
           │                             │
           ▼                             ▼
  ┌──────────────────┐        ┌──────────────────────┐
  │ Cache::forget()  │        │ Notification::send() │
  └──────────────────┘        └──────────┬───────────┘
                                         │
                                         ▼
                                ┌──────────────────┐
                                │ mail              │
                                │ broadcast         │
                                │ CustomDatabase    │
                                └───────┬──────────┘
                                        │
                                        ▼
                               ┌──────────────────┐
                               │ NotificationSent │
                               │ event dispatched │
                               └──────────────────┘
```

---

## 12. Complete Event Inventory

| Event Class | Module | eventName() | Payload | Listeners |
|---|---|---|---|---|
| `AcademicYearCreated` | Academics | `academic_year.created` | `academic_year_id` | ClearDashboardCacheOnYearChange |
| `AcademicYearActivated` | Academics | `academic_year.activated` | `academic_year_id`, `previous_active_id` | ClearDashboardCacheOnYearChange |
| `DepartmentCreated` | Academics | `department.created` | `department_id` | ClearDashboardCacheOnDepartmentChange |
| `DepartmentDeleted` | Academics | `department.deleted` | `department_id` | ClearDashboardCacheOnDepartmentChange |
| `CompanyCreated` | Partners | `company.created` | `company_id` | ClearDashboardOnCompanyChange |
| `PartnershipCreated` | Partners | `partnership.created` | `partnership_id` | (none) |
| `InternshipCreated` | Program | `internship.created` | `internship_id`, `created_by_id` | NotifyAdminsInternshipCreated |
| `StudentRegistered` | Enrollment | `student.registered` | `registration_id` | ClearDashboardOnRegistration |
| `SetupFinalized` | Setup | `setup.finalized` | `departmentId`, `installedAt` | LogSetupFinalized |
| `SettingUpdated` | Settings | `setting.created` or `setting.updated` | `setting`, `wasRecentlyCreated` | InvalidateSettingsCache |
| `LoginSucceeded` | Auth | `login.succeeded` | `user_id`, `identifier` | (none) |
| `LoginFailed` | Auth | `login.failed` | `identifier`, `reason` | (none) |
| `PasswordUpdated` | Auth | `password.updated` | `user_id` | (none) |
| `ProfileUpdated` | User | `profile.updated` | `profile_id` | (none) |
| `NotificationSent` | User | `notification.sent` | `notification_id` | ClearUnreadNotificationCache |
| `NotificationRead` | User | `notification.read` | `notification_id` | ClearUnreadNotificationCache |

---

## 13. Complete Notification Inventory

| Notification Class | Module | Channel | CustomDatabase `type` | ShouldQueue |
|---|---|---|---|---|
| `InternshipCreatedNotification` | Program | mail, broadcast, CustomDatabase | `internship_created` | ✅ Yes |
| `IncidentReportedNotification` | Incident | mail, broadcast, CustomDatabase | `incident_reported` | ✅ Yes |
| `AssignmentNotification` | Assignment | mail, broadcast, CustomDatabase | `assignment_published` | ✅ Yes |
| `SubmissionFeedbackNotification` | Assignment | mail, broadcast, CustomDatabase | `submission_feedback` | ✅ Yes |
| `RegistrationNotification` | Program | mail, broadcast, CustomDatabase | `internship_registration_update` | ✅ Yes |
| `AccountStatusNotification` | User | mail, broadcast, CustomDatabase | `account_status_change` | ✅ Yes |
| `WelcomeNotification` | User | mail, broadcast, CustomDatabase | `system_welcome` | ✅ Yes |
| `GeneralNotification` | User | CustomDatabase, (opt-in mail) | (dynamic) | ✅ Yes |
| `SuperAdminRecoveredNotification` | Auth | mail, broadcast, CustomDatabase | `super_admin_recovery` | ✅ Yes |
| `AnnouncementNotification` | SysAdmin | mail, broadcast, CustomDatabase | `announcement` | ✅ Yes |
| `ActivationCodeNotification` | SysAdmin | mail, CustomDatabase | `activation_code` | ❌ No |

---

## 14. Testing Events and Listeners

### 14a. Event Dispatch Tests

Use `Event::fake()` to capture dispatched events without running listeners:

```php
use Illuminate\Support\Facades\Event;

test('academic year created event is dispatched via action', function () {
    Event::fake([AcademicYearCreated::class]);

    app(CreateAcademicYearAction::class)->execute([
        'name' => '2025/2026',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
    ]);

    Event::assertDispatched(AcademicYearCreated::class);
});
```

### 14b. Payload Assertions

Assert the event was dispatched with specific properties:

```php
test('login succeeded event contains correct user data', function () {
    $user = User::factory()->create();

    Event::fake([LoginSucceeded::class]);

    $this->post('/login', [
        'username' => $user->username,
        'password' => 'password',
    ]);

    Event::assertDispatched(LoginSucceeded::class, function (LoginSucceeded $event) use ($user) {
        return $event->user->is($user) && $event->identifier === $user->username;
    });
});
```

### 14c. Event Finalization Test

Test the event class directly with `Event::dispatch()`:

```php
test('setup finalized event contains department id and installed at', function () {
    Event::fake();

    Event::dispatch(
        new SetupFinalized(
            departmentId: 'dept-123',
            installedAt: new DateTimeImmutable('2026-01-01 00:00:00'),
        ),
    );

    Event::assertDispatched(SetupFinalized::class, function (SetupFinalized $event) {
        return $event->departmentId === 'dept-123'
            && $event->installedAt->format('Y-m-d') === '2026-01-01';
    });
});
```

### 14d. SmartLogger + BaseEvent Integration Test

Test that SmartLogger dispatches the `BaseEvent` and merges its payload:

```php
test('smart logger dispatches base event and writes activity log', function () {
    Event::fake();

    $event = new TestUserCreatedEvent('uuid-123', 'test@example.com');

    SmartLogger::info('User created')
        ->event($event)
        ->module('TestModule')
        ->activityOnly()
        ->save();

    Event::assertDispatched(TestUserCreatedEvent::class);

    $log = ActivityLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->event)->toBe('user_created');
    expect($log->description)->toBe('User created');
});
```

### 14e. BaseEvent Unit Tests

The `BaseEvent` itself is tested for contract compliance:

```php
test('base event to payload extracts model as id', function () {
    $model = createTestModel('uuid-123');
    $event = new MockEventWithModel('update', $model);

    $payload = $event->toPayload();

    expect($payload)->toHaveKey('action');
    expect($payload)->toHaveKey('subject_id');
    expect($payload['action'])->toBe('update');
    expect($payload['subject_id'])->toBe('uuid-123');
    expect($payload)->not->toHaveKey('subject');
});

test('base event is dispatchable', function () {
    expect(method_exists(MockLogEvent::class, 'dispatch'))->toBeTrue();
});
```

### 14f. Listener Behaviour Test

To test listener behaviour end-to-end, allow the event to dispatch and assert the side effect:

```php
test('notification sent invalidates unread cache', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $user->id]);

    Cache::put(
        config('cache-keys.notification_unread').$user->id,
        5,
    );

    Event::dispatch(new NotificationSent($notification));

    expect(Cache::has(
        config('cache-keys.notification_unread').$user->id,
    ))->toBeFalse();
});
```

### Testing Summary

| What | How | Tool |
|---|---|---|
| Event was dispatched | `Event::fake([...])` + `assertDispatched()` | Laravel |
| Payload correctness | Closure in `assertDispatched()` | Laravel |
| Listener side effect | Dispatch event + assert state change | Manual |
| SmartLogger event integration | `Event::fake()` + assert both event and log | SmartLogger |
| BaseEvent contract | Unit tests on `toPayload()`, `dispatch()`, etc. | Pest |
