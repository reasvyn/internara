# Development Workflow: Feature Engineering Lifecycle

This document defines the **engineering workflow** for building features in the Internara project.
It specifies *how work is performed*, not *when versions are released* or *how they are classified
within the SDLC*.

This workflow applies to all feature development within Internara’s **Modular Monolith**
architecture.

**Core Principle**
Never start implementation without contextual clarity.
Never consider work complete without **Artifact Synchronization**.

---

## Phase 1: Pre-Development (Context Establishment)

This phase ensures that implementation decisions are grounded in historical and architectural
context.

### 1.1 Historical Context Review

Before initiating work:

* **Action**: Review the analytical narratives of the **current version** and up to **two prior
  milestones** in `docs/versions/`.
* **Objective**:

  * Understand prior architectural decisions.
  * Avoid redundant abstractions or conceptual regression.

This step is mandatory for changes affecting shared domains, services, or contracts.

---

### 1.2 Implementation Planning

Each non-trivial feature requires an explicit plan.

* **Action**: Decompose the feature into clear implementation steps.
* **Artifact**:
  Store the plan in `docs/internal/plans/` using the relevant version scope
  (e.g., `v0.7.x-alpha.md`).
* **Focus**:

  * Module boundaries
  * Cross-module interactions
  * Required **Contracts** and abstractions

Planning exists to reduce cognitive load during implementation, not to predict final outcomes.

---

## Phase 2: Development Execution (Implementation)

Implementation follows Internara’s architectural layering discipline.

### 2.1 Domain & Data Layer (Eloquent Models)

* **Identity**:
  All entities must use UUIDs via the `HasUuid` concern.
* **Module Isolation**:
  Cross-module relationships must not rely on physical foreign keys. Use indexed UUID references.
* **Lifecycle State**:
  Entities with state transitions must use `HasStatuses`.

The data layer models **business reality**, not UI or persistence convenience.

---

### 2.2 Application Logic Layer (Services)

Services represent the **authoritative source of business logic**.

* **Responsibility**:
  All business rules, orchestration, and validation reside in Services.
* **Base Abstraction**:
  CRUD-oriented services should extend
  `Modules\Shared\Services\EloquentQuery`.
* **Decoupling Rule**:
  When interacting across modules, depend exclusively on **Contracts**, never concrete
  implementations.

Services must remain deterministic and UI-agnostic.

---

### 2.3 Interface Layer (Livewire & Volt)

UI components are responsible for interaction, not business decisions.

* **Thin Components**:
  Livewire and Volt components manage state and events only.
* **Dependency Injection**:
  Services must be injected via the `boot()` lifecycle method.
* **UI Consistency**:
  Use standardized components provided by the `UI` module (MaryUI / DaisyUI).

UI code should remain disposable without risking domain integrity.

---

## Phase 3: Verification & Artifact Synchronization

Implementation is not considered complete until verification and documentation converge.

---

### 3.1 Iterative Verification Cycle

Before considering work finished:

* **Testing**:
  Execute unit and feature tests using Pest
  `php artisan test --parallel`
* **Security Review**:
  Manually validate authorization gates, IDOR exposure, and XSS vectors.
* **Quality Enforcement**:
  Run static analysis and formatting tools

  * `vendor/bin/pint`
  * `npm run lint`

Verification validates correctness, not feature completeness.

---

### 3.2 Artifact Synchronization (Doc-as-Code)

All relevant documentation must reflect the implemented behavior.

Update as applicable:

* **Root README**: High-level project state and capabilities.
* **Analytical Version Notes**:
  Update or append implementation narratives in `docs/versions/`.
* **Application Metadata**:
  Update `app_info.json` if a milestone boundary is reached.
* **Changelog**:
  Record meaningful changes under `[Unreleased]` or the active version section.
* **Technical Documentation**:
  Synchronize module READMEs and architectural references.

Documentation is treated as a **first-class artifact**, not a postscript.

---

*By following this workflow, engineering efforts remain predictable, auditable, and resilient to
complexity. Code quality is enforced through structure; long-term clarity is preserved through
documentation.*
