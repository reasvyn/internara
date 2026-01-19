# Development Workflow: Feature Engineering Lifecycle

This document serves as the **Standard Operating Procedure (SOP)** for all engineering activities
within the Internara project. It bridges high-level version planning with the granular technical
steps required to build features in our Modular Monolith architecture.

**The Golden Rule:** Never start coding without a plan. Never finish without **Artifact
Synchronization**.

---

## 🛠 Phase 1: Pre-Development (Context & Planning)

Before writing code, you must establish the "Why" and "What" through analytical contextualization.

### 1.1 Review Historical Context

- **Action**: Read the deep analytical narratives of the **current and two previous versions** in
  `docs/versions/`.
- **Goal**: Understand the technical rationale and architectural evolution to ensure your
  implementation is sustainable.

### 1.2 Formulate Implementation Plan

- **Action**: Construct a step-by-step plan that breaks down complexity.
- **Location**: Store the plan in `docs/internal/plans/vX.X.x-alpha.md`.
- **Goal**: Identify cross-module dependencies and required **Contracts** early.

---

## 💻 Phase 2: Development Execution (Implementation)

Follow the downward flow of the Internara Layered Architecture.

### 2.1 The Data Layer (Eloquent Models)

- **Primary Keys**: Always use UUIDs via the `HasUuid` concern.
- **Module Boundaries**: Do not use physical foreign keys for cross-module relations. Use indexed
  UUID columns.
- **State Management**: Use `HasStatuses` for entities with lifecycles.

### 2.2 The Logic Layer (Services)

- **Brain of the Feature**: Services orchestrate all business rules.
- **Base Class**: CRUD services should extend `Modules\Shared\Services\EloquentQuery`.
- **Decoupling**: If you need data from another module, type-hint its **Contract**, never the
  concrete service.

### 2.3 The UI Layer (Livewire & Volt)

- **Thin Components**: Livewire components handle UI logic and state. Business logic must reside in
  Services.
- **Dependency Injection**: Always inject services in the `boot()` method.
- **Design System**: Use standardized components from the `UI` module (MaryUI/DaisyUI).

---

## ✅ Phase 3: Post-Development (Verification & Artifact Sync)

A feature is not "Done" until it passes the **Iterative Sync Cycle**.

### 3.1 Iterative QA Cycle

- **Testing (Pest)**: Run unit and feature tests. `php artisan test --parallel`.
- **Security Audit**: Manually verify IDOR, XSS, and authorization gates (`$user->can()`).
- **Quality & Linting**: Run `vendor/bin/pint` and `npm run lint`.

### 3.2 Continuous Artifact Synchronization

Documentation is treated as code (**Doc-as-Code**). Update the following artifacts:

- **Main README**: Update the root `README.md` to reflect overall project status and metrics.
- **Analytical Version Note**: Update or create the narrative in `docs/versions/{status}/vX.X.X.md` (where status is releases, unreleases, or archived).
- **Application Info**: Update `app_info.json` if milestone reached.
- **Changelog**: Add detailed entries under the `[Unreleased]` or current version section.
- **Technical Guides**: Synchronize module-specific READMEs and architectural docs.

---

_By adhering to this workflow, we ensure that Internara remains a professional, predictable, and
high-quality project. Documentation is as important as the code itself._
