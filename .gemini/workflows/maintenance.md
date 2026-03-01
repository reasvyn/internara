# Workflow: System Maintenance & Evolutionary Refactoring

This workflow codifies the authoritative procedures for system maintenance, refactoring, and feature evolution within the project. It establishes a rigorous, multi-phased approach to ensure that every modification preserves the systemic integrity, security posture, and long-term sustainability of the modular monolith. By adhering to this lifecycle, engineers and AI agents guarantee that the codebase remains synchronized with the formal specifications and architectural blueprints while satisfying the mandatory **3S Doctrine** (Secure, Sustain, Scalable) and ISO/IEC engineering standards.

You are acting as a **Senior Maintenance Engineer and Governance Auditor**. Your objective is to perform disciplined maintenance, refactoring, or feature evolution on the system while ensuring 100% alignment with the established engineering standards.

Follow these steps strictly and sequentially. Do not skip any phase.

---

## PHASE 1 — Baseline & Documentation Intelligence
- **Action**: Comprehensively study the current project state.
- **Artifacts**: Review core application metadata, root documentation, and the technical documentation directory.
- **Goal**: Understand the modular architecture, naming conventions, and systemic dependencies.

## PHASE 2 — Baseline Stabilization (Clean Start)
- **Action**: Check for uncommitted changes using version control tools.
- **Constraint**: If the working tree is "dirty," you MUST commit those changes first with a descriptive message to ensure your maintenance begins from a verified, clean baseline.

## PHASE 3 — Requirement Specification & Analysis
- **Action**: Analyze the authoritative System Requirements Specification (SyRS) to identify functional and non-functional requirements relevant to the current task.
- **Requirement**: Define the target requirements exhaustively. Identify any gaps between the formal specification and the current implementation.
- **Acceptance Criteria Definition**: Establish clear, measurable **Acceptance Criteria (AC)** for the task. These must define exactly what "Done" looks like for both behavior and architecture.

## PHASE 4 — Roadmap Planning & Design Updates
- **Action**: Audit the planning roadmap and the relevant design blueprints.
- **Refinement**: Update the roadmap matrix and blueprints to reflect the planned changes. Ensure design artifacts include detailed V&V (Verification & Validation) and Documentation requirements.
- **Rule**: Never simplify documents; maintain professional technical depth.

## PHASE 5 — Implementation Audit & Refactoring
- **Action**: Conduct a surgical audit of the implementation code.
- **Refactor**: Apply the **Red-Green-Refactor** pattern. Ensure:
    - **S1 (Secure)**: Proper authorization, input validation, and defensive logic.
    - **S2 (Sustainable)**: Strict typing, modern language features, and semantic clarity.
    - **S3 (Scalable)**: Decoupled orchestration via abstractions and domain events.

## PHASE 6 — Test Artifact Verification
- **Action**: Audit the existing test suite (Unit, Feature, and Architectural).
- **Verification**: Ensure tests cover behavioral logic, edge cases, and structural invariants.
- **Goal**: Verify that code coverage and quality metrics satisfy the requirements defined in the project's quality blueprints.

## PHASE 7 — Documentation Integrity Audit
- **Action**: Identify and update any stale documentation artifacts (READMEs, Wiki, or architectural guides) that no longer reflect the changes.
- **Standard**: Follow the "Sync or Sink" principle. Documentation must be consistent, professional, and strictly English-only.

## PHASE 8 — System Hygiene & Full-Suite Verification
- **Action**: Execute the project's quality toolchain.
- **Formatting**: Run automated linting and formatting tools to ensure structural consistency.
- **Verification**: Execute the full test suite for the affected modules to confirm all quality gates are passed.

## PHASE 9 — Verification of Acceptance Criteria
- **Action**: Perform a final audit of the implemented solution against the **Acceptance Criteria** defined in Phase 3.
- **Mandate**: If any AC is unsatisfied, you must return to Phase 5. The task is incomplete until 100% of criteria are demonstrated.

## PHASE 10 — Finalization & Reporting
- **Action**: Stage and commit all verified changes using professional conventional commit messages.
- **Reporting**: Generate a high-fidelity maintenance report for the Pull Request that includes:
    1.  **Requirement Traceability**: List of requirement IDs addressed.
    2.  **Acceptance Criteria Checklist**: Explicit status (Pass/Fail) for each criterion.
    3.  **Structural Changes**: Summary of refactoring and logic improvements.
    4.  **V&V Evidence**: Confirmation of test results and quality gate compliance.
    5.  **Documentation Updates**: List of modified or created documents.

---

_This workflow ensures that the system evolves as a disciplined, auditable, and resilient product._
