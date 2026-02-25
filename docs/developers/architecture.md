# Architecture Description (AD): The Internara Modular Monolith

This document provides a formal **Architecture Description (AD)** for the Internara system,
standardized according to **ISO/IEC 42010** (Systems and software engineering — Architecture
description). It defines the architectural viewpoints, structural frameworks, and design invariants
required to ensure system integrity, maintainability, and modularity.

> **Governance Mandate:** This architecture implements the technical requirements mandated by the
> authoritative **[SyRS](specs.md)**. All architectural decisions
> demonstrate traceability to the STRS and SyRS requirements.

---

## 1. Architectural Philosophy & Rationale

Internara utilizes a **Modular Monolith** pattern to optimize the trade-off between domain isolation
(Modularity) and operational simplicity (Deployability).

### 1.1 Modular Decomposition (ISO/IEC 42010 Logical View)

The system is decomposed into self-contained business domains (Modules) to manage systemic
complexity.

- **Decomposition Principle**: Each module represents a distinct domain lifecycle with autonomous
  data and logic boundaries.
- **Rationale**: Facilitates independent evolution of business domains while sharing a common
  infrastructure baseline.

### 1.2 Service-Oriented Logic Execution (The Brain)

Business logic is centralized in the **Service Layer**, adhering to the **Single Responsibility
Principle (SRP)**.

- **Presentation Logic**: Restricted to request orchestration and UI state (Livewire).
- **Domain Logic**: Encapsulated within **Service Contracts** to ensure a single, testable source of
  truth.
- **Persistence Logic**: Restricted to data mapping and internal module relationships (Eloquent).

### 1.3 Principle of Explicit Dependency Injection

To ensure system predictability and testability, Internara enforces explicit dependency management.

- **Contract-First Design**: Modules interact exclusively through **Service Contracts**
  (Interfaces), never through concrete implementations.
- **Rationale**: Decouples the consumer from the provider, allowing for substitution and mock-driven
  verification.

---

## 2. Structural Views (Architectural Perspectives)

### 2.1 Logical View: Tiered Internal Architecture

Every module adheres to a strict 3-tier internal hierarchy:

- **Presentation Layer**: Livewire Components delegating logic to Services.
- **Domain Layer**: Service classes implementing defined Contracts.
- **Persistence Layer**: Eloquent Models utilizing UUID-based identity.

### 2.2 Development View: Modular Infrastructure

Cross-cutting technical concerns are distributed across specialized foundational modules:

- **Shared**: Universal, project-agnostic utilities (e.g., `HasUuid`, `EloquentQuery`).
- **Core**: Business-specific baseline data (e.g., RBAC, Academic Years).
- **Support**: Infrastructure tooling and generators.
- **UI**: Standardized design system implementing the **Instrument Sans** identity using 
  **maryUI** and **Volt**.

### 2.3 Process View: Communication Protocols

- **Synchronous**: Cross-module interaction via **Service Contracts**.
- **Asynchronous**: Cross-domain side-effects handled via Laravel's **Event/Listener** subsystem.
- **Rationale**: Prevents temporal coupling and ensures system scalability.

---

## 3. Cross-Cutting Concerns & Design Invariants

### 3.1 Security & Access Control (ISO/IEC 27034)

- **RBAC Invariant**: Access to domain resources is strictly governed by **Policies** and **Gates** 
  managed by the **Permission** module and mapped to the stakeholder roles defined in the SyRS.
- **Identity Invariant**: Mandatory use of **UUID v4** (via `Shared\Models\Concerns\HasUuid`) for 
  all entities to prevent enumeration.

### 3.2 Data Integrity & Isolation

- **Referential Integrity**: Managed at the **Service Layer**. Physical foreign keys across module
  boundaries are prohibited to maintain modular portability.
- **Transactional Integrity**: Multi-step operations must be atomic, encapsulated within database
  transactions.

### 3.3 Internationalization (i18n)

- **Localization Invariant**: Zero hard-coding of user-facing text. All strings must be resolved via
  translation keys using the `__('module::file.key')` pattern.

---

_This Architecture Description establishes the structural baseline for Internara. Every technical
modification must be validated against these viewpoints to ensure the long-term maintainability and
integrity of the system._

---

# Foundational Module Philosophy: System Decomposition

This document formalizes the **System Decomposition** strategy for the Internara modular monolith,
consistent with **ISO/IEC 12207**. It establishes a structural hierarchy based on functional
specialization and **Portability Invariants** to ensure architectural resilience and minimize
systemic entropy.

> **SSoT Alignment:** This hierarchy implements the structural requirements defined in the
> **[Internara Specs](specs.md)**, specifically the mandates for centralized administration and
> standardized multi-role user interfaces.

---

## 1. Architectural Hierarchy & Portability Matrix

Modules are classified into five distinct tiers based on their functional role and level of
decoupling from the Internara domain. This classification governs the **Dependency Direction** and
**Interface Control Protocols**.

| Category    | Functional Role          | Portability Requirement        | Example Artifacts           |
| :---------- | :----------------------- | :----------------------------- | :-------------------------- |
| **Shared**  | Abstract Utilities       | **High (Project-Agnostic)**    | `HasUuid`, `Formatter`      |
| **Core**    | Domain Blueprint         | **Low (Business-Specific)**    | `AcademicYear`, `BaseRoles` |
| **Support** | Infrastructure Bridge    | **Medium (Environment-Aware)** | `Scaffolding`, `Onboarding` |
| **UI**      | Design System            | **Low (Identity-Specific)**    | `AppLayout`, `NativeToasts` |
| **Domain**  | Business Logic Execution | **High (Domain-Encapsulated)** | `Internship`, `Journal`     |
| **Admin**   | System Monitoring        | **Medium (Operational-Focus)** | `Analytics`, `UserManager`  |

---

## 2. Shared Tier: The Portable Foundation (Infrastructure View)

The `Shared` tier constitutes the project-independent "Engine Room." It encapsulates universal
software engineering patterns.

- **Portability Invariant**: Components must remain strictly agnostic of Internara's specific
  business rules. They should be reusable in any Laravel-based system without modification.
- **Composition**:
    - **Cross-Cutting Concerns**: `HasUuid` (Shared), `HasStatus` (Status), `InteractsWithActivityLog` (Log).
    - **Base Abstractions**: `EloquentQuery`.
    - **General Utilities**: String manipulation, mathematical validators, and data transformers.

---

## 3. Core Tier: The Domain Blueprint (Contextual View)

The `Core Tier` provides the foundational building blocks that define the **Identity of Internara**.

- **Purpose**: It encapsulates global domain logic and static data required by all functional
  modules.
- **Composition**:
    - **Identity & Access**: Global Role and Permission definitions.
    - **Temporal Logic**: Academic Year and Semester scoping infrastructure.
    - **System Configuration**: Technical implementation for the `setting()` helper and persistence.

---

## 4. Support Tier: The Operational Bridge (Tooling View)

The `Support Tier` manages **Development and Infrastructure** operations, shielding domain logic
from environmental "noise."

- **Purpose**: Facilitate the construction and maintenance of domain artifacts through automation.
- **Composition**:
    - **Scaffolding**: Custom Artisan generators for modules and Livewire components.
    - **Integration**: Asset loaders (Vite), environment auditors, and deployment scripts.

---

## 5. UI Tier: The Visual Identity (Presentation View)

The `UI Tier` is the single source of truth for the Internara design system, enforcing the
**Mobile-First** and **Responsive** mandates.

- **Design Mandate**: Must strictly implement the **Instrument Sans** typography and emerald-accent
  theme defined in the SSoT.
- **Composition**:
    - **Semantic Layouts**: `Auth`, `App`, and `Setup` base layouts.
    - **Atomic Components**: Standardized buttons, modals, and form elements (MaryUI/Tailwind v4).
    - **Localization (i18n)**: UI components must facilitate dynamic `__('key')` injection.

---

## 6. Domain Tier: Functional Execution (Business View)

Domain modules (e.g., `User`, `Internship`) execute the actual business logic of the system.

- **Best Practice**: Domain modules should strive for encapsulation, exposing functionality only
  through **Service Contracts**.
- **Dependency Invariant**:
    1.  **Strict Downward Dependency**: Domain modules primarily depend on the `Shared` tier.
    2.  **Contractual Access**: Dependencies on the `Core` tier must be resolved via **Service
        Contracts** or framework-level Policies to prevent concrete coupling.
    3.  **Physical Integrity**: **No physical foreign keys** across modules. Referential integrity
        is enforced at the Service Layer.

---

_Adherence to this decomposition philosophy prevents "Spaghetti Modularity" and ensures that the
system remains analysable, testable, and evolvable throughout its lifecycle._
