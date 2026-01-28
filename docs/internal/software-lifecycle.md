# Software Development Lifecycle (SDLC) Framework

This document formalizes the **Software Development Lifecycle (SDLC)** for Internara. It is
grounded in the principles of **Software Engineering (SE)** theory, specifically **ISO/IEC 12207**
(Software Life Cycle Processes), and adapted for the **Modular Monolith** architecture. This
framework ensures the systemic transformation of stakeholder requirements into verified, validated,
and maintainable software artifacts.

> **Governance Mandate:** This SDLC is strictly governed by the authoritative
> **[Internara Specs](../internal/internara-specs.md)**. No lifecycle phase may proceed without
> demonstrated alignment to this Single Source of Truth (SSoT).

---

## 1. The SDLC Model: Iterative, Incremental, and Modular

Internara employs an **Iterative-Incremental** SDLC model, reinforced by **Modular Isolation**.

- **Iterative Development**: The system evolves through successive refinement cycles (Milestones).
- **Incremental Delivery**: Functional capabilities are added module-by-module, preserving the
  integrity of the Modular Monolith.
- **V-Model Alignment**: Every design phase is mapped to a corresponding verification and
  validation phase to ensure traceability and quality.

---

## 2. Phase 1: Requirements Engineering (Conceptual Integrity)

This phase focuses on defining the "Problem Space" and formalizing the technical boundaries of the
system, adhering to **IEEE Std 830** principles.

- **Objective**: Establish a rigorous and unambiguous specification of system behavior.
- **Sub-processes**:
    1.  **Elicitation**: Capturing needs from identified User Roles (Instructor, Staff, etc.).
    2.  **Analysis**: Determining feasibility, detecting requirement conflicts, and defining
        non-functional constraints.
    3.  **Specification**: Formalizing requirements into the **[Internara Specs](../internal/internara-specs.md)**.
    4.  **Validation**: Ensuring the specified requirements actually satisfy stakeholder objectives
        (Avoiding Type III Error).
- **Exit Gate**: Specification is verified for completeness, consistency, and traceability.

---

## 3. Phase 2: System & Detailed Design (Solution Synthesis)

This phase defines the "Solution Space" based on **ISO/IEC 42010** (Architecture Description) and
the principle of **Information Hiding**.

- **Architectural Design (HLD)**: Defining module boundaries, communication protocols (Service
  Contracts), and cross-cutting concerns (Infrastructure, Security).
- **Detailed Design (LLD)**: Specifying component-level logic, database schemas (UUID-based), and
  interface behaviors.
- **Security-by-Design**: Explicitly designing for **Role-Based Access Control (RBAC)** and data
  encryption at the architectural level.
- **Exit Gate**: Design artifacts (Blueprints) are validated against the Requirements Specification.

---

## 4. Phase 3: Construction (Implementation & TDD)

The transformation of design specifications into executable code using **Construction-as-Engineering**
principles.

- **Test-Driven Development (TDD)**: Implementation is driven by behavioral specifications (Pest v4)
  to ensure that code satisfies requirements from the outset.
- **Modular Isolation**: Construction must strictly respect module boundaries, utilizing **Contracts**
  for cross-module communication.
- **Static Analysis**: Continuous use of automated tools (Pint, PHPStan) to ensure code quality and
  adherence to **[Development Conventions](../internal/development-conventions.md)**.
- **Refactoring**: Improving internal structure without altering external behavior to manage
  technical entropy.
- **Exit Gate**: Code passes all local unit/feature tests and adheres to static analysis standards.

---

## 5. Phase 4: Verification & Validation (V&V Framework)

A dual-layered quality assurance process derived from the **V-Model**.

### 5.1 Verification (Building the System Right)
Ensuring the implementation conforms to the technical design and coding standards.
- **Process**: Unit Testing, Integration Testing, and Security Scanning.
- **Metric**: 100% test pass rate and zero static analysis violations.

### 5.2 Validation (Building the Right System)
Ensuring the implementation fulfills the requirements defined in the **Internara Specs**.
- **Process**: User Acceptance Testing (UAT) and Feature Validation against SSoT.
- **Metric**: Feature behavior exactly matches the specified functional requirements.

---

## 6. Phase 5: Transition & Release Management

The formal transition of software artifacts from development to operational status.

- **Configuration Management**: Finalizing SemVer identifiers and Git tagging according to the
  **[GitHub Protocols](../internal/github-protocols.md)**.
- **Release Documentation**: Synchronizing Release Notes and technical guides (Doc-as-Code).
- **Deployment**: Orchestrated delivery to the target environment (VPS/Cloud).
- **Exit Gate**: Release artifact is tagged, documented, and deployed successfully.

---

## 7. Maintenance & Software Evolution

Post-release activities guided by **Lehman’s Laws of Software Evolution**.

- **Corrective Maintenance**: Identifying and resolving latent defects.
- **Adaptive Maintenance**: Modifying the system to remain functional in evolving environments
  (e.g., PHP 8.4+ updates).
- **Perfective Maintenance**: Enhancing performance and maintainability (refactoring) based on
  operational telemetry.
- **Evolutionary Entropy**: Proactively managing technical debt to prevent the system from becoming
  unnecessarily complex or unmaintainable over time.

---

## 8. Lifecycle Classification Metrics

### 8.1 Maturity Stage
- **Experimental**: Feasibility exploration.
- **Alpha**: Internal feature-complete testing.
- **Beta**: External functional validation.
- **Stable**: Production-ready and support-active.

### 8.2 Operational Status
- **Planned** | **In Progress** | **Released** | **Deprecated** | **Archived**

---

_By adhering to this SDLC framework, Internara ensures that development is a disciplined,
traceable, and academically sound engineering process, yielding high-quality software that remains
aligned with its foundational specifications._