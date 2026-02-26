# Shared Module

The `Shared` module serves as the project-agnostic **Engine Room** of Internara. It encapsulates
universal software engineering patterns and technical utilities that are strictly decoupled from any
business domain, adhering to the **S3 (Secure, Sustain, Scalable)** philosophy.

> **Governance Mandate:** This module implements the foundational infrastructure required to satisfy
> **[SYRS-NF-601]** (Isolation). All implementation must adhere to the
> **[Coding Conventions](../../docs/dev/conventions.md)**.

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
- **`AppInfo`**: Static provider for application-wide metadata (name, version, license) stored in
  `app_info.json`.
- **`Asset`**: Orchestrates absolute URL resolution for modular static assets.

### 2.2 Service Layer (Standardized CRUD)

- **`EloquentQuery`**: An abstract base implementation for standardized model-based queries,
  filtering, and persistence.
    - _Contract_: `Modules\Shared\Services\Contracts\EloquentQuery`.
    - _Search & Sort_: Automatically handles `$searchable` and `$sortable` array properties.
      Supports nested relationship searching (e.g., `['name', 'user.email']`).
    - _API_: `paginate(['search' => 'query', 'sort_by' => 'created_at'])`.

### 2.3 Persistence Layer (Foundation Concerns)

- **`HasUuid`**: Implements mandatory **UUID v4** identity generation.
- **`HasAcademicYear` (Core Integration)**: Automatically scopes queries to the active institutional
  cycle.
    - _Bypassing_: To query historical data, use the `withoutAcademicYear()` scope or the underlying
      Eloquent `withoutGlobalScope` method in specific service logic.

---

## 3. Verification & Validation (V&V)

Reliability is ensured through the mathematical verification of technical logic.

- **Unit Tests**: 100% behavioral coverage for all `Support` and `Concerns` classes.
- **Standard**: Adheres to the **Mirroring Invariant** (`tests/Unit/Support/FormatterTest.php`).
- **Command**: `php artisan test modules/Shared`

---

_The Shared module provides the technical certainty required to build complex business domains on a
stable foundation._
