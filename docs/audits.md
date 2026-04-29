# System Audits Documentation

## Overview
Internara uses a centralized, immutable audit trail system to track critical user and system activities. This system is designed to meet S1 (Secure) requirements by providing a forensic record of changes.

## Data Structure
Audit logs are stored in the `audit_logs` table using the `App\Models\AuditLog` model.

### Fields:
- `id`: UUID (Primary Key)
- `user_id`: UUID of the user who performed the action (nullable for system events).
- `subject_id`: UUID/Identifier of the entity being acted upon.
- `subject_type`: Class name of the entity.
- `action`: The specific event (e.g., `created`, `updated`, `deleted`, `login`).
- `payload`: JSON data containing contextual details (e.g., changes made).
- `ip_address`: Originating IP.
- `user_agent`: Browser/Client information.
- `module`: The business domain/module this audit belongs to.

## Usage: LogAuditAction
Logging should always be performed via the stateless `App\Actions\Audit\LogAuditAction`.

### Implementation Example:
```php
use App\Actions\Audit\LogAuditAction;

public function someMethod(LogAuditAction $logAudit) {
    // ... logic ...
    
    $logAudit->execute(
        action: 'internship_approved',
        subjectType: Internship::class,
        subjectId: $internship->id,
        payload: ['approver_note' => 'Documents verified'],
        module: 'Internship'
    );
}
```

## Security Standards (S1)
1. **Immutability**: Audit logs should never be updated or deleted through the application UI.
2. **PII Masking**: Sensitive information (passwords, private tokens) must be masked before being passed to the `payload`.
3. **Completeness**: Every state-changing action in the system must trigger an audit log.
