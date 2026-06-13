# Logging & Error Handling Pattern

> **Last updated:** 2026-06-10
>
> Complete reference for the SmartLogger dual-channel logging system, PII masking,
> translation resolution, BaseEvent integration, and error handling in Internara.

---

## Table of Contents

1. [SmartLogger Architecture](#1-smartlogger-architecture)
2. [Fluent API](#2-fluent-api)
3. [Channel Routing](#3-channel-routing)
4. [PII Masking](#4-pii-masking)
5. [Translation Resolution](#5-translation-resolution)
6. [BaseEvent Integration](#6-baseevent-integration)
7. [BaseAction log() Shorthand](#7-baseaction-log-shorthand)
8. [HandlesActionErrors Trait](#8-handlesactionerrors-trait)
9. [Graceful Degradation](#9-graceful-degradation)
10. [PiiMasker Reference](#10-piimasker-reference)
11. [Testing SmartLogger](#11-testing-smartlogger)

---

## 1. SmartLogger Architecture

SmartLogger is the single point of entry for all application logging. Every log call — whether from a Command Action, a Livewire component, or error handling infrastructure — routes through this class.

### Dual-Channel Model

Every `save()` call writes to **one or both** of these channels:

| Channel | Destination | Retention | Purpose |
|---------|-------------|-----------|---------|
| **System log** | `storage/logs/laravel.log` (daily rotation) | 14 days | Technical debugging for developers and operations |
| **Activity log** | `activity_log` database table (via Spatie `laravel-activitylog` v5) | 365 days | Business audit trail, queryable by user/action/module/date |

### Data Flow

```
Caller (Action / Livewire / Trait)
        │
        ▼
  SmartLogger::info() / error() / warning() / success()
        │
        ▼
  Chain: ->for()->about()->withPayload()->withPiiMasking()->event()
        │
        ▼
  SmartLogger::save()
        │
    ┌───┴───────────────┐
    ▼                   ▼
  ┌────────┐    ┌──────────────┐
  │ Laravel│    │ Spatie       │
  │ Log    │    │ ActivityLog  │
  │ Facade │    │ (DB table)   │
  └────────┘    └──────────────┘
```

### Source

`app/Core/Support/SmartLogger.php` — `final class SmartLogger`, 321 lines.

---

## 2. Fluent API

SmartLogger uses a builder pattern. Every method returns `$this` for chaining. The terminal method is always `save()`.

### Static Factory Methods

Four static constructors map to log severity:

```php
SmartLogger::success(string $message, array $context = []): self
SmartLogger::info(string $message, array $context = []): self
SmartLogger::warning(string $message, array $context = []): self
SmartLogger::error(string $message, array $context = []): self
```

The `$context` array feeds directly into the system log's structured context. The `$message`
serves as both the system log message and the activity log description.

### Chaining Methods

| Method | Parameter | Description |
|--------|-----------|-------------|
| `for(?Model $user)` | `$user` | Sets the causer (actor). Falls back to `Auth::user()` on `save()` |
| `about(?Model $subject)` | `$subject` | Sets the subject model the action was performed on (activity log only) |
| `withPayload(array $payload)` | `$payload` | Merges additional data into the log payload |
| `withContext(array $context)` | `$context` | Merges extra context into the system log context array |
| `module(string $name)` | `$name` | Sets the module name (used as activity log name and system context) |
| `event(string\|BaseEvent $event)` | `$event` | Associates an event name string or a `BaseEvent` instance |
| `channel(string $channel)` | `$channel` | Sets an arbitrary channel label (system context only) |
| `systemOnly()` | — | Routes only to the system log |
| `activityOnly()` | — | Routes only to the activity log |
| `both()` | — | Routes to both channels (default) |
| `withPiiMasking()` | — | Enables PII masking on payloads and request metadata |

### Terminal Method

```php
public function save(): void
```

`save()` executes in this order:

1. **`processEventPayload()`** — If `$this->event` is a `BaseEvent`, dispatch it and merge `toPayload()` into `$this->payload`
2. **`applyPiiMasking()`** — If `$this->maskPii` is true, pass `$this->payload` through `PiiMasker::maskArray()`
3. **`resolveTranslations()`** — If an event name is set, resolve bilingual descriptions via `__()` and inject into `$this->context`
4. **`resolveCauser()`** — Use `$this->causer` or fall back to `Auth::user()`
5. **`writeSystemLog()`** — If `$this->toSystem` is true
6. **`writeActivityLog()`** — If `$this->toActivity` is true and `shouldWriteActivityLog()` passes

### Usage Examples

```php
// Minimal — info to both channels
SmartLogger::info('login_success')->save();

// With causer and subject
SmartLogger::info('profile_updated')
    ->for($user)
    ->about($profile)
    ->save();

// Technical error — system only, with PII masking
SmartLogger::error('Payment gateway timeout')
    ->withPayload(['txn_id' => $txnId])
    ->withPiiMasking()
    ->systemOnly()
    ->save();

// Audit-only event
SmartLogger::success('student_registered')
    ->event('student_registered')
    ->module('Enrollment')
    ->for($staff)
    ->about($registration)
    ->withPayload(['program' => $program->name])
    ->activityOnly()
    ->save();
```

---

## 3. Channel Routing

Three routing modes control which channel(s) receive the log entry.

| Mode | System Log | Activity Log | Intended Use |
|------|------------|--------------|--------------|
| `both()` | Written | Written | Default for `BaseAction::log()` — business mutations |
| `systemOnly()` | Written | Skipped | Technical errors, infrastructure failures, unexpected exceptions |
| `activityOnly()` | Skipped | Written | Audit-only events that don't need system log noise |

### Default Behavior

The constructor sets both flags to `true`:

```php
private bool $toSystem = true;
private bool $toActivity = true;
```

This means **both channels write by default** unless overridden.

### shouldWriteActivityLog Gate

The activity log has an additional guard in `shouldWriteActivityLog()`:

```php
private function shouldWriteActivityLog(?Model $causer): bool
{
    if (! $this->toActivity) {
        return false;
    }

    return $causer !== null || ($this->toActivity && ! $this->toSystem);
}
```

This means:
- If `toActivity` is `false` → skip
- If there IS a causer (resolved from `for()` or `Auth::user()`) → write
- If there is NO causer but `activityOnly()` was called → still write (explicit opt-in)
- If there is NO causer and both channels are enabled → skip (no anonymous audit entries)

### System Log Example

`HandlesActionErrors` routes unexpected exceptions to the system log only, keeping the audit trail clean:

```php
SmartLogger::error($context)
    ->withPayload(['error' => $e->getMessage()])
    ->withPiiMasking()
    ->systemOnly()
    ->save();
```

---

## 4. PII Masking

When `withPiiMasking()` is called, SmartLogger applies `PiiMasker::maskArray()` to all payload data before it reaches either channel. Request metadata (IP and User-Agent) is also masked at activity log write time.

### Full Mask

Keys matching any entry in `MASKED_KEYS` are replaced with `'***'`. Matching uses `str_contains()` — a key like `old_password` or `api_token` matches because it contains the substring `password` or `token`.

### Partial Mask

Three keys are partially masked (the last visible characters are preserved):

| Key | Method | Example |
|-----|--------|---------|
| `email` | `maskEmail()` | `jo***@example.com` |
| `phone` | `maskPhone()` | `*******8901` |
| `name` | `maskName()` | `J. Smith` |

### IP & User-Agent Masking

Applied inside `resolveRequestMetadata()` when `$this->maskPii` is true:

- **IPv4**: `192.168.***.***` (preserves first two octets)
- **IPv6**: `2001:db8::****` (preserves first segment)
- **User-Agent**: Truncated to first 50 characters with `...`

### Recursive Masking

`maskArray()` handles nested arrays recursively:

```php
$data = [
    'user' => [
        'email' => 'john@example.com',
        'credentials' => [
            'password' => 'secret123',
            'token' => 'abc',
        ],
    ],
];

// Result:
// 'user' => [
//     'email' => 'jo***@example.com',
//     'credentials' => [
//         'password' => '***',
//         'token' => '***',
//     ],
// ],
```

---

## 5. Translation Resolution

SmartLogger automatically resolves bilingual event descriptions when an event name is set.

### Resolution Logic (`resolveTranslations()`)

1. Get the current locale via `App::getLocale()`
2. Look up `__('log.'.$eventName)` for the current locale
3. If a translation exists, inject it as `context['event_description']`
4. Look up the **alternative locale** (en ↔ id)
5. If a translation exists, inject it as `context['event_description_'.$altLocale]`

### Example

Given `event('login_success')` with locale `id`:

```php
// context after resolution:
'event_description' => 'Pengguna berhasil masuk ke dalam sistem.'
'event_description_en' => 'User has successfully authenticated into the system.'
```

Both descriptions are embedded in the system log context, making every log entry self-documenting in both languages regardless of the request locale.

If no translation key exists (i.e., `__('log.'.$eventName)` returns `'log.'.$eventName` unchanged), the context keys are simply not set — no error is thrown.

### Translation Files

- `lang/en/log.php` — 59 English event descriptions
- `lang/id/log.php` — 59 Indonesian event descriptions

---

## 6. BaseEvent Integration

Events extending `BaseEvent` integrate with SmartLogger through the `event()` method.

### Automatic Dispatch

When a `BaseEvent` instance is passed to `event()`, `save()` dispatches it via Laravel's `event()` helper inside `processEventPayload()`:

```php
private function processEventPayload(): void
{
    if ($this->event instanceof BaseEvent) {
        event($this->event);
        $this->payload = array_merge($this->event->toPayload(), $this->payload);
    }
}
```

### Payload Merging

The event's `toPayload()` results are merged into `$this->payload` **before** any manually-set payload. This means manually-provided payload keys take precedence over event-derived ones.

`toPayload()` extracts all public properties from the event:

```php
public function toPayload(): array
{
    foreach (get_object_vars($this) as $key => $value) {
        if (str_starts_with($key, '__') || $key === 'socket') {
            continue;
        }

        if ($value instanceof Model) {
            $result[$key.'_id'] = $value->getKey();
        } elseif (is_object($value) && method_exists($value, 'toArray')) {
            $result[$key] = $value->toArray();
        } elseif (! is_object($value)) {
            $result[$key] = $value;
        }
    }
}
```

### Event Name Resolution

`eventName()` on `BaseEvent` provides the translation key used for bilingual descriptions:

```php
// If $this->event is a BaseEvent:
$this->event->eventName()  // e.g. 'login_success'

// If $this->event is a string:
$this->event  // used directly
```

---

## 7. BaseAction log() Shorthand

Every Command and Process Action extends `BaseAction`, which provides a convenience `log()` method:

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

This hard-codes:
- **Level**: `info`
- **Channel**: `both()` (system + activity)
- **PII masking**: always on
- **Module**: auto-derived from the Action's namespace (`$namespaceParts[1]`)

### Module Resolution

```php
protected function moduleName(): string
{
    $namespaceParts = explode('\\', static::class);
    return $namespaceParts[1] ?? 'Unknown';
}
```

`App\Auth\Login\Actions\LoginAction` → module `Auth`.
`App\Enrollment\Registration\Actions\ApproveRegistrationAction` → module `Enrollment`.

### Usage in Command Actions

```php
class ApproveReportAction extends BaseAction
{
    public function execute(Report $report, ApproveReportData $data): Report
    {
        return $this->transaction(function () use ($report, $data) {
            // ... business logic ...

            $this->log('report_approved', $report, ['score' => $data->score]);

            return $report;
        });
    }
}
```

This produces:
- **System log**: `[info] report_approved {"event":"report_approved","payload":{"score":85},"module":"Reports","user_id":"..."}`
- **Activity log**: Row in `activity_log` with `event=report_approved`, `subject_type=Report`, `subject_id=...`, properties including score

---

## 8. HandlesActionErrors Trait

The `HandlesActionErrors` trait, used by `BaseAction`, provides a consistent pattern for catching unexpected exceptions and logging them before rethrowing.

### Trait Source

`app/Core/Support/HandlesActionErrors.php`

```php
trait HandlesActionErrors
{
    protected function withErrorHandling(callable $callback, string $context): mixed
    {
        try {
            return $callback();
        } catch (RuntimeException|AppException|ModuleException|ValidationException
            |AuthorizationException|ModelNotFoundException|NotFoundHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            SmartLogger::error($context)
                ->withPayload([
                    'error' => $e->getMessage(),
                    'original_file' => $e->getFile(),
                    'original_line' => $e->getLine(),
                ])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            throw new RuntimeException(rtrim($context, '.').'.', 0, $e);
        }
    }
}
```

### Catch Strategy

| Exception Type | Handled | Behavior |
|---------------|---------|----------|
| `RuntimeException` | Re-thrown | Expected runtime failures pass through without logging |
| `AppException` | Re-thrown | Application-level expected failures (validation, conflict, not found, unauthorized) |
| `ModuleException` | Re-thrown | Domain rule violations pass through |
| `ValidationException` | Re-thrown | Laravel validation passes through |
| `AuthorizationException` | Re-thrown | Gate-denied passes through |
| `ModelNotFoundException` | Re-thrown | Eloquent not-found passes through |
| `NotFoundHttpException` | Re-thrown | 404 passes through |
| **All other `\Throwable`** | **Caught** | Logged with PII masking to system channel, then re-thrown as `RuntimeException` |

### Logged Payload

When an unexpected exception is caught, the log includes:

- `error` — `$e->getMessage()`
- `original_file` — `$e->getFile()`
- `original_line` — `$e->getLine()`

The message is sent to the **system log only** (`systemOnly()`) with PII masking enabled.

### Rethrow Behavior

The caught exception is re-thrown as:

```php
throw new RuntimeException(rtrim($context, '.').'.', 0, $e);
```

This preserves the stack trace (`$e` as previous) while providing a clean, contextual message.

---

## 9. Graceful Degradation

The activity log channel can fail without breaking the application. The system log channel cannot — unwritable log files should surface immediately.

### Activity Log Failure Handling

In `writeActivityLog()`, the entire Spatie activity log call is wrapped in try-catch:

```php
private function writeActivityLog(...): void
{
    try {
        // ... activity()->causedBy()->event()->withProperties()->log() ...
    } catch (\Throwable $e) {
        LogFacade::error('Failed to write activity log', [
            'face' => $this->face,
            'message' => $this->message,
            'module' => $this->module,
            'event' => $eventName,
            'error' => $e->getMessage(),
            'error_class' => get_class($e),
        ]);
    }
}
```

If the database is unreachable, the Spatie activity log insert fails, but:
1. An error is written to the system log with diagnostic context
2. Execution continues without throwing
3. The calling Action completes successfully

### System Log Failure

The system log channel is **not** wrapped in try-catch. If `storage/logs/` is unwritable, the exception propagates — this is intentional, as a system that cannot log should surface the problem immediately.

---

## 11. Testing SmartLogger

### Strategy

Test each channel independently. Use Laravel's `Log::spy()` for system log assertions and Spatie's `ActivitylogServiceProvider` test infrastructure for activity log assertions.

### System Log Assertions

```php
use Illuminate\Support\Facades\Log;

it('writes to system log on success', function () {
    Log::spy();

    SmartLogger::success('test_event')->save();

    Log::shouldHaveReceived('info')
        ->withArgs(fn ($message) => $message === 'test_event');
});
```

### Activity Log Assertions

```php
use Spatie\Activitylog\Models\Activity;

it('writes to activity log', function () {
    $user = User::factory()->create();

    SmartLogger::info('test_event')
        ->for($user)
        ->save();

    $activity = Activity::where('description', 'test_event')->first();
    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe((string) $user->id);
});
```

### PII Masking Tests

```php
it('masks sensitive payload keys', function () {
    $masked = PiiMasker::maskArray([
        'email' => 'john@example.com',
        'password' => 'secret123',
        'name' => 'John Smith',
    ]);

    expect($masked['email'])->toMatch('/^\w+\*\*\*@/')
        ->and($masked['password'])->toBe('***')
        ->and($masked['name'])->toBe('J. Smith');
});

it('masks ipv4 preserving first two octets', function () {
    expect(PiiMasker::maskIp('192.168.1.100'))->toBe('192.168.***.***');
});
```

### Channel Routing Tests

```php
it('does not write activity log when systemOnly', function () {
    Log::spy();

    SmartLogger::info('system_event')
        ->systemOnly()
        ->save();

    Log::shouldHaveReceived('info');
    // No Activity record should exist
    expect(Activity::count())->toBe(0);
});
```

### Graceful Degradation Test

```php
it('does not throw when activity log fails', function () {
    // Simulate DB failure by disconnecting
    DB::disconnect('activitylog');

    SmartLogger::info('test_event')
        ->for(User::factory()->create())
        ->save();

    // Should complete without throwing
    expect(true)->toBeTrue();

    // Reconnect for other tests
    DB::reconnect('activitylog');
});
```

### Translation Resolution Test

```php
it('resolves bilingual descriptions', function () {
    App::setLocale('id');

    SmartLogger::info('login_success')
        ->event('login_success')
        ->systemOnly()
        ->save();

    Log::shouldHaveReceived('info')
        ->withArgs(fn ($message, $context) =>
            str_contains($context['event_description'], 'berhasil')
            && str_contains($context['event_description_en'], 'authenticated')
        );
});
```

---

## References

| File | Purpose |
|------|---------|
| `app/Core/Support/SmartLogger.php` | Dual-channel fluent logger |
| `app/Core/Support/PiiMasker.php` | PII masking engine |
| `app/Core/Support/HandlesActionErrors.php` | Error handling trait |
| `app/Core/Actions/BaseAction.php` | Base class with `log()` shorthand |
| `app/Core/Events/BaseEvent.php` | Abstract event with SmartLogger integration |
| `docs/adr/adr-smartlogger-dual-channel.md` | ADR-005: Architecture Decision Record |
| `lang/en/log.php` | English event descriptions |
| `lang/id/log.php` | Indonesian event descriptions |
| `app/Core/Exceptions/Concerns/HasExceptionContext.php` | PII masking in exceptions |
