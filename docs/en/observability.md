# Observability

## SmartLogger

`SmartLogger` is the unified logging interface that writes to both the system log and the activity log:

```php
SmartLogger::info('Internship approved')
    ->for($user)
    ->about($internship)
    ->withPayload([...])
    ->module('Internship')
    ->event('approved')
    ->withPiiMasking()
    ->save();
```

| Method | Channels |
|---|---|
| `->both()` (default) | System log + Activity log |
| `->systemOnly()` | Laravel Log facade only |
| `->activityOnly()` | Spatie activity log only |

PII masking is handled by `App\Support\PiiMasker::maskArray()`.

## Audit Logging

`LogAuditAction` wraps SmartLogger for audit entries. It defaults to activity-only logging:

```php
$logAudit->execute(
    action: 'internship_approved',
    subjectType: Internship::class,
    subjectId: $internship->id,
    payload: ['approver_note' => 'Documents verified'],
    module: 'Internship',
);
```

Audit logs are immutable — they are never updated or deleted through the application.

## Activity Log

The `activity_log` table tracks who did what, when, and on which entity. Each entry records the actor (causer), target (subject), event type, and contextual properties.

The custom `App\Models\ActivityLog` model provides scopes for filtering by user, subject, action, log name, date range, and module.

> **Note**: See [Known Issues](known-issues.md) for the activity model configuration issue.

## Application Logs

Laravel's `Log` facade writes to `storage/logs/` with daily rotation. Levels: `debug`, `info`, `warning`, `error`. Raw PII or credentials are never logged.

## Laravel Pulse

Pulse is available at `/pulse` for monitoring system resources, slow routes, slow queries, queue health, and more. Configured via `config/pulse.php`.
