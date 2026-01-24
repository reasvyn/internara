# Software Development Lifecycle (SDLC)

This document formalizes the **Software Development Lifecycle (SDLC)** for Internara. It adapts
standard software engineering theory to the specific context of the **Internara Modular Monolith**,
establishing a rigorous process for converting business requirements into reliable software artifacts.

> **Governance Mandate:** This SDLC is strictly governed by the **[Internara Specs](../internal/internara-specs.md)**.
> No phase may proceed without alignment to this Single Source of Truth.

---

## 1. The SDLC Model: Iterative & Modular

Internara employs an **Iterative-Incremental** SDLC model.
- **Iterative:** The system evolves through repeated cycles (Sprints/Milestones).
- **Incremental:** Features are added module-by-module, adhering to the Modular Monolith architecture.

Each iteration follows the standard engineering phases defined below.

---

## 2. Phase 1: Requirements Engineering

The foundation of the lifecycle. This phase defines *what* is being built.

- **Objective:** Transform abstract business goals into concrete technical specifications.
- **Primary Artifact:** **[Internara Specs](../internal/internara-specs.md)** (Immutable Source of Truth).
- **Process:**
  1.  **Elicitation:** Gather requirements from user roles (Instructor, Staff, Student).
  2.  **Analysis:** Feasibility study and scope definition.
  3.  **Specification:** Documentation in `internara-specs.md`.
- **Exit Gate:** Requirements are frozen and approved.

---

## 3. Phase 2: System Design

This phase defines *how* the requirements will be met technically.

- **Objective:** Design the structural architecture to support the specifications.
- **Primary Artifact:** **[Architecture Guide](../internal/architecture-guide.md)** & Module Blueprints.
- **Process:**
  1.  **High-Level Design:** Define Module boundaries and inter-module contracts.
  2.  **Low-Level Design:** Define Database Schema, Service Interfaces, and UI Components.
  3.  **Compliance Check:** Ensure designs adhere to "Mobile-First" and "Multi-Language" constraints.
- **Exit Gate:** Architecture is validated against the Specs.

---

## 4. Phase 3: Construction (Implementation)

The translation of design into executable code.

- **Objective:** Produce high-quality, maintainable code.
- **Guidance:** **[Development Conventions](../internal/development-conventions.md)**.
- **Process:**
  1.  **Coding:** Implementation using Laravel v12, Livewire v3, and TALL Stack.
  2.  **Unit Testing:** Writing tests (Pest) concurrently with code.
  3.  **Static Analysis:** Linting (Pint) and Type Checking.
- **Constraint:** All "Application Settings" must use the `setting()` helper; no hard-coding.
- **Exit Gate:** Code compiles, passes local tests, and adheres to conventions.

---

## 5. Phase 4: Verification & Validation (V&V)

Quality Assurance ensures the software meets requirements (Validation) and is built correctly (Verification).

- **Objective:** Detect defects and ensure spec compliance.
- **Process:**
  1.  **Automated Testing:** CI pipelines run Feature and Unit tests.
  2.  **Spec Validation:** Verify that features function exactly as described in `internara-specs.md`.
  3.  **Security Audit:** Check for hard-coded secrets and vulnerability scanning.
- **Exit Gate:** Zero critical bugs, 100% test pass rate on affected paths.

---

## 6. Phase 5: Release & Deployment

The formal publication of a versioned artifact.

- **Objective:** Package the software for distribution/deployment.
- **Process:**
  1.  **Versioning:** Assign a SemVer identifier (e.g., `v1.0.0`).
  2.  **Documentation:** Finalize Release Notes (User-Centric Narrative) and Technical Guides.
  3.  **Tagging:** Git tag creation.
- **Exit Gate:** Release notes approved and artifact tagged.

---

## 7. Lifecycle Classification Axes

To manage the maturity and support of released artifacts, Internara uses a 3-axis classification system.

### 7.1 Maturity Stage
Reflects the stability confidence level.
- **Experimental:** Proof of concept.
- **Alpha:** Feature complete but unpolished.
- **Beta:** Functionally complete, testing for bugs.
- **Stable:** Production-ready.

### 7.2 Operational Status
Reflects the current state in the lifecycle.
- **Planned:** Concept phase.
- **In Progress:** Construction phase.
- **Released:** Publicly available.
- **Deprecated:** Scheduled for removal.
- **Archived:** Historical reference only.

### 7.3 Support Policy
Reflects the maintenance commitment.
- **Active Support:** Bugs and Security fixes.
- **Security Only:** No new features, security patches only.
- **EOL (End of Life):** No support provided.

---

## 8. Maintenance & Evolution

Post-release activities.
- **Corrective:** Fixing reported bugs.
- **Adaptive:** Updating for new environment versions (e.g., PHP updates).
- **Perfective:** Refactoring for performance (without changing external behavior).

_This theoretical framework ensures that Internara is not just "written," but **engineered** with precision, traceability, and quality at every step._
