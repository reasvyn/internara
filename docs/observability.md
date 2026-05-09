# Observability: Logging & Audits

## SmartLogger

`App\Support\SmartLogger` is the unified fluent logger providing dual-channel output:

```php
SmartLogger::info('Internship approved')
    ->for($user)              // Set causer
    ->about($internship)     // Set subject
    ->withPayload([...])      // Add context
    ->module('Internship')    // Set log_name
    ->event('approved')      // Set event type
    ->withPiiMasking()       // Mask sensitive data
    ->save();                // Default: both channels
```

| Method | Channel |
|---|---|
| `->both()` (default) | System log + Activity log |
| `->systemOnly()` | Laravel `Log` facade only |
| `->activityOnly()` | Spatie `activity()` only |

PII masking via `App\Support\PiiMasker::maskArray()`.

## Audit Logging

`App\Actions\Core\LogAuditAction` wraps SmartLogger for audit entries. It defaults to **activity-only** logging:

```php
$logAudit->execute(
    action: 'internship_approved',
    subjectType: Internship::class,
    subjectId: $internship->id,
    payload: ['approver_note' => 'Documents verified'],
    module: 'Internship',
);
```

Audit logs are immutable — never updated or deleted through the application.

## Activity Log Schema

Spatie `activity_log` table (`App\Models\ActivityLog` extends Spatie's `Activity`):

| Field | Description |
|---|---|
| `log_name` | Business domain (module) |
| `description` | Event description |
| `subject_type` / `subject_id` | Target entity |
| `causer_id` / `causer_type` | User who performed the action |
| `properties` | Contextual details (payload, ip, user_agent) |
| `event` | Event type (`created`, `updated`, `deleted`, `login`, etc.) |
| `batch_uuid` | Groups related activities |

Custom scopes on `ActivityLog`: `forUser()`, `forSubject()`, `ofAction()`, `inLog()`, `recent()`, `lastDays()`, `forModule()`, `groupedByDay()`.

## Application Logs

Laravel `Log` facade with daily rotation (14 days) in `storage/logs/`. Levels: `debug`, `info`, `warning`, `error`. Never log raw PII or credentials.

## Laravel Pulse

Dashboard at `/pulse` monitors system resources, slow routes/queries/jobs, active users, and cache ratios. Configured via `config/pulse.php`.

## Known Issue

`config/activitylog.php` points to Spatie's default `Activity::class`, not the custom `ActivityLog::class`. This means `activity()` and `SmartLogger` create `Activity` instances — custom scopes on `ActivityLog` are not available through the standard pipeline. See [Known Issues](known-issues.md).