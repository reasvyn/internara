# Setting Module

The `Setting` module provides a centralized, cached, and database-backed infrastructure for managing
dynamic application-wide configurations. It allows administrators to modify system parameters during
runtime without requiring code or environment file changes.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/developers/specs.md)**. All implementation must
> adhere to the **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## 1. Architectural Role

As a **Foundational Public Module**, the `Setting` module provides the authoritative source for
application parameters used by all other modules. It works in tandem with the `Core` module's global
`setting()` helper to ensure systemic stability.

---

## 2. Core Components

### 2.1 Service Layer

- **`SettingService`**: Provides a robust API for retrieving and persisting configuration values.
    - _Features_: Key-based and group-based retrieval, automated type casting, and proactive
      caching.
    - _Contract_: `Modules\Setting\Services\Contracts\SettingService`.

### 2.2 Persistence Layer

- **`Setting` Model**: Manages the underlying `settings` table.
    - _Fields_: `key` (PK), `value`, `type`, `group`, `description`.
    - _Casting_: Utilizes `SettingValueCast` to ensure values are returned as correct PHP types
      (string, boolean, integer, array).

### 2.3 Fail-safe Mechanisms

- **Bootstrapping Resilience**: Includes logic to allow critical system checks (via direct file
  reads of `modules_statuses.json`) before the database or full service container is initialized.

---

## 3. Engineering Standards

- **Zero Magic Values**: Cache prefixes and standard keys are managed via internal constants.
- **Auditability**: Leverages the `Log` module's auditing concern to track every change to system
  settings ("who", "when", "old value", "new value").
- **i18n Support**: Descriptions and group names are intended for administrative clarity and support
  translation.

---

## 4. Verification & Validation (V&V)

Reliability is ensured through **Pest v4**:

- **Unit Tests**: Verifies value persistence, default fallback logic, and cache integrity.
- **Feature Tests**: Validates the automatic recording of audit logs during setting updates.
- **Command**: `php artisan test modules/Setting`

---

_The Setting module ensures that Internara remains a flexible and manageable platform for
institutional administrators._
