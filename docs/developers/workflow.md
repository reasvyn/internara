# Software Engineering Lifecycle: Operational Workflow

This document formalizes the **Standardized Engineering Lifecycle** for the Internara project. It is
aligned with **ISO/IEC 12207** (Software Life Cycle Processes) and **ISO/IEC 25010** (System and
Software Quality Models). This workflow ensures that every contribution is traceable,
architecturally sound, and maintainable.

---

## 🏛️ The SLCP Invariant: Systemic Discipline

To ensure the delivery of high-fidelity, ISO-compliant software, Internara enforces a **Standardized
Life Cycle Process (SLCP)**.

1.  **Traceability**: Every engineering action must trace back to an authoritative Requirement ID.
2.  **Modular Sovereignty**: Changes must respect the isolation boundaries of the modular monolith.
3.  **Auditability**: Every implementation must be accompanied by an automated verification record.

---

## 🏛️ Foundational Doctrine: Sync or Sink

Internara operates under a **Documentation-First** philosophy. We believe that **"Code is a
side-effect of a well-documented design."**

- **Open with Documentation**: Never start coding without a clear, documented design or blueprint.
- **Execute with TDD**: Use tests to define behavioral and architectural boundaries.
- **Close with Documentation**: Finalize the work by synchronizing the technical record (Release
  Notes, Wiki) with the implemented reality.

---

## 🔄 The 8-Step Engineering Workflow

### Step 1: Requirements Analysis & Audit (Understand)

Before any action, a developer must achieve full comprehension of the goal and the current state.

- **Traceability Check**: Analyze the **System Requirements Specification (SyRS)** to identify which
  Requirement IDs (e.g., `[SYRS-F-101]`) are being addressed.
- **Technical Audit**: Perform a deep-dive into the existing codebase using search and discovery
  tools. Identify potential impacts on existing modules and detect any "Architectural Debt" that
  needs resolution.
- **Stakeholder Mapping**: Confirm the feature aligns with the designated User Roles (STRS) and
  authorization policies.

### Step 2: Architectural Alignment (Documentation Opening)

Synchronize the technical design before the implementation phase begins.

- **Blueprint Refinement**: Update or create an **Application Blueprint**
  (`docs/developers/blueprints/`). This serves as the "Contract of Intent" and must define modular
  boundaries and data schema.
- **Invariant Definition**: Explicitly document the naming conventions, UUID-based data structures,
  and Service Contracts (Interfaces) that will be applied.
- **Isolation Check**: Ensure no physical foreign keys across modules are planned.

### Step 3: TDD RED Phase (Verification First)

Establish the "Police" and the "Goal" through automated testing.

- **Architecture Testing**: Define structural invariants in the global architecture suite
  (`tests/Arch.php`). Ensure modular isolation and "Thin Component" rules are enforced.
- **Functional Testing**: Write behavioral test suites (Pest v4) that mirror the source structure
  1:1 (**Mirroring Invariant**). Every test should refer to a Requirement ID.
- **Failure Verification**: Run `composer test` and confirm they fail. This proves the requirement
  is not yet met and the tests are correctly identifying the gap.

### Step 4: Construction Phase (Green Implementation)

Implement the minimal code required to satisfy the tests, guided by the **S3 Doctrine**:

- **Secure (S1)**: Sanitize all inputs at the boundary. Enforce **RBAC** via Policies. Use **UUID
  v4** for identity. Mask PII in logs.
- **Sustain (S2)**: Declare `strict_types=1`. Use professional English PHPDoc. Resolve all
  user-facing text via translation keys (`__('module::file.key')`).
- **Scalable (S3)**: Centralize logic in the **Service Layer** (`EloquentQuery`). Maintain **Strict
  Modular Isolation** by communicating exclusively via **Service Contracts**.

### Step 5: Validation & Quality Control (Green Pass)

Ensure the implementation meets both functional and non-functional quality standards.

- **Success Verification**: Run the test suites until all tests pass (**Green**).
- **Static Analysis & Linting**: Execute `composer lint`. This runs **Pint** (PHP) and **Prettier**
  (Blade/JS). Ensure zero violations.
- **Performance Audit**: Check for N+1 queries. Use `paginate()` or `chunk()` for large datasets.
- **Refactoring**: Improve code structure while ensuring tests remain green.

### Step 6: Documentation Synchronization (Closure)

Close the loop between implementation and the technical record.

- **Release Narration**: Document the changes, fixed bugs, and remaining debt in the active
  **Release Note** (e.g., `docs/pubs/releases/`).
- **Knowledge Update**: If new protocols are introduced, update the **Developer Wiki** or technical
  guides.
- **Metadata Sync**: Update `app_info.json` if a version milestone is reached.

### Step 7: Repository Closure (Atomic Commit)

Prepare the changes for the permanent version control history.

- **Final Self-Audit**: Use `git status` and `git diff`. Remove all diagnostic code (`dd()`,
  `dump()`, `Log::debug()` without guards).
- **Conventional Commits**: Use the `type(scope): description` pattern (e.g.,
  `feat(user): add  uuid identity`). Link to issues if applicable.

### Step 8: Remote Synchronization (Push)

Transfer the validated and documented engineering artifacts to the central repository.

- **Final Gate Check**: Confirm all previous steps (Tests, Lint, Docs Sync) are successful.
- **PR Readiness**: Prepare the Pull Request with a clear description and traceability to the
  original blueprint.

---

## 📊 Summary of Quality Gates

| Gate                 | Requirement                                     | Tooling / Command          |
| :------------------- | :---------------------------------------------- | :------------------------- |
| **Architectural**    | No modular leakage, thin components, UUIDs.     | `composer test` (Arch)     |
| **Functional**       | 100% behavioral fulfillment of SyRS.            | `composer test` (Pest)     |
| **Static Quality**   | No syntax errors, strict type compliance.       | `composer lint` (Pint)     |
| **Visual/Stylistic** | Consistent formatting (PSR-12, Prettier).       | `composer lint` (Prettier) |
| **Performance**      | No N+1 queries, memory-efficient data handling. | Manual Audit / `app:test`  |
| **Security**         | RBAC active, PII masked, Zero-Trust validation. | Manual Audit               |
| **Documentation**    | Blueprints and Release Notes are in sync.       | Manual V&V                 |

---

## 🛡️ Operational Safeguards

1.  **Environment Awareness**: Use `is_debug_mode()`, `is_development()`, or `is_testing()` guards
    for environment-specific logic.
2.  **Zero hard-coding**: All configurations must reside in `config()` or `setting()`.
3.  **Strict Isolation**: Never use a Model or concrete class from another domain module.
    **Interface only.**

---

_Non-compliance with this lifecycle is considered an architectural defect. By following these steps,
we ensure that Internara remains a high-quality, professional, and sustainable ecosystem._
