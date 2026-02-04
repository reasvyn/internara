# School Module

The `School` module manages the authoritative identity and configuration of the educational
institution. It serves as the primary source for branding data (name, logo) utilized across the
Internara ecosystem.

> **Governance Mandate:** This module implements the requirements defined in the authoritative **[System Requirements Specification](../../docs/developers/specs.md)**. All implementation must adhere to the **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## 1. Architectural Role

As a **Core Domain Module**, the `School` module provides foundational institutional context. It
interacts with the `Shared` module for technical infrastructure and the `Media` module for asset
management, while providing data to all other modules for display purposes.

---

## 2. Core Components

### 2.1 Service Layer

- **`SchoolService`**: Orchestrates the management of school records, enforcing single-record
  constraints if configured.
    - _Features_: Automated logo handling and standardized institutional data retrieval.
    - _Contract_: `Modules\School\Services\Contracts\SchoolService`.

### 2.2 Persistence Layer

- **`School` Model**: Represents the institution.
    - _Identities_: Uses **UUID v4** for secure identification.
    - _Collections_: Manages the `COLLECTION_LOGO` for institutional branding.
    - _Concerns_: Implements `HasDepartmentsRelation` and `HasInternshipsRelation`.

### 2.3 Presentation Layer

- **`SchoolManager`**: A Livewire component providing a comprehensive administrative interface for
  institutional management.

---

## 3. Engineering Standards

- **Zero Magic Values**: Utilizes `COLLECTION_LOGO` and standard HTTP constants for status and
  conflict management.
- **Strict Isolation**: External modules interact with institutional data exclusively via the
  `SchoolService` contract.
- **Context-Aware Naming**: Prioritizes semantic clarity while maintaining brevity within the module
  namespace.

---

## 4. Verification & Validation (V&V)

Quality is ensured through **Pest v4**:

- **Feature Tests**: Validates administrative access controls, institutional data updates, and logo
  management.
- **Command**: `php artisan test modules/School`

---

_The School module establishes the institutional foundation required for academic accountability._
