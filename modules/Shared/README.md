# Shared Module

The `Shared` module serves as the technical foundation of the Internara modular monolith. It
provides business-agnostic infrastructure, technical utilities, and standardized behavioral concerns
that are utilized across all domain modules.

> **Governance Mandate:** This module implements the foundational technical invariants required by
> the authoritative
> **[System Requirements Specification](../../docs/internal/system-requirements-specification.md)**.
> All components adhere to **ISO/IEC 25010** (Maintainability) and **ISO/IEC 27034** (Security).

---

## 1. Architectural Philosophy

- **Business Agnosticism:** Contains zero business logic specific to internships, schools, or users.
- **Zero Coupling:** Strictly prohibited from depending on any other module. It sits at the bottom
  of the dependency graph.
- **Protocol Standardization:** Enforces systemic consistency (e.g., UUIDs, Status management)
  through shared contracts and concerns.

---

## 2. Layered Internal Structure

Every component within the `Shared` module is organized according to its architectural layer.

### 2.1 Service Layer (Business-Agnostic Logic)

- **`EloquentQuery`**: An abstract base service providing a standardized implementation for
  model-based CRUD operations, filtering, sorting, and caching.
    - _Features_: Built-in `withTrashed` support, automated filter application, and generic type
      safety.
    - _Contract_: `Modules\Shared\Services\Contracts\EloquentQuery`.

### 2.2 Support Layer (Technical Utilities)

Resides in `src/Support/`. Contains stateless tools for technical operations.

- **`Formatter`**: Normalizes system strings, paths, and namespaces.
- **`Masker`**: Redacts sensitive data (PII) such as emails and phone numbers to satisfy privacy
  mandates.
- **`helpers.php`**: Global procedural functions for module health and status checks.

### 2.3 Persistence Layer (Model Concerns)

Resides in `src/Models/Concerns/`. Standardizes data behavior.

- **`HasUuid`**: Implements mandatory **UUID v4** identity generation (ISO/IEC 11179).
- **`HasStatus`**: Orchestrates state transitions and provides localized labels/colors for UI
  components.
- **`HasAcademicYear`**: Automatically scopes queries and data creation to the active academic
  cycle.

### 2.4 Presentation Layer (Livewire Concerns)

Resides in `src/Livewire/Concerns/`.

- **`ManagesRecords`**: A standardized trait for CRUD-heavy Livewire components, providing automated
  pagination, searching, and modal orchestration.

### 2.5 Infrastructure Layer (Provider Concerns)

Resides in `src/Providers/Concerns/`.

- **`ManagesModuleProvider`**: Automates the registration of configurations, translations, views,
  migrations, and service bindings for all modules.

---

## 3. Localization (i18n)

The `Shared` module provides the baseline translations for systemic feedback and UI elements.

- **Directory**: `lang/` (supporting `id` and `en`).
- **Standardized Keys**:
    - `shared::ui.*`: Common buttons and labels (Save, Cancel, Edit).
    - `shared::status.*`: Localized names for entity states (Active, Pending).
    - `shared::messages.*`: Standard success/error notifications.
    - `shared::exceptions.*`: Technical exception messages (Unique violation, Record not found).

---

## 4. Verification & Validation (V&V)

Quality is enforced through rigorous testing using **Pest v4**.

- **Unit Tests**: 100% coverage for Support Layer utilities and Model concerns.
- **Standards Compliance**: All code is verified against **PSR-12** and **Laravel Pint**.
- **Command**: `php artisan test modules/Shared`

---

_The Shared module is the engine room of Internara. It must remain clean, portable, and strictly
agnostic to preserve systemic integrity._
