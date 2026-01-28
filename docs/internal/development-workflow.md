# Development Workflow: Feature Engineering Lifecycle

This document defines the **engineering workflow** for building features in the Internara project.
It specifies _how work is performed_ based on the principles of **Software Engineering (SE)** and
the **ISO/IEC 12207** standards to meet the rigorous requirements of our **Modular Monolith**.

> **Governance Mandate:** All engineering work must align with the
> **[Internara Specs](../internal/internara-specs.md)** and the
> **[Software Development Lifecycle (SDLC)](../internal/software-lifecycle.md)**. Deviation from the
> Specs requires a formal Change Request.

**Core Principle:** Never start implementation without contextual clarity. Never consider work
complete without **Artifact Synchronization**.

---

## Phase 1: Requirements Engineering (Analysis & Specification)

This phase corresponds to the **Requirements Analysis** phase of the SDLC. It ensures that the "Type
III Error" (solving the wrong problem) is avoided.

### 1.1 Specification Verification

- **Action:** Validate the feature request against the **[Internara Specs](../internal/internara-specs.md)**.
- **Analysis:** Identify functional requirements (what it does) and non-functional requirements
  (performance, security, i11n).
- **Constraint Check:** Ensure compliance with **Mobile-First** design and **Multi-Language**
  mandates.
- **Role Analysis:** Confirm the feature aligns with the designated User Roles (Instructor, Staff,
  Student, Mentor).

### 1.2 Architectural & Historical Review

- **Action:** Review existing patterns in `docs/versions/` and `docs/internal/architecture-guide.md`.
- **Objective:** Prevent architectural regression and maintain modular consistency.

---

## Phase 2: Design & Blueprinting (Architectural Decomposition)

This phase corresponds to the **Design** phase of the SDLC. It focuses on **Information Hiding**
(Parnas) and **Separation of Concerns** (Dijkstra).

### 2.1 Implementation Planning (Blueprint)

- **Action:** Create a detailed implementation plan (Blueprint).
- **Artifact:** Store the plan in `docs/internal/blueprints/` scoped to the current version.
- **Technical Detail:**
    - **Modular Boundaries**: Define which modules are impacted and how they will interact.
    - **Contract Definitions**: Specify the **Service Contracts** (Interfaces) to be created.
    - **Data Schema**: Define UUID-based entities, indexes, and encryption requirements.
    - **UI/UX Strategy**: Map the user flow for mobile and desktop viewports.

---

## Phase 3: Construction (TDD & Implementation)

This phase corresponds to the **Construction** phase of the SDLC, utilizing **Test-Driven
Development (TDD)** to ensure behavioral compliance.

### 3.1 Preliminary Scaffold & Test Specification

- **Action:** Verify existing scaffolds/logic in the module.
- **TDD Requirement:** Write unit and feature tests (`Pest v4`) *before* implementation to define
  the expected behavior. Refer to the **[Testing Guide](testing-guide.md)**.

### 3.2 Domain & Application Layer Construction

- **Eloquent Models**: Implement UUID-based entities. Ensure **No physical foreign keys** across
  modules.
- **Service Layer**: Implement the business logic within the **Service Layer** only.
- **Contract-First Implementation**: Ensure the service implements its designated Interface.
- **Dynamic Configuration**: Use `setting($key)` for application-wide values; never hard-code.

### 3.3 Interface Construction (Livewire Components)

- **Logic Separation**: Components must remain "thin," delegating all business logic to Services.
- **Localization**: Implement `__('key')` for all user-facing strings.
- **Responsiveness**: Implement mobile-first Tailwind v4 styles.

---

## Phase 4: Verification & Validation (V&V)

This phase ensures both technical correctness (**Verification**) and requirement fulfillment
(**Validation**).

### 4.1 Verification (Building the System Right)

- **Automated Testing**: Execute `php artisan test --parallel`.
- **Static Analysis**: Run `composer lint` (Pint) and `npm run lint`.
- **Security Audit**: Perform manual/automated security reviews (XSS, SQLi, IDOR, SSRF).

### 4.2 Validation (Building the Right System)

- **Spec Validation**: Manually verify the feature against the original **Internara Specs**.
- **UX Validation**: Ensure the interface provides a professional and intuitive experience across
  all supported viewports.

### 4.3 Repository Synchronization (Git Protocols)

- **Action:** Stage and commit changes following the **[GitHub Protocols](github-protocols.md)**.
- **Protocol**: Use **Conventional Commits** and ensure branch alignment with the milestone.

---

## Phase 5: Artifact Synchronization & Evolution (Closure)

The final phase ensures documentation and system metadata reflect the current state of the
application.

### 5.1 Documentation Standards (The SSoT)

Work is "incomplete" until documentation converges with code.

- **Technical Rigor**: Documentation must be precise, analytical, and grounded in architectural reality.
- **No Simplification**: Maintain full technical depth; never truncate or simplify definitions.
- **English-Only**: All technical documentation must be in professional English.
- **Artifact Update**: Update `docs/versions/` (Release Notes) and `docs/internal/blueprints/`
  (Status).
- **ToC Synchronization**: Register new documents in `docs/internal/table-of-contents.md`.

### 5.2 Application Metadata

- Update `app_info.json` if a major milestone or version change has occurred.

---

_By rigorously following this workflow, we ensure that every line of code contributes directly to
the product goals defined in the Internara Specs through a scientifically sound engineering
process._