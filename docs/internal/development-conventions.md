# Development Conventions: Engineering Standards

This document codifies the **Construction Standards** for the Internara project, ensuring semantic
consistency, maintainability, and structural integrity according to **ISO/IEC 11179** (Metadata) and
**ISO/IEC 25010** (Maintainability).

> **Governance Mandate:** These conventions serve as the technical implementation of the
> authoritative **[Internara Specs](../internal/internara-specs.md)**. All software artifacts must
> satisfy the requirements for Multi-Language support, Mobile-First responsiveness, and Role-Based
> security defined in the SSoT.

---

## 1. Syntax & Static Analysis (Quality Gate)

Internara utilizes rigorous static analysis to ensure code interoperability and prevent latent
defects.

- **PSR-12 Alignment**: All PHP source code must strictly adhere to the PSR-12 Extended Coding
  Style.
- **Strict Typing**: Every PHP file must declare `strict_types=1` to ensure type safety and
  predictability.
- **Automated Linting**: Adherence to **Laravel Pint** standards is mandatory. Run
  `./vendor/bin/pint` prior to any repository synchronization.

---

## 2. Semantic Namespacing (The `src` Invariant)

To maintain brevity and semantic clarity in a modular environment, Internara enforces the **src
Omission** rule for namespaces.

- **Directory Structure**: All module-specific logic resides in `modules/{ModuleName}/src/`.
- **Namespace Invariant**: The `src` segment **must be omitted** from the namespace declaration.
- **Rationale**: Aligns modular namespaces with standard Laravel conventions and reduces cognitive
  load during cross-module integration.

**Example**:

- **File Path**: `modules/Internship/src/Services/InternshipService.php`
- **Namespace**: `namespace Modules\Internship\Services;` (✅ Correct)

---

## 3. Naming Conventions (ISO/IEC 11179 Alignment)

Names must reflect the **conceptual intent** of the entity, not its implementation detail.

### 3.1 Class Identifiers

- **Controllers/Livewire**: PascalCase reflecting the user action or resource (e.g.,
  `ManageAttendance`).
- **Services**: PascalCase with the `Service` suffix (e.g., `AssessmentService`).
- **Models**: PascalCase, singular, reflecting the domain entity (e.g., `CompetencyRubric`).
- **Contracts (Interfaces)**: PascalCase, named by capability. **The `Interface` suffix is
  prohibited**.
    - **Service Contracts**: `Services/Contracts/` (e.g., `InternshipService`).
    - **General Contracts**: `Contracts/` (e.g., `Authenticatable`).
- **Concerns (Traits)**: PascalCase, prefixed with semantic verbs such as `Has`, `Can`,
  `Handles`, or `Manages` (e.g., `HasAuditLog`, `HandlesResponse`). Naming should be flexible to
  context while remaining semantically standardized.
- **Enums**: PascalCase, located in `src/Enums/`, used for fixed status values and domain constants.

---

## 4. Identity & Persistence Standards

### 4.1 Identity: UUID Invariant

All entities must utilize **UUID v4** for identification to prevent enumeration and ensure modular
portability.

- **Implementation**: Utilize the `HasUuid` concern from the `Shared` module.
- **Isolation Constraint**: **Physical foreign keys across module boundaries are forbidden**.
  Referential integrity is maintained at the Service Layer using indexed UUID columns.

### 4.2 State Lifecycle: `HasStatuses`

Operational entities must track their lifecycle transitions using the `HasStatuses` concern.

- **Rationale**: Provides an immutable audit trail of state changes ("who", "when", "why") as
  required for monitoring.

### 4.3 Temporal Scoping: `HasAcademicYear`

Data must be automatically scoped by the active academic cycle.

- **Mechanism**: The `HasAcademicYear` concern filters all queries by the value of
  `setting('active_academic_year')`.

---

## 5. Application Logic: The Service Layer

The **Service Layer** is the exclusive repository for business logic and orchestration.

### 5.1 The `EloquentQuery` Pattern

Domain services should extend `Modules\Shared\Services\EloquentQuery` to utilize standardized CRUD
operations.

- **Standardized API**: `all()`, `paginate()`, `create()`, `update()`, `delete()`, `find()`,
  `query()`.
- **Overriding Logic**: Only override base methods when injecting cross-module side-effects or
  complex domain events. Always utilize database **Transactions** for multi-entity operations.

### 5.2 Service Design Invariants

- **Contract-First**: Always type-hint **Contracts**, never concrete implementations, for
  cross-module dependencies.
- **Strict Isolation**: Direct instantiation of external module classes (especially Models) is
  strictly prohibited. All inter-module requests must pass through the target module's **Service
  Contract**.
- **Configuration Hygiene**: Never call `env()`. Use `config()` for static values and the
  `setting()` helper for dynamic application values.

---

## 6. Presentation Layer: Livewire Components

Livewire components serve as thin orchestrators between the UI and the Service Layer.

- **The Thin Component Rule**: Implementation of business logic within Livewire components is
  forbidden. Components must delegate immediately to the appropriate Service.
- **Mobile-First Construction**: UI logic and styling must default to mobile layouts, utilizing
  Tailwind v4 breakpoints for larger viewports.

---

## 7. Internationalization (i11n) Standards

Internara is a multi-language system. Hard-coding of user-facing text is a **Critical Quality
Violation**.

- **Translation Protocol**: All text must be resolved via `__('module::file.key')`.
- **Contextual Formatting**: Dates, currency, and numerical values must be formatted according to
  the active locale (`id` or `en`).

---

## 8. Documentation (The Engineering Record)

Every public class and method must include professional PHPDoc in English.

- **Analytical Intent**: Describe the "why" and "what," not the obvious "how."
- **Strict Typing**: All `@param` and `@return` tags must match the method signature's strict types.

---

_Non-compliance with these conventions indicates a failure of architectural integrity and will
result in the rejection of the artifact during the V&V phase._
