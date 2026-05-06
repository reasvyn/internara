# Audits

## Overview

The `audit_logs` table provides an immutable record of critical user and system activities. Stored in `App\Domain\Core\Models\AuditLog`.

## Schema

| Field | Type | Description |
|---|---|---|
| `id` | uuid | Primary key |
| `user_id` | uuid (nullable) | User who performed the action |
| `subject_id` | string | Target entity ID |
| `subject_type` | string | Target entity class |
| `action` | string | Event type (`created`, `updated`, `deleted`, `login`, etc.) |
| `payload` | json | Contextual details (changes made) |
| `ip_address` | string | Originating IP |
| `user_agent` | text | Browser/client info |
| `module` | string | Business domain |

## Usage

All audit logging goes through `App\Domain\Core\Actions\LogAuditAction`:

```php
use App\Domain\Core\Actions\LogAuditAction;

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

## Security

- Audit logs are never updated or deleted through the application
- Sensitive data (passwords, tokens) must be masked before passing to `payload`
- Every state-changing action must trigger an audit log
