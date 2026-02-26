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
- **Conflict Resolution**: All technical friction MUST be resolved using the authoritative 
  **S3 Hierarchy** (Secure > Sustain > Scalable) defined in **[Conflict Resolution Policy](policy.md)**.
- **Execute with TDD**: Use tests to define behavioral, structural, and accessibility boundaries.
- **Close with Documentation**: Finalize the work by synchronizing the technical record (Wiki, 
  Metadata) with the implemented reality.

---

## 🔄 The 8-Step Engineering Workflow

### Step 1: Requirements Analysis & Audit (Understand)

Before any action, a developer must achieve full comprehension of the goal and the current state.

- **Traceability Check**: Analyze the **System Requirements Specification (SyRS)** to identify which
  Requirement IDs (e.g., `[SYRS-F-101]`) are being addressed.
- **SLRI & PII Audit**: 
    - Identify cross-module data relationships (SLRI).
    - Classify PII fields requiring encryption and masking.
- **Security Scoping (OWASP)**: Identify potential attack vectors (Broken Access Control, 
  Injection, etc.) related to the feature.
- **Technical Audit**: Identify potential impacts on existing modules. **Strictly verify** that no 
  physical foreign keys across modules are planned.

### Step 2: Architectural Alignment (Documentation Opening)

Synchronize the technical design before the implementation phase begins.

- **Blueprint Refinement**: Update or create an **Application Blueprint**
  (`docs/dev/blueprints/`). Define:
    - **Service Contracts** (Interfaces) using SOLID principles (ISP, DIP).
    - **Logic Dualism (CQRS)**: Distinguish between Query (EloquentQuery) and Command (BaseService) logic.
    - **Model Hooks** (PHP 8.4) for virtual/calculated attributes.
    - **Slot Registry** injection points for UI decoration.
- **Namespace Protocol**: Ensure the `src` omission rule is applied (`Modules\{Module}\...`).
- **Conflict Filter**: Apply the **S3 Filter** to ensure security and maintenance are prioritized over 
  immediate speed or complex scalability.

### Step 3: TDD RED Phase (Verification First)

Establish the "Police" and the "Goal" through automated testing.

- **Architecture Testing**: Define structural invariants in the global architecture suite 
  (`tests/Arch/`). Enforce:
    - No direct model usage from other modules (Modular Sovereignty).
    - No legacy Eloquent accessors (Mandate PHP 8.4 Hooks).
    - Thin Component rules for Livewire (Clean Architecture).
- **Functional Testing**: Write behavioral test suites (Pest v4) mirroring the source structure 1:1.
- **Accessibility Testing**: Write tests for WCAG 2.1 AA compliance (ARIA, semantic markers).
- **Failure Verification**: Run `composer test` and confirm they fail.

### Step 4: Construction Phase (Green Implementation)

Implement the minimal code required to satisfy the tests, guided by the **S3 Doctrine**:

- **Secure (S1)**: 
    - **OWASP Mitigation**: Enforce **RBAC & IDOR** protection via Policies. 
    - **PET**: Use **UUID v4** identities, encrypt PII at rest, and mask in log sinks.
    - **Zero-Trust**: Validate and sanitize all external inputs at the PEP.
- **Sustain (S2)**: 
    - **Clean Code**: Adhere to SOLID, DRY, KISS, and YAGNI heuristics.
    - **Zero Hard-coding**: Resolve all text via translation keys (`__('module::file.key')`).
    - **PHP 8.4 Construction**: Use Property Hooks for virtual/calculated attributes.
- **Scalable (S3)**: 
    - **CQRS**: Separate read and write concerns in the Service Layer. 
    - **Contract-First**: Cross-module communication MUST use Interfaces.

### Step 5: High-Fidelity Validation (The 3S Audit)

Ensure the implementation meets both functional and non-functional quality standards using the 
mandatory **3S Audit** protocol defined in the **[Code Quality Standardization](quality.md)**.

- **Success Verification**: Run the test suites until all tests pass (**Green**).
- **Static Analysis & Linting**: Execute `composer lint` (Pint + Prettier + PHPStan).
- **A11y Audit**: Verify **WCAG 2.1 AA** compliance (Keyboard nav, ARIA, Semantic HTML).
- **Performance Audit**: Check for N+1 queries. Mandate `paginate()` or `cursor()` for datasets.
- **Refactoring**: Improve code structure while ensuring tests remain green.

### Step 6: Documentation Synchronization (Closure)

Close the loop between implementation and the technical record.

- **Technical Narration**: Update the **Developer Wiki** with architectural rationale and 
  newly introduced protocols.
- **Metadata Sync**: Update `app_info.json` versioning if a milestone is reached.
- **V&V Mapping**: Ensure every Requirement ID is successfully verified in the test records and linked to the corresponding **Blueprint ID**.

### Step 7: Repository Closure (Atomic Commit)

Prepare the changes for the permanent version control history.

- **Final Self-Audit**: Use `git status` and `git diff`. Remove diagnostic code.
- **Conventional Commits**: Use the `type(scope): description` pattern (English-only).

### Step 8: Remote Synchronization (Push)

Transfer the validated and documented engineering artifacts to the central repository.

- **Final Gate Check**: Confirm all previous steps (Tests, Lint, A11y, Metadata Sync) are successful.
- **PR Readiness**: Prepare the Pull Request with traceability to the original blueprint.

---

## 📊 Summary of Quality Gates

| Stage | Gate                 | Requirement                                     | Tooling / Command          |
| :---- | :------------------- | :---------------------------------------------- | :------------------------- |
| **S1**| **Security**         | RBAC active, PII masked, IDOR protection.       | Manual Audit / S3 Checklist|
| **S2**| **Functional**       | 100% behavioral fulfillment of SyRS (>90%).     | `composer test` (Pest)     |
| **S2**| **Sustainability**   | PHP 8.4 (Hooks/Vis), SOLID/DRY/KISS, i18n.      | `composer lint` (Pint)     |
| **S2**| **Accessibility**    | WCAG 2.1 AA, Semantic HTML, ARIA.               | `composer test` (A11y)     |
| **S2**| **Documentation**    | Blueprints and Wiki are in sync (Sync or Sink). | Manual V&V                 |
| **S3**| **Architectural**    | Modular isolation, Contract-First, Events.      | `composer test` (Arch)     |
| **S3**| **Performance**      | Zero N+1 queries, memory-efficient data.        | Manual Audit / `app:test`  |

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
