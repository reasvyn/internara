# Foundational Module Philosophy: System Decomposition

This document formalizes the **System Decomposition** strategy for the Internara modular monolith,
consistent with **ISO/IEC 12207**. It establishes a structural hierarchy based on functional
specialization and **Portability Invariants** to ensure architectural resilience and minimize
systemic entropy.

> **SSoT Alignment:** This hierarchy implements the structural requirements defined in the
> **[Internara Specs](../internal/internara-specs.md)**, specifically the mandates for centralized
> administration and standardized multi-role user interfaces.

---

## 1. Architectural Hierarchy & Portability Matrix

Modules are classified into five distinct tiers based on their functional role and level of
decoupling from the Internara domain. This classification governs the **Dependency Direction** and
**Interface Control Protocols**.

| Category    | Functional Role          | Portability Requirement        | Example Artifacts           |
| :---------- | :----------------------- | :----------------------------- | :-------------------------- |
| **Shared**  | Abstract Utilities       | **High (Project-Agnostic)**    | `HasUuid`, `EloquentQuery`  |
| **Core**    | Domain Blueprint         | **Low (Business-Specific)**    | `AcademicYear`, `BaseRoles` |
| **Support** | Infrastructure Bridge    | **Medium (Environment-Aware)** | `ModuleGenerators`, `Audit` |
| **UI**      | Design System            | **Low (Identity-Specific)**    | `AppLayout`, `Instrument`   |
| **Domain**  | Business Logic Execution | **High (Domain-Encapsulated)** | `Internship`, `Journal`     |

---

## 2. Shared Tier: The Portable Foundation (Infrastructure View)

The `Shared` tier constitutes the project-independent "Engine Room." It encapsulates universal
software engineering patterns.

- **Portability Invariant**: Components must remain strictly agnostic of Internara's specific
  business rules. They should be reusable in any Laravel-based system without modification.
- **Composition**:
    - **Cross-Cutting Concerns**: `HasUuid`, `HasStatuses`, `HasAuditLog`.
    - **Base Abstractions**: `EloquentQuery`, `BaseServiceContract`.
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
    - **Localization (i11n)**: UI components must facilitate dynamic `__('key')` injection.

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
