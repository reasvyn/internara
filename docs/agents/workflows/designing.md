# Comprehensive Engineering Protocol: Architectural & Logical Design

This document establishes the authoritative **Design Protocol** for the Internara project, adhering
to **ISO/IEC 42010** (Architecture Description) and the project's **Modular DDD** baseline.

---

## ⚖️ Core Mandates & Prohibitions (The Design Laws)

The Agent must adhere to these invariants to maintain the structural purity of the Modular Monolith.

### 1. Contract & Logic Laws

- **Contract-First Invariant**: Inter-module communication MUST be designed via **Service
  Contracts** (Interfaces) before any implementation.
- **Service Responsibility (SRP)**: Business logic MUST be designed for the Service Layer.
  Presentation layers (Livewire) are restricted to UI orchestration.
- **Zero-Coupling**: Physical foreign keys across modules are **PROHIBITED**. Design referential
  integrity via indexed UUIDs at the Service Layer.

### 2. Structural & Presentation Laws

- **The `src` Omission**: All designed namespaces MUST omit the `src` segment.
- **Slot Injection Pattern**: Cross-module UI integration MUST utilize the `UI` module's Slot
  Registry. Direct component calls across domain boundaries are forbidden.
- **Mobile-First Design**: UI components MUST be designed starting from mobile breakpoints,
  utilizing Tailwind v4 for progressive enhancement.

---

## 🎯 Scope & Authorized Actions

### 1. Protocol Scope

- **Architectural Blueprinting**: Formalizing the logic and data flow of a feature.
- **Schema Design**: Designing UUID-based persistence structures.
- **Service Contract Specification**: Defining the public API of a business domain.

### 2. Authorized Actions

- **Blueprint Evolution**: Modifying files in `docs/developers/blueprints/`.
- **Contract Drafting**: Proposing new Interfaces in `src/Contracts/`.
- **ADR Creation**: Documenting significant architectural decisions in
  `docs/developers/architecture.md`.

---

## Phase 0: Strategic Immersion

- **Baseline Audit**: Re-read `architecture.md` and `conventions.md`.
- **Knowledge Synthesis**: Audit **GitHub Discussions** and **Issues** for community-driven design
  requirements or architectural constraints.
- **Contract Review**: Identify if existing Service Contracts can be leveraged.

## Phase 1: Modular Decomposition (The logical View)

- **Domain Identification**: Determine the correct module for the new logic.
- **Boundary Mapping**: Identify cross-module side effects (Events/Listeners).

## Phase 2: Data Schema & Persistence (The Physical View)

- **Identity Invariant**: Ensure all entities use **UUID v4**.
- **Normalization Audit**: Design for indexed UUIDs. Avoid physical FKs across modules.
- **Status Design**: Apply `HasStatuses` for auditable entity lifecycles.

## Phase 3: Service Contract Specification

- **Interface Definition**: Design methods, parameters, and return types (DTOs).
- **Dependency Injection Plan**: Plan required external contracts.

## Phase 4: UI/UX & Interaction Design (The Presentation View)

- **Component Breakdown**: Design the Livewire components as "Thin Orchestrators."
- **Accessibility (A11y)**: Ensure semantic HTML and keyboard navigability (WCAG 2.1) are part of
  the component design.
- **Localization Plan**: Identify all potential UI strings for translation.

## Phase 5: Documentation & GitHub Synchronization

- **Blueprint Finalization**: Update the narrative blueprint in `docs/developers/blueprints/`.
- **Issue Connection**: Link the blueprint to its corresponding **GitHub Issue** for implementation
  tracking.
- **README Update**: Document the new domain scope in the module-level `README.md`.

---

_Design is the blueprint of stability; without it, implementation is mere entropy._
