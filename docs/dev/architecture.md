# Architecture Description (AD): The Internara Modular Monolith

This document provides a formal **Architecture Description (AD)** for the Internara system,
standardized according to **ISO/IEC 42010** (Systems and software engineering — Architecture
description). It defines the architectural viewpoints, structural frameworks, and design invariants
required to ensure system integrity, maintainability, and modularity.

> **Governance Mandate:** This architecture implements the technical requirements mandated by the
> authoritative **[SyRS](specs.md)**. All architectural decisions demonstrate traceability to the
> STRS and SyRS requirements.

---

## 1. Architectural Philosophy & Rationale

Internara utilizes a **Modular Monolith** pattern to optimize the trade-off between domain isolation
(Modularity) and operational simplicity (Deployability).

### 1.1 Modular Decomposition & DDD (ISO/IEC 42010 Logical View)

The system is decomposed into self-contained business domains (Modules) using **Domain-Driven Design (DDD)** principles to manage systemic complexity.

- **Decomposition Principle**: Each module represents a distinct **Bounded Context** with autonomous data, logic boundaries, and a dedicated **Ubiquitous Language**.
- **Strategic Design**: Modules encapsulate related **Aggregates**, ensuring that domain invariants are maintained within the module boundary.
- **Rationale**: Facilitates independent evolution of business domains while sharing a common infrastructure baseline.

### 1.2 Service-Oriented Logic Execution (The Brain)

Business logic is centralized in the **Service Layer**, adhering to the **Single Responsibility Principle (SRP)** and the **Service Layer Dualism** pattern.

- **Presentation Logic**: Restricted to request orchestration and UI state (Livewire).
- **Domain Services (Data-Oriented)**: Encapsulated within `EloquentQuery` to provide a standardized, testable interface for data persistence and retrieval.
- **Orchestration Services (Logic-Oriented)**: Encapsulated within `BaseService` to handle complex business rules and cross-service coordination without direct coupling to Eloquent.
- **Service Contracts**: Interaction between modules occurs exclusively through Interfaces to ensure a single, testable source of truth.

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
- **Core**: System orchestration (Module Discovery, Academic Year, Metadata SSoT).
- **Support**: Infrastructure tooling and generators.
- **UI**: Standardized design system implementing the **Instrument Sans** identity using
  **DaisyUI**, **Tailwind CSS v4**, and **Livewire v3**.

### 2.3 Process View: Communication Protocols

Internara utilizes a tiered communication strategy to balance **Developer Experience (DX)** with
**Strict Modular Isolation**:

1.  **Read-Only Operations (Queries)**: Modules may interact with external domains via **Service
    Contracts** (Interfaces) to retrieve data. This ensures simple, traceable data flow and fulfills
    the **Contract-First** mandate.
2.  **State-Changing Operations (Commands)**: Side-effects that cross module boundaries MUST be
    handled via **Domain Events** (asynchronous listeners). This prevents high coupling and protects
    the "Zero-Coupling" invariant.
3.  **Rationale**: Prevents "Chatty Communication" for reads while ensuring no module has the
    authority to directly modify the state of another domain.

---

## 3. Cross-Cutting Concerns & Design Invariants

### 3.1 Security & Access Control (ISO/IEC 27034)

- **RBAC Invariant**: Access to domain resources is strictly governed by **Policies** and **Gates**
  managed by the **Permission** module and mapped to the stakeholder roles defined in the SyRS.
- **Identity Invariant**: Mandatory use of **UUID v4** (via `Shared\Models\Concerns\HasUuid`) for
  all entities to prevent enumeration.
- **PII Protection**: Personally Identifiable Information is encrypted at rest using Eloquent's
  `encrypted` cast and automatically masked in all logging sinks via the `PiiMaskingProcessor`.

### 3.2 Architectural Governance (The Architecture Police)

To prevent architectural drift and ensure modular isolation, Internara utilizes an automated
**Architecture Verification Suite (tests/Arch/)** and a mandatory **3S Audit** protocol. This 
composite quality gate enforces:

- **Security (S1)**: **Strict Authorization** (RBAC), UUID v4 identities, and PII masking. 
- **Sustainability (S2)**: **PHP 8.4 Construction** (Property Hooks), thin 
  components, and zero hard-coding strings.
- **Scalability (S3)**: **Strict Isolation** (zero physical foreign keys, zero direct model access), 
  and **Contract-First Communication**.

### 3.3 Data Integrity & Isolation

- **Referential Integrity**: Managed at the **Service Layer**. Physical foreign keys across module
  boundaries are prohibited to maintain modular portability.
- **Transactional Integrity**: Multi-step operations must be atomic, encapsulated within database
  transactions.

### 3.4 Internationalization (i18n)

- **Localization Invariant**: Zero hard-coding of user-facing text. All strings must be resolved via
  translation keys using the `__('module::file.key')` pattern.

### 3.5 Test-Driven Development (TDD) Lifecycle

Internara mandates a **TDD-First** engineering approach to ensure reliability and traceability.

- **The RED-GREEN-REFACTOR Cycle**: Every technical requirement must be expressed as a failing test (RED) before implementation begins.
- **Verification-as-Documentation**: Tests serve as the executable documentation of the system's behavioral requirements (Pest v4).
- **Architecture Enforcement**: Automated Arch tests ensure that modular isolation and structural invariants are never violated during development.
- **Coverage Mandate**: Domain modules strive for a minimum of 90% behavioral coverage, linked back to SyRS requirements.

### 3.5 Cross-Module Testing Invariant

To maintain **Modular Sovereignty** while ensuring integration integrity, Internara enforces a dual-tier testing strategy for inter-module dependencies:

- **Read Operations (Queries)**: Tests SHOULD utilize **Real Service Implementations** when retrieving data from another module. This ensures that the test reflects the actual data contract and prevents "Mocking Drift."
- **State-Changing Operations (Commands)**: Tests MUST utilize **Mocking** or **Event Faking** (`Event::fake()`) for side effects that cross module boundaries. This ensures that a test in one module does not accidentally modify the state of another domain.

### 3.6 Domain Event Payload Standard

To prevent "Serialization Bloat" and ensure data consistency in the asynchronous queue:

- **Identity-Only Payloads**: Domain Events MUST only carry the **UUID** of the aggregate root (e.g., `internship_id`) and a **Primitive DTO** for necessary metadata.
- **Model Restriction**: Passing full Eloquent Model instances across the event bus is **Strictly Prohibited**. This prevents stale data issues and reduces the memory footprint of the queue workers.
- **Re-hydration Invariant**: Event Listeners MUST re-hydrate the required data by calling the relevant **Service Contract** using the provided UUID.

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
    - **Cross-Cutting Concerns**: `HasUuid` (Shared), `HasStatus` (Status),
      `InteractsWithActivityLog` (Log).
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
        Contracts** or framework-level Policies.
    3.  **Physical Integrity**: **No physical foreign keys** across modules.
    4.  **Identity Anchors**: The `User` and `Profile` models are recognized as **Global Identity 
        Anchors**. They are exempt from the strict isolation rule and may be utilized across 
        all tiers for authentication and authorization context.

---

_Adherence to this decomposition philosophy prevents "Spaghetti Modularity" and ensures that the
system remains analysable, testable, and evolvable throughout its lifecycle._
