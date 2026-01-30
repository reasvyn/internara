# Conventions and Rules: Engineering Standards

This document codifies the **Construction Standards** for the Internara project, ensuring semantic
consistency, maintainability, and structural integrity according to **ISO/IEC 11179** (Metadata) and
**ISO/IEC 25010** (Maintainability).

> **Governance Mandate:** These conventions serve as the technical implementation of the
> authoritative **[System Requirements Specification](system-requirements-specification.md)**. All software artifacts must
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
    - **Layer-Specific Contracts**: reside within the layer's directory: `src/<Layer>/Contracts/`
      (e.g., `src/Services/Contracts/InternshipService.php`).
    - **Module-Global Contracts**: reside in the root contracts directory: `src/Contracts/`
      (e.g., `src/Contracts/Authenticatable.php`).
- **Concerns (Traits)**: PascalCase, prefixed with semantic verbs such as `Has`, `Can`, `Handles`,
  or `Manages` (e.g., `HasAuditLog`, `HandlesResponse`).
    - **Layer-Specific Concerns**: reside within the layer's directory: `src/<Layer>/Concerns/`
      (e.g., `src/Models/Concerns/HasUuid.php`).
    - **Module-Global Concerns**: reside in the root concerns directory: `src/Concerns/`
      (e.g., `src/Concerns/HasModuleMetadata.php`).
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
- **Validation Invariant (ISO/IEC 27034)**: Validation must occur at the earliest possible boundary (PEP). Livewire components must utilize `rules()` or Form Requests to ensure data integrity before Service invocation.

---

## 7. Internationalization (i18n) Standards

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

## 9. Database Migrations & Schema Design

Migrations must ensure modular portability and data integrity without physical coupling.

- **UUID Invariant**: Primary keys must use `uuid('id')->primary()` (or resolved via config). Foreign keys within the same module should use `foreignUuid()`.
- **Strict Isolation**: Physical foreign keys across module boundaries are **Forbidden**. Use indexed UUID columns (e.g., `$table->uuid('user_id')->index()`) and maintain referential integrity at the Service Layer.
- **Anonymous Migrations**: All migrations must utilize anonymous classes (`return new class extends Migration`).
- **Standard Columns**: Always include `$table->timestamps()` for auditability.

---

## 10. Exception Handling & Resilience (ISO/IEC 25010)

Fault management must be disciplined, secure, and localized.

- **Semantic Exceptions**: Throw custom, module-specific exceptions (e.g., `JournalLockedException`) from the Service Layer.
- **Sanitization Invariant**: Exceptions rendered to end-users must NEVER expose system internals (schema, paths, traces). Use generic messages in production.
- **Localization**: Exception messages must be resolved via translation keys (`module::exceptions.key`).
- **PII Protection**: Logging must redact sensitive data (passwords, tokens) to satisfy privacy mandates.

---

## 11. Asynchronous Orchestration (Events)

Utilize events for decoupled cross-module side-effects.

- **Naming Semantic**: Events must use the **Past Tense** (`{Entity}{Action}ed`, e.g., `InternshipStarted`).
- **Lightweight Payloads**: Event constructors should only accept the **UUID** of the entity or a lightweight DTO to prevent serialization overhead.
- **Isolation Constraint**: Listeners must interact with foreign modules exclusively via Service Contracts, never direct Model access.

---

## 12. Authorization Policies (ISO/IEC 29146)

Authorization logic must be centralized and context-aware.

- **Policy Enforcement Points (PEP)**: Every domain model must have a corresponding **Policy** class.
- **Strict Typing**: All policy methods must declare strict types for the `User` subject and the `Model` object.
- **Deny by Default**: Policies must explicitly return `false` if conditions are not met. Ambiguity is a security defect.
- **Ownership Verification**: Always verify functional permission (`$user->can()`) AND context (e.g., `$user->id === $resource->user_id`).

---

## 13. Verification & Validation (ISO/IEC 29119)

Tests serve as the executable proof of requirement fulfillment.

- **TDD-First**: Construct verification suites (Pest v4) prior to implementation.
- **Traceability**: Link tests to SyRS requirements or architectural invariants using `test('it fulfills [SYRS-ID]')`.
- **Architecture Testing**: Enforce modular isolation using Pest's Arch plugin.
- **Coverage**: Maintain a minimum of 90% behavioral coverage for domain modules.

---

## 14. Repository Management & Traceability

Standards for maintaining the engineering record.

- **Conventional Commits**: All commit messages must follow the `type(module): description` pattern (e.g., `feat(user): add uuid-based identity`).
- **Atomic Commits**: Each commit must represent a single, logical unit of work.
- **Doc-as-Code**: Every code modification must trigger a corresponding update to technical documentation to prevent desynchronization.

---

_Non-compliance with these conventions indicates a failure of architectural integrity and will
result in the rejection of the artifact during the V&V phase._
