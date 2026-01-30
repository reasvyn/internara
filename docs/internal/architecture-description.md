# Architecture Description (AD): The Internara Modular Monolith

This document provides a formal **Architecture Description (AD)** for the Internara system,
standardized according to **ISO/IEC 42010** (Systems and software engineering — Architecture
description). It defines the architectural viewpoints, structural frameworks, and design invariants
required to ensure system integrity, maintainability, and modularity.

> **Governance Mandate:** This architecture implements the technical requirements mandated by the
> authoritative **[System Requirements Specification](system-requirements-specification.md)**. All
> architectural decisions demonstrate traceability to the STRS and System Requirements Specification
> requirements.

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
- **UI**: Standardized design system implementing the **Instrument Sans** identity.

### 2.3 Process View: Communication Protocols

- **Synchronous**: Cross-module interaction via **Service Contracts**.
- **Asynchronous**: Cross-domain side-effects handled via Laravel's **Event/Listener** subsystem.
- **Rationale**: Prevents temporal coupling and ensures system scalability.

---

## 3. Cross-Cutting Concerns & Design Invariants

### 3.1 Security & Access Control (ISO/IEC 27034)

- **RBAC Invariant**: Access to domain resources is strictly governed by **Policies** and **Gates**
  mapped to the stakeholder roles defined in the System Requirements Specification.
- **Identity Invariant**: Mandatory use of **UUID v4** for all entities to prevent enumeration.

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
