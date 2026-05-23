# ADR-006: SmartLogger Dual-Channel Logging

## Status
Accepted

## Context
The application needs two distinct kinds of logging:

1. **System logs**: Technical debugging information — errors, warnings, performance data.
   Consumed by developers and operations teams. Stored in `storage/logs/laravel.log`.
   Managed by standard log rotation.

2. **Activity audit logs**: Business-level records — who did what, when, and to which entity.
   Consumed by administrators, auditors, and GDPR compliance workflows. Stored in the
   `activity_log` database table. Requires higher retention and cannot be pruned without
   configurable policies.

Using Laravel's `Log::` facade directly for both purposes creates problems:
- No distinction between debug noise and audit-significant events
- No structured context (module, entity type, event name) attached to audit entries
- PII (passwords, tokens, emails) can leak into plain-text log files
- Activity logs are not queryable — grep doesn't scale for audit investigations

## Decision
All logging goes through `SmartLogger` — a fluent, dual-channel logger. Each log call
specifies the event name, module, subject entity, and payload. `SmartLogger` routes the data
to both the system log (file) and the activity log (database), applying PII masking via
`PiiMasker` automatically.

Usage pattern:
```php
SmartLogger::info('registration_approved')
    ->event('registration_approved')
    ->module('Registration')
    ->about($registration)
    ->withPayload(['admin_id' => $admin->id])
    ->activityOnly()  // audit only, no system log
    ->save();
```

The `BaseAction::log()` method wraps this for Actions, extracting the module name
automatically from the namespace and passing the subject model.

## Consequences
- **Positive**: Every significant business event is automatically audited with full context.
- **Positive**: PII is masked before it reaches log files — passwords, tokens, emails are
  replaced with `[REDACTED]`.
- **Positive**: Activity logs are queryable via the database — admins can search by user,
  action type, module, or date range.
- **Positive**: `SmartLogger` is the only logger used in the codebase — zero `Log::` facade
  calls exist.
- **Negative**: Two storage systems to manage (log files + database table). The database table
  requires periodic pruning via `system:cleanup`.
- **Negative**: Fluent interface adds verbosity compared to `Log::info()`.
- **Negative**: If `SmartLogger` fails (e.g., database connection issue), it must degrade
  gracefully without breaking the application — currently handled by catching exceptions
  in the logger implementation.

## References
- `app/Domain/Core/Support/SmartLogger.php`
- `app/Domain/Core/Support/PiiMasker.php`
- `app/Domain/Core/Actions/BaseAction.php` — `log()` method
- `docs/observability.md`
- `app/Domain/Admin/Livewire/AuditLogManager.php` — UI for browsing audit logs
