# Development Workflow: Feature Engineering Lifecycle

This document defines the **engineering workflow** for building features in the Internara project.
It specifies _how work is performed_ to meet the rigorous standards of our **Modular Monolith**.

> **Governance Mandate:** All engineering work must align with the
> **[Internara Specs](../internal/internara-specs.md)** and the
> **[Software Development Lifecycle (SDLC)](../internal/software-lifecycle.md)**. Deviation from the
> Specs requires a formal Change Request.

**Core Principle:** Never start implementation without contextual clarity. Never consider work
complete without **Artifact Synchronization**.

---

## Phase 1: Context & Planning (Requirements Engineering)

This phase corresponds to the **Requirements** and **Design** phases of the SDLC.

### 1.1 Specification Validation

Before any code is written:

- **Action:** Verify the feature request against `docs/internal/internara-specs.md`.
- **Constraint:** Ensure the feature respects **Mobile-First** design and **Multi-Language**
  support.
- **Role Check:** Confirm the feature aligns with the designated User Roles (Instructor, Staff,
  etc.).

### 1.2 Historical & Architectural Review

- **Action:** Review the analytical narratives of the **current version** and **prior milestones**
  in `docs/versions/`.
- **Objective:** Understand existing patterns to avoid architectural regression.

### 1.3 Implementation Planning

For any non-trivial feature:

- **Action:** Create a detailed implementation plan.
- **Artifact:** Store the plan in `docs/internal/blueprints/` scoped to the current version.
- **Content:**
    - Module boundaries involved.
    - New Contracts/Interfaces required.
    - Database schema changes (UUIDs, Indexes).
    - UI Components needed (Mobile-responsive).

---

## Phase 2: Development Execution (Construction)

This phase corresponds to the **Construction** phase of the SDLC.

### 2.1 Domain & Data Layer (Eloquent Models)

- **Identity:** All entities must use UUIDs via the `HasUuid` concern.
- **Isolation:** **No physical foreign keys** between modules. Use indexed UUID columns.
- **Security:** Encrypt sensitive data as defined in the Specs.

### 2.2 Application Logic Layer (Services)

- **Authority:** Services are the _only_ place for business logic.
- **No Hard-Coding:** Use `setting($key)` for application configuration. Never hard-code brand names
  or emails.
- **Decoupling:** Inject **Contracts**, not concrete classes, for cross-module dependencies.

### 2.3 Interface Layer (Livewire & Volt)

- **Presentation Only:** No business logic in components.
- **Mobile-First:** Build for mobile screens first, then enhance for desktop.
- **Localization:** Use `__('key')` for ALL user-facing text.
- **UI Standard:** Use `MaryUI` components for consistency.

---

## Phase 3: Verification (V&V)

This phase corresponds to the **Verification & Validation** phase of the SDLC.

### 3.1 Iterative Verification Cycle

- **Automated Testing:** Run `php artisan test --parallel`.
- **Spec Validation:** Manually verify the feature behaves exactly as described in
  `internara-specs.md`.
- **Static Analysis:**
    - `vendor/bin/pint` (Formatting)
    - `npm run lint` (JS/CSS Linting)

---

## Phase 4: Artifact Synchronization (Closure)

Work is strictly **incomplete** until documentation converges with code.

### 4.1 Documentation Standards (Artifact Synchronization)

Documentation is an engineering requirement. Work is "incomplete" until documentation converges with
code.

- **Analytical Precision**: Documentation must be grounded in facts and architectural reality.
- **English-Only**: All internal technical documentation must be written in professional English.
- **Terminology Consistency**: Use standardized terms (e.g., `setting()`, `Blueprint`,
  `Series Code`).
- **Markdown Only**: Use Markdown (`.md`) exclusively for all technical guides.
- **Narrative Version Notes**: Update the narrative in `docs/versions/` to reflect changes.
- **SSoT**: No document may contradict the **[Internara Specs](../internal/internara-specs.md)**.

### 4.2 Application Metadata

- Update `app_info.json` if a milestone is reached.

---

_By rigorously following this workflow, we ensure that every line of code contributes directly to
the product goals defined in the Internara Specs._
