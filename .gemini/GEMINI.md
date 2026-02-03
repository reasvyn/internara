# Gemini Guidelines: Internara Project

This document formalizes the principles, project context, and operational guidelines for the AI
assistant (“Gemini”) when orchestrating developmental activities for the Internara project. It
ensures that all AI-driven interventions demonstrate 100% alignment with the system's authoritative
engineering records and international standards.

> **Single Source of Truth Mandate:** The document
> **[`docs/internal/system-requirements-specification.md`](../docs/internal/system-requirements-specification.md)**
> is the **Authoritative Specification** for this project. For a complete list of modules and their
> roles, refer to the **[Module Catalog](../docs/main/modules-catalog.md)**. All architectural
> decisions, logic implementation, and lifecycle events MUST satisfy its requirements.

---

## 1. Project Overview & Architectural Logic

**Internara** is an internship management ecosystem engineered as a **Modular Monolith**. Business
rules are encapsulated within autonomous domain modules, orchestrated via a **Service-Oriented Logic
Layer**.

The architecture prioritizing systemic integrity and maintainability, adhering to the formal
**[Architecture Description](../docs/internal/architecture-description.md)**.

---

## 2. Interaction & Operational Principles

Gemini must adhere to the following interaction invariants:

### 2.1 Aesthetic-Natural Principle

Maintain a calm, adaptive, and structured tone focused on clarity and cognitive efficiency. Answers
must be structurally minimalist and oriented toward actionable engineering outcomes.

### 2.2 Analytical Accountability

- **Keypoints Summary**: Interaction must conclude with a concise, scannable summary of conclusion,
  decisions, and verified outcomes.
- **Privacy Protocol**: Strictly prohibit the storage or processing of personal or sensitive
  information. Refuse non-compliant requests explicitly.
- **Limited Initiative**: Do not execute strategic lifecycle transitions (e.g., Promotion to Stable)
  without explicit user authorization.

---

## 3. Engineering Workflows & Lifecycle Governance

Gemini is mandated to follow the formal
**[Software Life Cycle Processes](../docs/internal/software-lifecycle-processes.md)**.

### 3.1 Implementation Process

- **Traceability Driven**: Every feature must be traceable to a specific requirement in the SyRS.
- **Design Baseline**: Formulate an **Architectural Blueprint** and obtain explicit approval prior
  to construction.
- **TDD-First Construction**: Implementation must utilize the **`test(...)`** pattern via Pest v4.
- **Analytical Documentation**: Every modification triggers a **Doc-as-Code** synchronization cycle.
  Simplification or truncation of documentation is **STRICTLY PROHIBITED**.

### 3.2 Verification Gates

- **System Verification**: Mandatory execution of **`composer test`**.
- **Static Analysis**: Mandatory execution of **`composer lint`**.
- **Configuration Management**: Adherence to **Conventional Commits** and the
  **[Repository Configuration Protocols](../docs/internal/repository-configuration-protocols.md)**.

---

## 4. Technical Invariants (Systemic Record)

### 4.1 Construction Baseline

- **Identity Invariant**: Mandatory utilization of **UUID v4** for all entities.
- **Isolation Invariant**: No physical foreign keys across modules. Use Service Contracts.
- **Logic Invariant**: Direct `env()` calls are prohibited. Use the `setting()` registry.
- **Presentation Invariant**: Livewire components must satisfy the **Thin Component** mandate.
- **UI Decoupling Invariant**: Cross-module UI integration must utilize the **Slot Injection**
  pattern via the `UI` module. Direct cross-module component calls are prohibited.

### 4.2 Semantic Namespacing

Namespaces MUST omit the `src` segment to maintain modular portability.

- _Correct_: `namespace Modules\User\Services;`
- _Mapping_: `modules/User/src/Services/UserService.php`

---

## 5. Verification & Validation (V&V) Standards

Gemini must verify all artifacts against the
**[Testing & Verification Guide](../docs/internal/testing-verification-guide.md)** and the
**[Conventions and Rules](../docs/internal/conventions-and-rules.md)**.

- **V-Model Alignment**: Unit and Feature tests must verify both technical design and requirement
  fulfillment.
- **Multi-Language Integrity**: All user-facing output must be verified in **ID** and **EN**.
- **Security Audit**: Proactive verification of RBAC Policies and potential injection vectors.

---

## 6. Supporting Orchestration

Gemini is authorized to utilize the **GitHub CLI (`gh`)** and **Laravel Boost** tools to facilitate
systemic synchronization and debugging.

- **Artisan Orchestration**: Utilization of modular generators defined in the
  **[Automated Tooling Reference](../docs/internal/automated-tooling-reference.md)**.
- **Information Retrieval**: Prioritize `search-docs`, `tinker`, and `database-schema` for
  contextual immersion.

Refer to the **[Technical Index](../docs/internal/table-of-contents.md)** for exhaustive engineering
standards.

---

## 7. Mandatory Context Reference

Gemini must always consider the following authoritative documents as active context:

- **[System Requirements Specification](../docs/internal/system-requirements-specification.md)**
- **[Architecture Description](../docs/internal/architecture-description.md)**
- **[Conventions and Rules](../docs/internal/conventions-and-rules.md)**
- **[Repository Configuration Protocols](../docs/internal/repository-configuration-protocols.md)**
- **[Release Publication Protocols](../docs/internal/release-publication-protocols.md)**
- **[Version Management](../docs/internal/version-management.md)**
- **[Module Catalog](../docs/main/modules-catalog.md)**