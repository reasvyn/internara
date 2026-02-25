# Conventions and Rules: Engineering Standards

This document codifies the **Construction Standards** for the Internara project, ensuring semantic
consistency, maintainability, and structural integrity according to **ISO/IEC 11179** (Metadata) and
**ISO/IEC 25010** (Maintainability).

> **Governance Mandate:** These conventions serve as the technical implementation of the
> authoritative **[System Requirements Specification](specs.md)**. All software artifacts must
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

## 2. Semantic Namespacing & Internal Structure

To maintain brevity and semantic clarity in a modular environment, Internara enforces the **src
Omission** rule and a **Domain-Driven** internal structure.

### 2.1 The `src` Invariant

- **Namespace Invariant**: The `src` segment **must be omitted** from the namespace declaration.
- **Rationale**: Aligns modular namespaces with standard Laravel conventions and reduces cognitive
  load during cross-module integration.

### 2.2 Internal Directory Structure (Modular DDD)

Modules utilize a DDD-inspired hierarchy to organize logic by business domain.

- **Standard Path**: `modules/{ModuleName}/src/{Domain}/{Layer}/{StudlyName}.php`
- **Simplified Path (Preferred)**: `modules/{ModuleName}/src/{Layer}/{StudlyName}.php`

#### 2.2.1 The Omission Invariant

To prevent "Redundant Nesting," Internara enforces the following omission rules:

1.  **Domain-to-Module Omission**: If the **Domain Name** is identical to the **Module Name**, the
    domain folder MUST be omitted.
    - _Correct_: `Modules/User/src/Models/User.php`
    - _Incorrect_: `Modules/User/src/User/Models/User.php`
2.  **src Omission**: The `src` segment is always omitted from the **PHP Namespace**, but present 
    in the **File System**.
    - _File Path_: `modules/User/src/Models/User.php`
    - _Namespace_: `namespace Modules\User\Models;` (✅ Correct)
3.  **Layer Precision**: Layers must follow standard Laravel/Internara naming (Models, Services,
    Livewire, Enums, etc.).

**Example Hierarchy**:

- **File Path**: `modules/Internship/src/Registration/Services/RegistrationService.php`
- **Namespace**: `namespace Modules\Internship\Registration\Services;` (✅ Correct)

---

## 3. Naming Conventions (ISO/IEC 11179 Alignment)

Names must reflect the **conceptual intent** of the entity, not its implementation detail.

### 3.1 Class Identifiers

- **Semantic & Contextual Naming**: Class names must be descriptive and context-aware. While
  avoiding redundant prefixes provided by the namespace is encouraged, clarity must never be
  sacrificed for brevity.
    - _Brevity_: Avoid repeating the module name if the namespace already makes it obvious (e.g.,
      `Modules\Internship\Services\RegistrationService` instead of `InternshipRegistrationService`).
    - _Context_: Ensure the name explicitly describes the entity or action (e.g.,
      `RecordNotFoundException` is preferred over `NotFoundException` because it specifies that a
      database record is the missing entity).
- **Controllers/Livewire**: PascalCase reflecting the user action or resource (e.g.,
  `ManageAttendance`).
- **Services**: PascalCase with the `Service` suffix (e.g., `AssessmentService`).
- **Models**: PascalCase, singular, reflecting the domain entity (e.g., `CompetencyRubric`).
- **Contracts (Interfaces)**: PascalCase, named by capability. **The `Interface` suffix is
  prohibited**.
    - **Layer-Specific Contracts**: reside within the layer's directory:
      `src/({Domain}/)<Layer>/Contracts/` (e.g.,
      `src/Registration/Services/Contracts/RegistrationService.php`).
    - **Module-Global Contracts**: reside in the root contracts directory: `src/Contracts/` (e.g.,
      `src/Contracts/Authenticatable.php`).
- **Concerns (Traits)**: PascalCase, prefixed with semantic verbs such as `Has`, `Can`, `Handles`,
  or `Manages` (e.g., `InteractsWithActivityLog`, `HandlesResponse`).
    - **Layer-Specific Concerns**: reside within the layer's directory:
      `src/({Domain}/)<Layer>/Concerns/` (e.g., `src/Models/Concerns/HasUuid.php`).
    - **Module-Global Concerns**: reside in the root concerns directory: `src/Concerns/` (e.g.,
      `src/Concerns/HasModuleMetadata.php`).
- **Enums**: PascalCase, located in `src/Enums/`, used for fixed status values and domain constants.

---

## 4. Identity & Persistence Standards

### 4.1 Identity: UUID Invariant

All domain entities must utilize **UUID v4** for identification to prevent enumeration and ensure
modular portability. System-level tables or key-value stores (e.g., `Setting`, migrations, jobs) may
utilize standard sequences or string keys.

- **Implementation**: Utilize the `HasUuid` concern from the `Shared` module.
- **SLRI (Software-Level Referential Integrity)**: **Physical foreign keys across module boundaries
  are strictly prohibited**. Referential integrity is maintained exclusively at the **Service
  Layer** using indexed UUID columns and contract-driven verification.

### 4.2 User Identifiers: The Single Profile Strategy

To prevent identity fragmentation, all institutional and national user identifiers must reside
exclusively within the `Profile` model.

- **`national_identifier`**: The authoritative field for national-level IDs (e.g., NIP for teachers,
  NISN for students).
- **`registration_number`**: The authoritative field for institution-specific IDs (e.g., NIS for
  students).
- **Mandate**: No other module or model may define columns for these identifiers. They must be
  retrieved or verified through the `ProfileService`.

### 4.3 State Lifecycle: `HasStatus`

Operational entities must track their lifecycle transitions using the `HasStatus` concern from the
`Status` module.

- **Rationale**: Provides an immutable audit trail of state changes ("who", "when", "why") as
  required for monitoring.

### 4.3 Temporal Scoping: `HasAcademicYear`

Data must be automatically scoped by the active academic cycle using the `HasAcademicYear` concern
from the `Core` module.

- **Mechanism**: The `HasAcademicYear` concern filters all queries by the value of
  `setting('active_academic_year')`.

### 4.4 Semantic Identity: App vs. Brand

To ensure clear attribution and institutional flexibility, Internara distinguishes between two types
of identity:

- **`app_name` (Product Identity)**: Defined in `app_info.json` (SSoT). This represents the software
  itself ("Internara") and is used for technical metadata, versioning, and system-level attribution.
- **`brand_name` (Instance Identity)**: Managed via `setting('brand_name')`. This represents the
  specific institution using the system (e.g., "SMK Negeri 1 Jakarta"). It is used for UI branding,
  reports, and communication.
- **Fallback Rule**: `brand_name` should always fallback to `app_name` if no custom brand is
  defined.

---

## 5. Application Logic: The Service Layer

The **Service Layer** is the exclusive repository for business logic and orchestration.

- **Business Logic Restriction**: Services must strictly contain logic related to domain rules,
  workflows, and cross-module orchestration.
- **Decoupling**: Services must interact with other modules only through their respective Service
  Contracts.

### 5.1 The `EloquentQuery` Pattern

**Mandate**: All domain services MUST extend `Modules\Shared\Services\EloquentQuery`.

- **Standardized API**: `all()`, `paginate()`, `create()`, `update()`, `delete()`, `find()`,
  `query()`.
- **Logic Placement**: Use the Service to encapsulate a complete "Business Use Case."
- **Verification Invariant**: Every state-altering method (Create/Update/Delete) within a Service
  MUST explicitly invoke `Gate::authorize()` to ensure Policy-First security.
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

## 6. Infrastructure Utilities: The Support Layer

The **Support Layer** (`src/Support/`) contains project-specific utilities, helpers, and static
tools that provide technical capabilities without containing business logic.

- **Technical Scope**: Reserved for stateless, technical operations such as string formatting, data
  masking, and mathematical utilities.
- **Service vs. Support**: If logic defines a business rule (e.g., "how an internship is
  validated"), it resides in a **Service**. If it defines a technical tool (e.g., "how to normalize
  a path"), it resides in **Support**.
- **Semantic Class Structure**: Utilities must be organized into semantic static classes based on
  their domain of responsibility (e.g., `Support\Formatter.php`, `Support\Module.php`).
- **Pragmatic SOC**: While Separation of Concerns is vital, avoid systemic bloat. Logic that is
  highly specific to a single class and unlikely to be reused should remain encapsulated within that
  class rather than being forced into an external utility or trait.
- **Finality Invariant**: All helper and support classes must be declared as **`final`** to prevent
  inheritance and ensure stateless integrity.

### 6.1 Global Helper Functions (Wrappers)

To enhance developer productivity and code brevity, technical utilities may be exposed as global
helper functions. These functions must strictly adhere to the following protocols:

- **Wrapper Pattern**: Global functions must act solely as "syntactic sugar" wrappers for the
  underlying static classes in the Support layer. Direct implementation of logic within a function
  is prohibited.
- **Naming Convention**: Utilize `snake_case` for global functions to align with Laravel's core
  helper conventions (e.g., `is_active_module()` wraps `Module::isActive()`).
- **Organization**: Each global function must reside in its own file within the `src/Functions/`
  directory, named after the function (e.g., `src/Functions/is_testing.php`). Multiple functions
  within a single file are prohibited.
- **Autoloading**: Functions must be registered via the `autoload.files` section of the module's
  `composer.json`. Each file path must be explicitly listed to ensure proper discovery by the
  Composer autoloader.
    - _Example_:
        ```json
        "autoload": {
            "files": [
                "src/Functions/is_active_module.php",
                "src/Functions/setting.php"
            ]
        }
        ```

---

## 7. Presentation Layer: Livewire Components

Livewire components serve as thin orchestrators between the UI and the Service Layer.

- **The Thin Component Rule**: Implementation of business logic within Livewire components is
  forbidden. Components must delegate immediately to the appropriate Service.
- **Mobile-First Construction**: UI logic and styling must default to mobile layouts, utilizing
  Tailwind v4 breakpoints for larger viewports.
- **Validation Invariant (ISO/IEC 27034)**: Validation must occur at the earliest possible boundary
  (PEP). Livewire components must utilize `rules()` or Form Requests to ensure data integrity before
  Service invocation.

### 7.1 Unified Record Management: `RecordManager`

... (existing content) ...

### 7.2 Volt Components (Functional UI)

For reactive UI elements that require minimal server-side state, Internara utilizes **Livewire
Volt**.

- **Location**: Volt files must reside in the module's `resources/views/livewire/` directory (e.g.,
  `modules/Admin/resources/views/livewire/dashboard.blade.php`).
- **Registration**: These components are automatically discovered and registered via the
  `Volt::mount()` mechanism integrated into the `Shared` module's foundational providers.

### 7.3 Slot Injection Implementation

Modules register their UI contributions into the design system using the following syntax in their
`ServiceProvider`:

```php
protected function viewSlots(): array
{
    return [
        'sidebar.menu' => [
            'internship::menu-item',
            'attendance::menu-item',
        ],
        'dashboard.widgets' => [
            'admin::analytics-widget' => ['role' => 'admin'],
        ],
    ];
}
```

---

## 8. Internationalization (i18n) Standards

Internara is a multi-language system. Hard-coding of user-facing text is a **Critical Quality
Violation**.

- **Translation Protocol**: All text must be resolved via `__('module::file.key')`.
- **Contextual Formatting**: Dates, currency, and numerical values must be formatted according to
  the active locale (`id` or `en`).

---

## 9. Avoiding Magic Values

Predictability and maintainability require the elimination of "Magic Values" (hard-coded strings or
numbers with semantic meaning).

- **Principle**: Any value that carries semantic meaning (e.g., a status code, a role name, a
  specific limit) must be abstracted.
- **Implementation**:
    - **Enums (Recommended)**: Use PHP 8.1+ Enums for fixed sets of values (e.g.,
      `src/Enums/RegistrationStatus.php`). This is the preferred approach for better type safety and
      clarity.
    - **Constants**: Use class constants for internal model or service configuration.
    - **Config/Settings**: Use `config()` for environment-static values and `setting()` for values
      that require runtime administrative control.
- **Rationale**: Reduces the risk of "typo-based" bugs and provides a single source of truth for
  value changes.

---

## 10. Documentation (The Engineering Record)

Every public class and method must include professional PHPDoc in English.

- **Analytical Intent**: Describe the "why" and "what," not the obvious "how."
- **Strict Typing**: All `@param` and `@return` tags must match the method signature's strict types.

---

## 11. Database Migrations & Schema Design

Migrations must ensure modular portability and data integrity without physical coupling.

- **UUID Invariant**: Primary keys must use `uuid('id')->primary()` (or resolved via config).
  Foreign keys within the same module should use `foreignUuid()`.
- **Strict Isolation**: Physical foreign keys across module boundaries are **Forbidden**. Use
  indexed UUID columns (e.g., `$table->uuid('user_id')->index()`) and maintain referential integrity
  at the Service Layer.
- **Anonymous Migrations**: All migrations must utilize anonymous classes
  (`return new class extends Migration`).
- **Standard Columns**: Always include `$table->timestamps()` for auditability.

---

## 12. Exception Handling & Resilience (ISO/IEC 25010)

Fault management must be disciplined, secure, and localized.

### 12.1 Environment & Debugging Standards

To ensure security and prevent information leakage, all debugging activities must be
environment-aware.

- **Global Helpers**: Use `is_debug_mode()`, `is_development()`, `is_testing()`, and
  `is_maintenance()` helpers instead of direct `env()` or `config()` calls for environment
  detection.
- **Conditional Logging**: All `Log::debug()` or `console.log()` statements MUST be wrapped in a
  debug mode check.
    - _PHP_: `if (is_debug_mode()) { ... }`
    - _JS_: `if (isDebugMode()) { ... }`
- **PII Redaction**: Never log raw Personally Identifiable Information (PII). Use the `Masker`
  utility if logging is necessary.
- **Production Hygiene**: All experimental or diagnostic code must be removed or strictly guarded by
  `is_development()` before merging into the `main` branch.

- **Semantic Exceptions**: Throw custom, module-specific exceptions (e.g., `JournalLockedException`)
  from the Service Layer.
- **Sanitization Invariant**: Exceptions rendered to end-users must NEVER expose system internals
  (schema, paths, traces). Use generic messages in production.
- **Localization**: Exception messages must be resolved via translation keys
  (`module::exceptions.key`).
- **PII Protection**: Logging must redact sensitive data (passwords, tokens) to satisfy privacy
  mandates.

---

## 13. Asynchronous Orchestration (Events)

Utilize events for decoupled cross-module side-effects.

- **Naming Semantic**: Events must use the **Past Tense** (`{Entity}{Action}ed`, e.g.,
  `InternshipStarted`).
- **Lightweight Payloads**: Event constructors should only accept the **UUID** of the entity or a
  lightweight DTO to prevent serialization overhead.
- **Isolation Constraint**: Listeners must interact with foreign modules exclusively via Service
  Contracts, never direct Model access.

---

## 14. Authorization Policies (ISO/IEC 29146)

Authorization logic must be centralized and context-aware.

- **Policy Enforcement Points (PEP)**: Every domain model must have a corresponding **Policy**
  class.
- **Strict Typing**: All policy methods must declare strict types for the `User` subject and the
  `Model` object.
- **Deny by Default**: Policies must explicitly return `false` if conditions are not met. Ambiguity
  is a security defect.
- **Ownership Verification**: Always verify functional permission (`$user->can()`) AND context
  (e.g., `$user->id === $resource->user_id`).

---

## 15. Verification & Validation (ISO/IEC 29119)

Tests serve as the executable proof of requirement fulfillment.

- **TDD-First**: Construct verification suites (Pest v4) prior to implementation.
- **Traceability**: Link tests to SyRS requirements or architectural invariants using
  `test('it fulfills [SYRS-ID]')`.
- **Architecture Testing**: Enforce modular isolation using Pest's Arch plugin.
- **Coverage**: Maintain a minimum of 90% behavioral coverage for domain modules.

---

## 16. Repository Management & Traceability

Standards for maintaining the engineering record.

- **Conventional Commits**: All commit messages must follow the `type(module): description` pattern
  (e.g., `feat(user): add uuid-based identity`).
- **Atomic Commits**: Each commit must represent a single, logical unit of work.
- **Doc-as-Code**: Every code modification must trigger a corresponding update to technical
  documentation to prevent desynchronization.

---

## 17. Zero-Coupling & Domain Isolation Rules

To preserve the modular integrity of the system, strict boundaries are enforced between business
domains.

### 17.1 Dependency Direction

- **Public Modules**: Modules such as `Shared`, `UI`, `Core`, `Support`, `Auth`, and `Exception` are
  considered infrastructure providers. Domain modules **may** depend on these directly.
- **Domain Modules**: Modules representing business logic (e.g., `Internship`, `Journal`,
  `Attendance`) **must never** depend on each other directly.

### 17.2 Data Isolation

- **No Physical Foreign Keys**: Cross-module database relationships must use indexed UUID columns
  without physical constraints.
- **Model Isolation**: Direct instantiation or usage of a Model class from another domain module is
  **Strictly Forbidden**.

### 17.3 Logic Isolation (Service Contracts)

- **Contract-First Communication**: Inter-module logic requests must be handled through the target
  module's **Service Contract** (Interface). This applies to all layers, including **Tests**.
- **Service Dependency**: If Module A requires data from Module B, it must inject the Service
  Contract of Module B into its own Service.
- **Testing Invariant**: In verification suites, developers are encouraged to utilize **Service
  Contracts** of external modules to minimize mocking overhead.
- **Concrete Class Restriction**: The use of **Domain-Specific Concrete Classes** (Models,
  Controllers, or private implementations) from other modules is **Strictly Forbidden**. However,
  **Public Infrastructure Classes** (Stateless Helpers, Utilities, or Facades) that are
  intentionally designed for public consumption and are not coupled to the source module's internal
  architecture may be utilized. Verification should verify the contract's output, not the external
  module's internals.

### 17.4 Presentation Isolation (Slot Injection)

- **Direct Component Restriction**: Calling a Blade/Livewire component from another domain module
  directly (e.g., `<x-internship::... />` inside `Journal`) is **Forbidden**.
- **Slot Pattern**: Cross-module UI integration must utilize the **Slot Injection** pattern via the
  `SlotRegistry` provided by the `UI` module.

---

## 18. Accessibility (A11y Aware) Standards

Internara is committed to inclusivity and usability for all users, adhering to **WCAG 2.1 (Level
AA)** standards.

- **Semantic HTML**: Mandatory usage of semantic tags (`<main>`, `<nav>`, `<article>`, `<button>`)
  to provide context to assistive technologies.
- **Keyboard Navigability**: Every interactive element must be reachable and operable via keyboard
  tabbing. Focus states must be clearly visible.
- **Color Contrast**: All text and UI elements must maintain a minimum contrast ratio of **4.5:1**
  to ensure legibility.
- **ARIA Attributes**: Utilize ARIA roles and labels (`aria-label`, `aria-expanded`, etc.) when
  native HTML semantics are insufficient.
- **Labeling Invariant**: Every input element must have a corresponding `<label>` or an `aria-label`
  to ensure screen reader compatibility.

---

_Non-compliance with these conventions indicates a failure of architectural integrity and will
result in the rejection of the artifact during the V&V phase._

---

# Exception Handling: Engineering Resilience Standards

This document formalizes the **Fault Tolerance** and **Error Management** protocols for the
Internara project, adhering to **ISO/IEC 25010** (Reliability) and **ISO/IEC 27034** (Application
Security). It defines the strategy for ensuring system stability while providing secure, traceable,
and localized feedback to stakeholders.

> **Governance Mandate:** Exception handling must prioritize system integrity and the protection of
> sensitive information as mandated by the authoritative **[Internara Specs](specs.md)**.

---

## 1. Categorization of Faults & Exceptions

Internara utilizes a tiered classification system to manage system anomalies based on their origin
and intended resolution path.

### 1.1 Validation Exceptions (Anticipated Errors)

- **Origin**: Boundary layers (Livewire Components, API Controllers).
- **Behavior**: Handled via standard validation protocols.
- **Feedback**: Immediate, localized feedback identifying the specific invalid input.

### 1.2 Domain Exceptions (Business Logic Violations)

- **Origin**: The **Service Layer**.
- **Behavior**: Custom exceptions (e.g., `JournalLockedException`) representing specific business
  rule violations.
- **Feedback**: Transformed into user-friendly notifications in the UI layer.

### 1.3 System Exceptions (Non-Recoverable Failures)

- **Origin**: Infrastructure (Database, Network, Filesystem).
- **Behavior**: Logged with full stack traces for forensic analysis.
- **Feedback**: Abstracted into a generic, secure notification (e.g., "System Anomaly") in
  production to prevent information leakage.

---

## 2. Security & Privacy Invariants (ISO/IEC 27034)

Exception handling must never compromise the system's security posture or expose Personally
Identifiable Information (PII).

### 2.1 Information Leakage Prevention

- **Sanitization**: Error messages intended for end-users must never include database schema
  details, file paths, or variable values.
- **PII Masking**: The automated logging subsystem must redact sensitive fields (e.g., `email`,
  `password`, `ssn`) from all log payloads.

### 2.2 Forensic Traceability

- **Correlation**: Every exception must be associated with a unique **Correlation ID** and the
  authenticated **User ID** to facilitate cross-module log analysis.
- **Contextual Logging**: Capture the specific module state and operation parameters while ensuring
  compliance with privacy mandates.

---

## 3. Localization (i18n) Standards

Hard-coding of exception messages is a **Critical Quality Violation**.

- **Protocol**: All exception messages must be resolved via translation keys.
- **Mapping**:
    - **Module-Specific**: `__('module::exceptions.key')`.
    - **Generic/Shared**: `__('exception::messages.key')`.

---

## 4. Implementation Pattern

### 4.1 Boundary Level Handling

Livewire components must orchestrate the transition from Domain Exceptions to UI-friendly feedback.

```php
try {
    $this->journalService->lock($journalId);
} catch (JournalAlreadyLockedException $e) {
    $this->notify(__('journal::exceptions.already_locked'), type: 'warning');
} catch (DomainException $e) {
    $this->notify(__('exception::messages.unauthorized'), type: 'error');
}
```

### 4.2 Service Layer Propagation

Services should throw semantic, custom exceptions that reflect the domain rule being violated.

- **Base Requirement**: Custom exceptions must reside in the `src/Exceptions/` directory of their
  respective module.

---

_By adhering to these resilience standards, Internara ensures that faults are managed in a
disciplined, secure, and user-centric manner, preserving the reliability of the modular monolith._
