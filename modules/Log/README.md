# Log Module

The `Log` module provides the observability and auditing infrastructure for the Internara ecosystem.
It ensures accountability by tracking user actions and system events while maintaining strict
privacy standards through automated PII masking.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/dev/specs.md)**. All implementation must
> adhere to the **[Coding Conventions](../../docs/dev/conventions.md)**.

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
