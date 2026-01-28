# GitHub Protocols: Configuration Management Standards

This document formalizes the **Configuration Management (CM)** protocols for the Internara project,
adhering to **ISO/IEC 12207** and **ISO 10007**. It ensures that every code modification is
traceable, secure, and aligned with the architectural invariants defined in the
**[Architecture Description](architecture-guide.md)**.

> **Governance Mandate:** All GitHub operations must facilitate the **Verification & Validation**
> of requirements defined in the **[Internara Specs](internara-specs.md)**.

---

## 1. Commit Protocols: Traceability & Semantics

Internara utilizes **Conventional Commits** to establish a mathematically clear and searchable
history of system evolution.

### 1.1 Atomic Commit Specification

Every commit must represent a single, atomic change to the system state.
- **Format**: `<type>(<scope>): <description>`
- **Types (ISO/IEC 12207 Alignment)**:
    - `feat`: New capability (corresponds to functional requirement fulfillment).
    - `fix`: Resolution of a verified defect.
    - `refactor`: Structural improvement without behavioral change (Technical Debt management).
    - `docs`: Modification of the engineering record.
    - `test`: Construction of verification artifacts.
    - `chore`: Infrastructure or toolchain updates.
- **Scope**: Must identify the impacted module (e.g., `attendance`, `setup`) or `shared`.

### 1.2 Traceability Requirement

Commits should link to the corresponding Issue or Blueprint task to provide a full audit trail from
Requirement to Execution.
- **Syntax**: `Refs #123` or `Closes #123`.

---

## 2. Configuration Control: Branching Strategy

Internara employs a modified **Git Flow** strategy to ensure high-fidelity integration and
continuous verification.

- **`main`**: The **Authoritative Baseline**. Represents the current stable state of the system.
- **`develop`**: The **Integration Baseline**. Used for synchronizing features before final
  validation.
- **`feature/{module}/{description}`**: For isolated construction of new capabilities.
- **`release/{version}`**: For final stabilization, documentation synchronization, and V&V before
  baseline promotion.
- **`hotfix/{description}`**: For critical corrective maintenance targeting the `main` branch.

---

## 3. Configuration Identification: Tagging & Versioning

Tags serve as immutable **Configuration Baselines**, identifying specific snapshots of the system.

- **Standard**: Semantic Versioning (**SemVer**) with a `v` prefix (e.g., `v0.9.0-alpha`).
- **Baselines**:
    - **Annotated Tags**: Mandatory for all releases (`git tag -a`) to include provenance data.
    - **Immutability Invariant**: Baseline tags must never be modified or deleted once synchronized
      with the remote repository.

---

## 4. Release Management: Baseline Publication

Releases transform technical snapshots into validated delivery artifacts.

- **Release Narrative**: Must be derived from the **[Software Lifecycle](software-lifecycle.md)**
  and correspond to the analytical release notes in `docs/versions/`.
- **Maturity Identification**: Pre-release identifiers (Alpha, Beta, RC) must be used for any
  baseline not yet certified as Stable.

### 4.1 CLI Synchronization (gh-cli)
```bash
gh release create v0.9.0-alpha --title "v0.9.0-alpha (Theme)" --notes-file docs/versions/v0.9.0.md --prerelease
```

---

## 5. Verification Gate: Pull Requests (PRs)

Pull Requests are the mandatory **Quality Gate** for configuration changes.

- **Atomic Integrity**: Each PR must address a single feature or defect.
- **Verification Requirement**: PRs cannot be merged without 100% test pass rate and clean static
  analysis.
- **Isolation Check**: The reviewer must verify that the changes do not violate **Modular Isolation**
  or **Contract-First** invariants.

---

## 6. Strategic State Synchronization (Full Sweep)

To maintain zero configuration drift, a full synchronization sweep is required at every milestone
completion.

1.  **Repository Sync**: `git fetch --all --tags --prune`.
2.  **Artifact Audit**: Ensure local documentation in `docs/` exactly matches the remote release
    state on GitHub.
3.  **Milestone Audit**: Synchronize GitHub Milestones with the version roadmap defined in
    `docs/versions/`.
4.  **Traceability Sweep**: Ensure all closed Issues are correctly linked to the corresponding
    milestone and series label (e.g., `Series: ARC01-BOOT`).

---

_Strict adherence to these configuration management protocols ensures that Internara remains an
engineered, traceable, and reliable system throughout its evolution._