# Comprehensive Engineering Protocol: Refactoring & System Realignment

This document establishes the authoritative **End-to-End Refactoring Protocol** for the Internara
project, adhering to **ISO/IEC 12207** (Software Life Cycle Processes) and **ISO/IEC 25010**
(Systems and Software Quality Requirements).

---

## ⚖️ Core Mandates & Prohibitions (The Systemic Laws)

The Agent must adhere to these invariants during every phase of the protocol.

### 1. Structural Laws

- **The `src` Omission**: Namespaces MUST omit the `src` segment (e.g., `Modules\User\Models`).
- **Domain Seclusion (Modular DDD)**: Organize logic by business domain:
  `src/({Domain}/){Layer}/{Class}.php`.
- **Domain-to-Module Exception**: If the **Domain Name** is identical to the **ModuleName**, the
  domain folder MUST be omitted (e.g., `modules/User/src/Models/User.php`).
- **Contract/Concern Placement**: Reside in `src/({Domain}/){Layer}/{Contracts|Concerns}/`.
- **Finality Invariant**: Every support, helper, or utility class MUST be declared as **`final`**.

### 2. Logic & Security Laws

- **Zero-Coupling**: Physical foreign keys across module boundaries are **STRICTLY PROHIBITED**.
- **Isolation Invariant**: Cross-module communication MUST occur via **Service Contracts**. Direct
  instantiation of models or concrete classes from foreign modules is forbidden.
- **Privacy Invariant**: Sensitive PII MUST utilize the `encrypted` cast. Masking in logs is
  mandatory.
- **Localization Invariant**: User-facing raw strings are **STRICTLY PROHIBITED**. Use translation
  keys.
- **Thin Component Rule**: Livewire components MUST be thin orchestrators; business logic belongs in
  Services.

### 3. Verification & Documentation Laws

- **Mirroring Invariant**: The `tests/` directory MUST exactly mirror the `src/` hierarchy.
- **Test Pattern**: Use **`test(...)`** and **`describe(...)`** only. Legacy PHPUnit styles are
  discouraged.
- **Traceability**: Every test MUST be linked to a requirement ID (e.g.,
  `test('it fulfills [SYRS-ID]')`).
- **Blueprint Purity**: Checklists (`[ ]` or `[x]`) and status taxonomy are **FORBIDDEN** in
  blueprints. Use narrative points.

### 4. Operational Ethics & Scope

- **Audit-First**: Always verify existing state and user changes before execution.
- **No Batch Actions**: Performing bulk automated modifications across multiple modules in a single
  pass is **STRICTLY PROHIBITED**. Refactoring must be performed module-by-module or
  domain-by-domain to ensure atomic integrity and prevent destructive regressions.
- **Non-Destructive**: Do NOT delete files unless explicitly requested. Prioritize relocation.
- **Refactoring Scope**: The Agent operates under the refactoring scope. Providing comprehensive
  test code is mandatory, but **execution** of test suites is handled by specialized agents.
- **Pragmatic SOC (Separation of Concerns)**: Apply SOC logically but avoid over-engineering. Do not
  create external utilities or traits for logic that is highly specific and not reusable to prevent
  systemic bloat. If a utility is unique to a class, keep it encapsulated.

---

## 🎯 Scope & Permitted Actions (Operational Boundary)

The Agent's operational authority under this protocol is focused on structural integrity and logic
encapsulation of _existing_ code without changing external behavioral contracts.

### 1. Protocol Scope

- **Structural Realignment**: Relocating existing classes, traits, and interfaces to their correct
  **Modular DDD** domain folders.
- **Logic Hardening**: Moving business logic from Livewire components into the **Service Layer**
  (Thin Component Rule).
- **Distinction**: This protocol focuses on **reorganizing and securing existing code**, whereas
  **[Developing](./developing.md)** focuses on the **construction of new features**.

### 2. Authorized Actions

- **Repository Orchestration**: Branch creation and management via `git checkout -b`.
- **Atomic Refactoring Commits**: Utilizing **Conventional Commits** (`refactor(...)`) referencing
  the Issue.
- **PR Management**: Creating **Pull Requests** via `gh pr create` for architectural review.
- **Relocation**: Moving code and test files to correct DDD folders.
- **Namespace Repair**: Updating `use` statements globally to reflect relocations.

---

## Phase 0: Discovery & Strategic Immersion

Before any action, The Agent must achieve full contextual immersion to prevent architectural drift.

- **Authoritative Review**: Re-read `specs.md` (SSoT) and `architecture.md` to re-align with
  strategic goals.
- **Dependency Mapping**: Use `search_file_content` to identify all modules depending on the target
  module via Service Contracts.
- **Constraint Audit**: Identify any "Critical Technical Debts" listed in `docs/developers/debts/`
  related to the module.

---

## Phase 1: Analytical Audit & Proposal

Analysis of the current state and formulation of an actionable plan.

- **Structural Inventory**: List all files in `src/` and `tests/` to identify non-DDD placements.
- **Proposed Blueprint Update**: Formulate a technical proposal for the Blueprint refinement and
  **obtain explicit user approval** before touching the code.

---

## Phase 2: Structural Migration (Atomic & Traceable)

Realignment of the physical and logical structure of the module.

- **Branch Management**: Perform refactoring on a dedicated branch:
  `refactor/{module}/{description}`.
- **Relocation (Non-Destructive)**: Move components to the hierarchy defined in Structural Laws.
- **Namespace & Import Sync**:
    - Update `namespace` (omitting `src`).
    - Update `use` statements in the module.
    - **Global Repair**: Update every external file referencing the relocated components.
- **Atomic Commits**: Use conventional commits referencing the refactoring issue (e.g.,
  `refactor(user): move models to DDD structure. Refs #456`).
- **Provider Integrity**: Update `bindings`, `policies`, `commands`, and `viewSlots` in the Module's
  Service Provider.

---

## Phase 3: Invariant Enforcement & Hardening

Applying the project's "Hukum" (Laws) to the codebase.

- **Terminology & i18n Sync**:
    - Update variables to universal terminology (e.g., `nisn` -> `national_identifier`).
    - Extract all raw strings to translation files.
- **Security & a11y Hardening**:
    - Apply encryption and masking.
    - Verify Turnstile/Honeypot presence.
    - Ensure WCAG 2.1 Level AA compliance in UI.

---

## Phase 4: Verification & Validation (The V-Model)

Executable proof of integrity and requirement fulfillment.

- **Mirroring Invariant**: Move and rename test files to exactly mirror the new `src/` structure.
- **Execution Gates**:
    - **Logic**: Run `php artisan app:test {Module}`.
    - **Architecture**: Run Arch tests to ensure zero-coupling and domain isolation.
    - **Static Analysis**: Run `composer lint`.

---

## Phase 5: SSoT Synchronization & Documentation Audit

Closing the loop between code and documentation.

- **Engineering Record Sync**: Update `README.md`, `governance.md`, and module-level `README.md`.
- **Blueprint Finalization**: Update the Application Blueprint's pillars to reflect the refactored
  state.
- **Traceability Check**: Ensure all refactored logic remains traceable to SyRS requirements.

---

## Phase 6: Final Verification & Closure

Executable proof of refactoring success.

- **Integrity Audit**: Verify that all physical relocations are correctly reflected in `bindings`
  and `imports`.
- **Branch Deletion**: Once the refactoring PR is merged, delete the feature branch:
  `git branch -d feature/...`.
- **Issue Closure**: Confirm the linked architectural issue is closed.
- **Keypoints Summary**: Provide a report of Actions, Rationales, and Verified Outcomes.

---

_Adherence to these mandates is non-negotiable to maintain the architectural integrity of
Internara._
