# Audits

## Overview

The `activity_log` table (from `spatie/laravel-activitylog`) provides an immutable record of critical user and system activities. Extended via `App\Models\ActivityLog`.

## Schema

Uses Spatie Activitylog's default schema:

| Field | Type | Description |
|---|---|---|
| `id` | bigIncrements | Primary key |
| `log_name` | string (nullable) | Business domain (module) |
| `description` | text | Event description |
| `subject_id` | string (nullable) | Target entity ID |
| `subject_type` | string (nullable) | Target entity class |
| `causer_id` | uuid (nullable) | User who performed the action |
| `causer_type` | string (nullable) | Causer class |
| `properties` | json (nullable) | Contextual details (payload, ip, user_agent) |
| `event` | string (nullable) | Event type (`created`, `updated`, `deleted`, `login`, etc.) |
| `batch_uuid` | uuid (nullable) | For grouping related activities |

## Usage

All audit logging goes through `App\Actions\Core\LogAuditAction`, which internally uses `App\Support\Logger` for consistent dual-channel logging:

```php
use App\Actions\Core\LogAuditAction;

public function approveInternship(LogAuditAction $logAudit)
{
    // ... approval logic ...

    $logAudit->execute(
        action: 'internship_approved',
        subjectType: Internship::class,
        subjectId: $internship->id,
        payload: ['approver_note' => 'Documents verified'],
        module: 'Internship',
    );
}
```

For direct logging outside audit trails, use the `Logger` helper:

```php
use App\Support\Logger;

Logger::success('Internship approved')
    ->for($user)
    ->about($internship)
    ->withPayload(['note' => 'Documents verified'])
    ->module('Internship')
    ->save();                            // system + activity logs

Logger::warning('Disk space low')
    ->systemOnly()                       // system log only
    ->save();

Logger::error('Payment failed')
    ->activityOnly()                     // activity log only
    ->save();
```

## Security

- Audit logs are never updated or deleted through the application
- Sensitive data (passwords, tokens) must be masked before passing to `payload`
- Every state-changing action must trigger an audit log
