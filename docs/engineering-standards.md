# Conventions and Rules: Engineering Standards

This document codifies the **Engineering Governance Framework** for the Internara system. It
establishes structural, semantic, and operational invariants to guarantee long-term maintainability,
architectural coherence, and systemic resilience in alignment with:

- ISO/IEC 11179 (Metadata & semantic clarity)
- ISO/IEC 25010 (Maintainability & reliability model)

Internara further adheres to established global engineering doctrines:

- SOLID
- Domain-Driven Design
- The Twelve-Factor App
- OWASP Top 10
- Clean Code heuristics (DRY, KISS, YAGNI)

> **Governance Mandate:** These conventions operationalize the authoritative System Requirements
> Specification (SyRS). All artifacts must preserve Multi-Language capability, Mobile-First
> responsiveness, Role-Based Authorization, and modular isolation invariants.

---

## 1. Static Quality Gate & Syntax Governance

### 1.1 Coding Standard Compliance

- **PSR-12 Enforcement**: All PHP code must conform to PSR-12.
- **Strict Typing**: `declare(strict_types=1);` is mandatory in every PHP file.
- **Automated Formatting**: `Laravel Pint` must be executed before repository synchronization.

### 1.2 PHP 8.4 Modernization Protocol

- Property Hooks supersede legacy `get...Attribute()` / `set...Attribute()` patterns.
- Type declarations must be explicit and non-ambiguous.
- Avoid dynamic properties and untyped arrays unless structurally unavoidable.

---

## 2. Modular Namespace Architecture

Internara implements a **Domain-Aligned Modular Monolith**.

### 2.1 Namespace Invariant

- The `src` segment is present in filesystem structure but **must be omitted** from namespace
  declarations.
- Example:
    - File: `modules/User/src/Models/User.php`
    - Namespace: `Modules\User\Models`

### 2.2 Structural Hierarchy

Standard path:

```
modules/{ModuleName}/src/{Domain}/{Layer}/{Class}.php
```

Preferred simplified path when Domain == Module:

```
modules/{ModuleName}/src/{Layer}/{Class}.php
```

Redundant nesting is prohibited.

### 2.3 PSR-4 Mapping Integrity

Each module must map:

```json
"Modules\\User\\": "src/"
```

The root composer provides a catch-all mapping to preserve discoverability.

---

## 3. Semantic Naming Doctrine (ISO/IEC 11179 Alignment)

Naming must reflect **conceptual intent**, not technical implementation.

### 3.1 Class Categories

- **Models**: Singular domain nouns (`CompetencyRubric`)
- **Services**: `{UseCase}Service`
- **Controllers / Livewire**: Action-oriented (`ManageAttendance`)
- **Contracts**: Capability-based naming (suffix `Interface` prohibited)
- **Enums**: Strict domain constants
- **Concerns (Traits)**: `Has*`, `Handles*`, `Manages*`

Redundant module prefixes inside namespaces are discouraged.

---

## 4. Identity & Persistence Architecture

### 4.1 UUID Invariant

All domain entities must use UUID v4 identifiers.

Cross-module physical foreign keys are **forbidden**. Referential integrity is enforced at the
Service Layer.

### 4.2 Single Profile Strategy

All national and institutional identifiers reside exclusively within `Profile`.

No duplication across modules is permitted.

### 4.3 Lifecycle & Temporal Traits

- `HasStatus` for auditable state transitions.
- `HasAcademicYear` for automatic academic scoping.

### 4.4 Archive Invariant

Soft-deletions require:

- `SoftDeletes` trait.
- Dispatching `{Entity}Archived` event.
- Listening modules must reconcile UI visibility accordingly.

---

## 5. Service Layer Governance

The Service Layer is the exclusive locus of business rules.

### 5.1 Architectural Responsibilities

- No business logic in Models or Livewire components.
- Cross-module communication must occur via **Service Contracts**.
- All state mutations require explicit authorization (`Gate::authorize()`).
- Multi-entity operations must be transactional.

### 5.2 Dual Service Model (CQRS Influence)

Inspired by Command Query Responsibility Segregation:

1. **Query Services** (extend `EloquentQuery`)
2. **Orchestration Services** (extend `BaseService`)

Separation improves cognitive clarity and traceability.

### 5.3 Dependency Inversion Enforcement

- Always depend on Contracts.
- Direct instantiation of foreign module Models is prohibited.
- Public infrastructure utilities may be consumed if intentionally exposed.

---

## 6. Support Layer (Technical Utilities)

The Support layer contains stateless, technical helpers.

- Business logic is forbidden.
- All helper classes must be `final`.
- Global functions must act as thin wrappers only.

Each helper must reside in its own file and be registered via `autoload.files`.

---

## 7. Presentation Layer Discipline

Livewire components function as boundary controllers.

### Invariants:

- No embedded business rules.
- Server-side validation mandatory.
- Mobile-first default construction.
- OWASP A01 and A03 mitigations must be enforced at entry boundaries.

---

## 8. Internationalization

Hard-coded user-facing strings are prohibited.

All strings must resolve via translation keys:

```
__('module::file.key')
```

---

## 9. Elimination of Magic Values

All semantic constants must be:

- PHP Enums (preferred)
- Class constants
- `config()` or `setting()` values

This reduces typo-based defects and preserves SSoT consistency.

---

## 10. Documentation Standards

All public methods require English PHPDoc explaining:

- Intent
- Contract
- Side effects (if any)

---

## 11. Database & Migration Governance

- UUID primary keys
- Anonymous migration classes
- No cross-module physical foreign keys
- Mandatory timestamps

All `find()` operations must throw a localized `RecordNotFoundException`.

---

## 12. Exception Handling & Reliability Model

Aligned with ISO/IEC 25010 (Reliability dimension).

### 12.1 Fault Categorization

1. **Validation Exceptions** – Boundary layer
2. **Domain Exceptions** – Service Layer
3. **System Exceptions** – Infrastructure

### 12.2 Security Invariants

- No schema/path leakage in production.
- PII masking mandatory.
- Correlation ID logging required.

### 12.3 Localization

All exception messages must resolve via translation keys.

---

## 13. Event-Driven Orchestration

Events must:

- Use past tense naming
- Carry lightweight payloads (UUID only)
- Interact with foreign modules exclusively via Service Contracts

---

## 14. Authorization Governance

Aligned with ISO/IEC 29146.

- Every domain model must have a Policy.
- Deny by default.
- Verify both permission and ownership context.

---

## 15. Verification & Validation

Aligned with ISO/IEC 29119.

- TDD-first (Pest)
- Requirement traceability via SyRS IDs
- Architecture enforcement via Arch testing

---

## 16. Repository Governance

- Conventional Commits: `type(module): description`
- Atomic commits only
- Documentation must evolve with code

---

## 17. Domain Isolation & Modular Integrity

### 17.1 Dependency Rules

- Infrastructure modules may be depended upon.
- Domain modules must never depend directly on other domain modules.

### 17.2 Data Isolation

- No cross-module physical constraints.
- No foreign Model instantiation.

### 17.3 Contract-First Communication

- All cross-module logic via Service Contracts.
- Tests must also respect Contracts.
- Concrete domain classes remain private unless explicitly public infrastructure.

### 17.4 UI Slot Injection

Cross-module UI composition must use SlotRegistry.

Direct Blade/Livewire invocation across modules is prohibited.

---

## 18. Accessibility Compliance

Aligned with WCAG 2.1 (Level AA).

- Semantic HTML required
- Keyboard navigability mandatory
- Minimum contrast ratio 4.5:1
- Proper ARIA usage
- Mandatory labeling for inputs

---

# Exception Handling: Engineering Resilience Standards

This section formalizes the **Fault Tolerance & Error Management Framework**, aligned with:

- ISO/IEC 25010 (Reliability)
- ISO/IEC 27034 (Security)

## Implementation Pattern

Boundary layer example:

```php
try {
    $this->journalService->lock($journalId);
} catch (JournalAlreadyLockedException $e) {
    $this->notify(__('journal::exceptions.already_locked'), type: 'warning');
}
```

Services must throw semantic exceptions from `src/Exceptions/`.

System exceptions must be logged with full trace internally but sanitized externally.

---

By enforcing these invariants, Internara preserves architectural clarity, security posture, modular
independence, and long-term maintainability across its domain-driven modular monolith.
