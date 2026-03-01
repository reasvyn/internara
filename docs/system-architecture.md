# Architecture Design (AD): Internara Modular Monolith

This document defines the formal **Architecture Design (AD)** of the Internara system in alignment
with **ISO/IEC 42010 (Systems and software engineering — Architecture description)**. It establishes
the architectural viewpoints, structural principles, governance policies, and design invariants that
collectively ensure long-term maintainability, controlled evolution, and systemic integrity.

---

# 1. Architectural Philosophy and Design Rationale

Internara adopts a **Modular Monolithic Architecture** as a deliberate strategic decision to
balance:

- **Domain Encapsulation**
- **Operational Simplicity**
- **Deployment Cohesion**
- **Cognitive Scalability**

The architectural objective is not absolute physical isolation, but **explicit boundary governance
with controlled coupling**.

The system enforces _logical modularity with contract-based interaction_, rather than
microservice-style runtime isolation.

---

## 1.1 Domain Decomposition Strategy (Logical View)

Internara is decomposed into cohesive **Domain Modules**, each representing a **Bounded Context** as
defined by **Domain-Driven Design (DDD)**.

### Decomposition Principles

- **Bounded Context Integrity** Each module defines its own Ubiquitous Language, aggregate rules,
  and invariants.

- **Aggregate Consistency Boundary** Domain invariants are enforced within aggregate roots owned by
  the module.

- **Autonomous Persistence Ownership** Each module governs its own persistence schema and data
  lifecycle.

Modularity is enforced at the **architectural contract boundary**, not at the file-system or
repository boundary.

---

## 1.2 Service-Oriented Application Core

Internara centralizes business logic within a structured **Application Service Layer**, adhering to:

- Single Responsibility Principle (SRP)
- Clear separation between orchestration and persistence
- Explicit dependency inversion

### Layer Responsibilities

**Presentation Layer**

- Livewire components responsible solely for request orchestration and UI state management.
- No embedded business rules.

**Application / Domain Services**

- Implement defined Service Contracts.
- Coordinate domain logic and enforce invariants.
- Execute transactional workflows.

**Persistence Abstractions**

- Eloquent-based models with UUID identity.
- Query abstraction layer (`EloquentQuery`) to standardize persistence operations.

### Interface-First Contract Model

All inter-module communication must occur through **Service Contracts (Interfaces)**. Concrete
implementations are resolved through dependency injection.

This enforces:

- Substitutability
- Testability
- Architectural transparency
- Explicit coupling declaration

---

## 1.3 Dependency Governance Model

Internara enforces **Contract-First Dependency Management**.

### Governing Rules

- Modules may depend only on **Interfaces**, never on concrete implementations.
- Dependency resolution must occur through container binding.
- Circular dependencies are prohibited.

Absolute zero dependency is not required. All dependencies must be:

1. Explicit
2. Interface-bound
3. Architecturally justified
4. Non-circular

This replaces rigid isolation with **controlled dependency governance**.

---

# 2. Architectural Views (ISO/IEC 42010 Perspective)

---

## 2.1 Logical View — Internal Module Structure

Each module adheres to a strict layered architecture:

1. **Presentation Layer** Livewire components.

2. **Application/Domain Layer** Service implementations fulfilling Contracts.

3. **Persistence Layer** Eloquent models with UUID identity.

This layered decomposition ensures:

- Separation of concerns
- Predictable dependency flow
- Reduced cognitive complexity

---

## 2.2 Development View — Modular Infrastructure Stratification

Internara defines foundational tiers that govern dependency direction and reuse boundaries.

| Tier    | Responsibility                         | Architectural Role              |
| ------- | -------------------------------------- | ------------------------------- |
| Shared  | Cross-cutting abstractions             | Portable foundation             |
| Core    | System-wide domain primitives          | Identity & configuration anchor |
| Support | Tooling & orchestration infrastructure | Operational bridge              |
| UI      | Design system & presentation framework | Visual identity                 |
| Domain  | Business capability modules            | Functional execution            |
| Admin   | Operational monitoring                 | Governance & observability      |

Dependency direction is hierarchical but pragmatic.

---

## 2.3 Process View — Inter-Module Communication Model

Internara adopts a dual-mode interaction model:

### Query Operations (Read Path)

- Must occur via Service Contracts.
- Direct cross-module model access is strongly discouraged.
- Objective: preserve boundary integrity while avoiding unnecessary indirection.

### Command Operations (Write Path)

Two acceptable interaction mechanisms:

1. **Synchronous Invocation via Interface**
    - Used when strong consistency is required.
    - Suitable for orchestration-level coordination.

2. **Asynchronous Domain Events**
    - Used for eventual consistency scenarios.
    - Decouples modules temporally and structurally.

Event-driven interaction is encouraged, not dogmatically enforced.

The architectural principle is **Explicit Coupling over Implicit Coupling**.

---

# 3. Cross-Cutting Architectural Policies

---

## 3.1 Security Architecture (Aligned with ISO/IEC 27034)

### Authorization Model

- RBAC enforced via Policies and Gates.
- Authorization checks required before state mutation.

### Identity Model

- UUID v4 mandated for all aggregate identities.
- Enumeration-resistant identifiers.

### Data Protection

- PII encrypted at rest using Eloquent `encrypted` cast.
- Logging pipeline applies automated PII masking.

---

## 3.2 Architectural Governance Framework (3S Protocol)

Internara enforces architectural integrity through:

- Architecture Verification Suite (`tests/Arch`)
- Mandatory 3S Audit Compliance

### S1 — Secure

- Mandatory authorization checks.
- UUID identity enforcement.
- PII encryption and masking.

### S2 — Sustainable

- `strict_types=1` required.
- PHP 8.4 Property Hooks recommended.
- Thin presentation components.
- No hard-coded strings.
- Mandatory localization.

### S3 — Scalable

- Interface-first boundaries.
- No uncontrolled direct cross-module model coupling.
- Asynchronous domain events for decoupled workflows.
- Transactional integrity for multi-step operations.

Isolation is redefined as **Boundary Discipline**, not physical segregation.

---

## 3.3 Data Integrity and Coupling Policy

### Referential Integrity

- Managed primarily at the Application Service Layer.
- Cross-module foreign keys are discouraged.
- Allowed only when:
    - Architecturally justified
    - Documented
    - Non-portability is accepted

### Transactional Integrity

- Multi-step operations must be atomic.
- Database transactions required for orchestration workflows.

---

## 3.4 Internationalization Policy

- Zero hard-coded user-facing strings.
- All text resolved through translation keys:

    `__('module::file.key')`

---

## 3.5 Engineering Lifecycle — Test-Driven Governance

Internara mandates **TDD-first engineering**.

### RED–GREEN–REFACTOR Discipline

- Requirements must first be encoded as failing tests.
- Tests act as executable specifications.

### Coverage Objective

- Target ≥ 90% behavioral coverage for domain modules.

### Architecture Validation

- Automated Arch tests verify dependency rules and invariants.

Testing is considered an architectural enforcement mechanism.

---

## 3.6 Cross-Module Testing Policy

### Read Scenarios

- Prefer real service implementations to validate data contracts.

### Write Scenarios

- Mock or fake cross-module side effects.
- Prevent unintended mutation of foreign domain state.

This ensures modular sovereignty during test execution.

---

## 3.7 Domain Event Payload Standard

To prevent serialization inefficiency and stale state propagation:

- Events must carry only:
    - Aggregate UUID
    - Primitive metadata values

- Passing full ORM model instances across event boundaries is prohibited.
- Listeners must rehydrate state via Service Contracts.

This ensures queue stability and data consistency.

---

# Foundational Module Stratification Model

Aligned with **ISO/IEC 12207**, Internara enforces structured system decomposition.

---

## Shared Tier — Portable Infrastructure Layer

- Business-agnostic abstractions.
- No domain dependencies.
- Fully reusable across Laravel systems.

---

## Core Tier — System Identity Anchor

- Global configuration primitives.
- Academic year and system metadata.
- Role and permission blueprints.

Accessed via Contracts where applicable.

---

## Support Tier — Operational Infrastructure

- Scaffolding
- Setup orchestration
- Environment auditing
- Build and deployment tooling

May coordinate across modules via Service Contracts.

---

## UI Tier — Presentation Governance

- Single source of truth for design system.
- Enforces typography and theme identity.
- No embedded business rules.

---

## Domain Tier — Business Capability Modules

- Encapsulate functional capabilities.
- Expose behavior exclusively through Service Contracts.
- Synchronous cross-module calls permitted when:
    - Dependency is explicit
    - Non-circular
    - Documented

### Global Identity Anchors

`User` and `Profile` serve as cross-domain identity anchors for authentication and authorization
context.

---

# Concluding Architectural Statement

Internara’s Architecture Design enforces:

- Explicit architectural boundaries
- Contract-governed inter-module interaction
- Transactional consistency
- Event-driven extensibility
- Test-validated invariants

The system prioritizes **evolutionary maintainability**, **controlled coupling**, and
**architectural clarity** over rigid isolation dogma.
