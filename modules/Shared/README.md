# Shared Module

The `Shared` module serves as the project-agnostic engine room of Internara. It encapsulates
universal software engineering patterns and technical utilities that are strictly decoupled from any
business domain.

> **Governance Mandate:** This module implements the foundational infrastructure required to satisfy
> **[SYRS-NF-601]** (Isolation). All implementation must adhere to the
> **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## 1. Architectural Philosophy

- **Business Agnosticism**: Contains zero business logic specific to internships or vocational
  education.
- **Dependency Invariant**: Strictly prohibited from depending on any other module. It sits at the
  absolute bottom of the dependency graph.
- **Portability Invariant**: Components must remain reusable in any Laravel-based modular system
  without modification.

---

## 2. Core Components

### 2.1 Support Layer (Technical Utilities)

Resides in `src/Support/`. All classes are declared as **`final`**.

- **`Formatter`**: Normalizes paths, namespaces, and provides Indonesian-aware formatting for
  currency, dates, and phone numbers.
- **`Masker`**: Redacts sensitive data (PII) from logs and UI views.
- **`Asset`**: Orchestrates absolute URL resolution for modular static assets.

### 2.2 Service Layer (Standardized CRUD)

- **`EloquentQuery`**: An abstract base implementation for standardized model-based queries,
  filtering, and persistence.
    - _Contract_: `Modules\Shared\Services\Contracts\EloquentQuery`.

### 2.3 Persistence Layer (Foundation Concerns)

- **`HasUuid`**: Implements mandatory **UUID v4** identity generation.

### 2.4 Presentation Layer (UI Orchestration)

- **`ManagesRecords`**: A standardized trait for CRUD-heavy Livewire components, providing automated
  pagination and searching.

---

## 3. Verification & Validation (V&V)

Reliability is ensured through the mathematical verification of technical logic.

- **Unit Tests**: 100% behavioral coverage for all `Support` and `Concerns` classes.
- **Standard**: Adheres to the **Mirroring Invariant** (`tests/Unit/Support/FormatterTest.php`).
- **Command**: `php artisan test modules/Shared`

---

_The Shared module provides the technical certainty required to build complex business domains on a
stable foundation._
