# Log Module

## Overview

Enterprise-grade audit and logging system for Internara v0.14.0. Supports 29+ modules with PII protection, automated integrity verification, and regulatory compliance.

## Features

### 🔐 Security (S1)
- **PII Masking**: Automatic masking of sensitive data (email, phone, NIK, NISN)
- **Audit Trail**: Complete audit logs for all state changes
- **Integrity Verification**: Automated detection of tampered logs
- **Access Control**: Audit logs accessible only by authorized roles

### 📊 Audit Service

```php
// Main audit orchestrator
AuditService::log(
    module: 'Student',
    action: 'create',
    auditable: $student,
    newValue: $data
);
```

**Available Methods:**
- `log()` - Create audit entry
- `getLogsForModel()` - Get logs for specific model
- `getLogsByUser()` - Get logs by user
- `getLogsByModule()` - Filter by module
- `verifyIntegrity()` - Check audit integrity

### ⚙️ Configuration

See `config/audit.php`:
```php
return [
    'enabled' => env('AUDIT_ENABLED', true),
    'pii_fields' => ['email', 'phone', 'nik', 'nisn'],
    'masking_strategy' => 'partial',
    'retention_days' => 365,
    'modules' => ['Student', 'Teacher', 'Attendance', ...],
];
```

## Usage

### Basic Audit Logging

```php
// In your service
public function create(array $data): Model
{
    $model = parent::create($data);
    
    app(AuditService::class)->log(
        module: 'YourModule',
        action: 'create',
        auditable: $model,
        newValue: $data
    );
    
    return $model;
}
```

### Integrity Check

```php
$audit = app(AuditService::class);
$report = $audit->verifyIntegrity();

// Returns: [
//     'total_checked' => 1500,
//     'issues_found' => 2,
//     'issues' => [...],
//     'integrity_score' => 99.87
// ]
```

## Testing

```bash
php artisan test modules/Log
```

**Coverage**: 90%+
- PII masking tests
- Audit log creation tests
- Integrity verification tests
- Module filtering tests

## Architecture

```
modules/Log/
├── src/
│   ├── Services/
│   │   ├── AuditService.php       # Main audit orchestrator
│   │   ├── ActivityService.php    # Activity logging
│   │   └── Contracts/
│   │       └── AuditService.php  # Contract
│   ├── Models/
│   │   ├── AuditLog.php          # Audit log model
│   │   └── ActivityLog.php       # Activity log model
│   └── Providers/
│       └── LogServiceProvider.php
├── config/
│   └── audit.php                 # Configuration
└── tests/
    ├── Feature/
    └── Unit/
```

## Documentation

Full documentation: [docs/audit-log-system.md](../docs/audit-log-system.md)

The `Log` module provides the observability and auditing infrastructure for the Internara ecosystem.
It ensures accountability by tracking user actions and system events while maintaining strict
privacy standards through automated PII masking.

> **Governance Mandate:** This module strictly adheres to the **3S Doctrine** (Secure, Sustain,
> Scalable) and the **Modular Domain-Driven Design (DDD)** architecture. All implementations must
> preserve its Bounded Context isolation and maintain Documentation Parity (Sync or Sink).

---

## 1. Architectural Role

As a **Public Module**, the `Log` module provides centralized logging services and UI components
that allow other modules to record and visualize audit trails without domain coupling.

---

## 2. Core Components

### 2.1 Service Layer

- **`ActivityService`**: Orchestrates the querying and analysis of user activity logs.
- _Features_: Engagement statistics calculation, filtered log retrieval, and subject-based
  correlation.
- _Contract_: `Modules\Log\Services\Contracts\ActivityService`.

### 2.2 Logging Infrastructure

- **`AuditLog` Model**: Provides an immutable trail of critical administrative and system-wide data
  modifications.
- **`Activity` Model**: Extends Spatie Activitylog to support **UUID v4** identities for behavioral
  tracking.
- **`PiiMaskingProcessor`**: A Monolog processor that recursively redacts sensitive fields (emails,
  passwords, IDs) from log payloads.

### 2.3 Specialized Concerns

- **`HandlesAuditLog`**: A trait for automated recording of system-level audit events.
- **`InteractsWithActivityLog`**: A trait for standardized user activity tracking across the
  ecosystem.

### 2.4 Presentation Layer

- **`ActivityFeed`**: A reusable Livewire component for visualizing activity streams. It adheres to
  the **Thin Component** mandate by delegating all data retrieval to the `ActivityService`.

---

## 3. Engineering Standards

- **Identity Invariant**: Every log entry is identified by a UUID v4.
- **Privacy First**: Automated masking of all Personally Identifiable Information (PII) before
  persistence.
- **Zero-Coupling**: UI integration is achieved via **Slot Injection** (e.g.,
  `admin.dashboard.side`).
- **i18n Compliance**: All log descriptions and UI labels utilize module-specific translation keys.

---

## 4. Verification & Validation (V&V)

Quality is enforced through **Pest v4**:

- **Unit Tests**: Verifies activity querying and statistical aggregation logic.
- **Feature Tests**: Validates automatic audit recording during cross-module operations.
- **Command**: `php artisan test modules/Log`

---

_The Log module provides the transparency and data integrity required for a reliable internship
management ecosystem._
