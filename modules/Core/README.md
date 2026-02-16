# Core Module

The `Core` module provides the foundational building blocks that define the **Identity of
Internara**. It encapsulates global domain logic and static configurations required by all
functional modules.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/developers/specs.md)**. All implementation must
> adhere to the **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## 1. Architectural Role

As a **Global Domain Module**, `Core` acts as the system's "glue." It manages concepts that are
fundamental to the vocational education business domain but transcend individual functional modules.

---

## 2. Core Domains

### 2.1 Academic Domain

- **`HasAcademicYear`**: A persistence-layer concern that automatically scopes queries and data
  creation to the active academic cycle.
- **`AcademicYear` Support**: A final utility that generates dynamic academic year strings (e.g.,
  "2025/2026") based on the current date (transitioning in July).

### 2.2 Metadata Domain

- **`MetadataService`**: The single source of truth for application versioning, series code, and
  author attribution. It enforces architectural integrity and protects author rights.
    - _Contract_: `Modules\Core\Metadata\Services\Contracts\MetadataService`.
    - _API_: `getVersion()`, `getSeriesCode()`, `getAuthor()`.
- **`AppInfoCommand`**: Artisan command to audit and display system metadata.

### 2.3 Localization Domain

- **`SetLocale` Middleware**: Automatically manages application locale persistence based on session
  state (supporting `id` and `en`).

---

## 3. Engineering Standards

- **Zero Coupling**: `Core` depends only on `Shared`. It must never depend on functional domain
  modules (like `Internship` or `Journal`) to prevent circular dependencies.
- **Finality**: All helper classes within the `Support` folders are declared as `final`.

---

## 4. Verification & Validation (V&V)

- **Unit Tests**: Mirroring structure for academic and metadata logic.
- **Middleware Tests**: Verifying session-based locale switching.
- **Command**: `php artisan test modules/Core`

---

_The Core module ensures that every part of Internara operates within a consistent academic and
technical context._
