# Status Module

The `Status` module provides the foundational infrastructure for tracking and managing the state
transitions of all entities within the Internara ecosystem. It centralizes state-based logic and
ensures that lifecycle changes are auditable, localized, and consistent.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/developers/specs.md)**. All implementation must
> adhere to the **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## 1. Architectural Role

As a **Foundational Public Module**, the `Status` module provides the `HasStatus` concern and the
underlying persistence layer for state history. It is designed to be consumed by any domain module
that requires an audit trail of state transitions ("active", "pending", "completed", etc.).

---

## 2. Core Components

### 2.1 Persistence Layer

- **`Status` Model**: The central entity for state history.
    - _Identities_: Uses **UUID v4** for its own identity and the related `model_id`.
    - _Features_: Automatically integrates with the `Log` module to record state transitions.

### 2.2 Global Concerns

- **`HasStatus`**: A standardized trait for Eloquent models that provides methods for setting,
  retrieving, and visualizing entity states.
    - _Methods_: `setStatus()`, `getStatusLabel()`, `getStatusColor()`.

---

## 3. Engineering Standards

- **Zero Magic Values**: Utilizes `STATUS_*` constants within the `HasStatus` trait for standard
  system states.
- **Identity Invariant**: Full support for UUID-based entity relationships.
- **i18n Compliance**: All status labels are resolved via module-specific translation keys
  (`status::status.*`).

---

## 4. Verification & Validation (V&V)

Quality is ensured through **Pest v4**:

- **Unit Tests**: Verifies state transition persistence and localized label resolution.
- **Command**: `php artisan test modules/Status`

---

_The Status module ensures that every lifecycle transition in Internara is transparent, localized,
and auditable._
