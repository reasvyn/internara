# Version Management Guide

This document defines the authoritative standards for versioning, lifecycle classification, and
support policies within the Internara project. It ensures that every system milestone is
analytically precise and operationally unambiguous.

> **Governance Mandate:** All versioning decisions must align with the milestones defined in the
> **[Internara Specs](internara-specs.md)**.

---

## 1. Versioning Standard (SemVer)

Internara adheres to **Semantic Versioning (SemVer)**: `MAJOR.MINOR.PATCH`.

- **MAJOR**: Incompatible API changes or fundamental architectural shifts.
- **MINOR**: Backward-compatible feature additions (aligned with Spec Milestones).
- **PATCH**: Backward-compatible bug fixes or security patches.
- **Pre-release Identifiers**: Used during non-production stages (e.g., `v0.9.0-alpha`).

---

## 2. The 3-Axis Lifecycle Model

Internara explicitly separates **Maturity**, **Support Policy**, and **Operational Status** to avoid
semantic overlap.

### 2.1 Maturity Stages (Quality & Completeness)

| Stage                  | Definition                                            |
| ---------------------- | ----------------------------------------------------- |
| Experimental           | Architectural or conceptual validation.               |
| Alpha                  | Core features incomplete; breaking changes expected.  |
| Beta                   | Feature complete; focus on stabilization.             |
| Release Candidate (RC) | Potential final release; only critical fixes allowed. |
| Stable                 | Production-ready with defined guarantees.             |

### 2.2 Support Policies (Maintenance Contract)

| Policy        | Definition                             | Guarantee      |
| ------------- | -------------------------------------- | -------------- |
| Full Support  | Active maintenance and improvements.   | Bug + Security |
| Bugfix Only   | Maintenance without feature additions. | Bug fixes      |
| Security Only | Critical vulnerability patches only.   | Security fixes |
| Snapshot      | Point-in-time release provided as-is.  | None           |
| EOL           | End of Life.                           | None           |

### 2.3 Operational Status (Current Reality)

| Status      | Meaning                                      |
| ----------- | -------------------------------------------- |
| Planned     | Identified in Specs but not implemented.     |
| In Progress | Under active construction.                   |
| Preview     | Accessible for demonstration or evaluation.  |
| Released    | Publicly tagged and distributed.             |
| Deprecated  | Accessible but no longer recommended.        |
| Archived    | Closed and historically preserved.           |

---

## 3. Support Matrix Governance

All versions must be tracked in the **[Versions Overview](../versions/versions-overview.md)**.

- **Mandatory Mapping:** All versions with an **EOL** support policy must be marked as
  **Deprecated** or **Archived**.
- **Archival Protocol:** Transitioning a version to **Archived** status and moving its blueprints to
  `archived/` directories requires explicit permission from the project lead.
- **Immutability:** Tags for EOL/Archived versions are **immutable**.
- **Visibility:** Release note visibility for EOL versions remains in `docs/versions/`.
- **Closure:** Issues/PRs targeting EOL versions will be closed.

---

## 4. Version Identity Artifacts

### 4.1 `app_info.json`

The machine-readable identity of the application.

- `version`: The SemVer identifier.
- `series_code`: The architectural lineage identifier.
- `support_policy`: The maintenance contract level.

### 4.2 Analytical Release Notes

Located in `docs/versions/`, these document the **realized outcome** of a version.

- **Naming Convention:** `v{MAJOR}.{MINOR}.{PATCH}.md` (e.g., `v0.9.0.md`).
- **Stable Filenames:** Filenames remain static across maturity stages. Information regarding the
  **Stage** (Alpha, Beta, etc.) is managed as internal metadata within the file.
- **Intent vs. Outcome:** Blueprints document the **intent**, while release notes document the
  **outcome**.

---

## 5. Handling 'Preview' Versions

The **Preview** status serves as an operational bridge between active construction and formal
release, allowing for early demonstration and evaluation.

- **Objective:** Provide a functional environment for stakeholders to review progress without the
  finality of a stable release.
- **Data Policy:** Preview environments may use transient or mock data; persistent data storage is
  not guaranteed.
- **Deployment:** Preview versions are typically deployed to temporary or "Staging" environments.
- **Feedback:** Stakeholders are encouraged to provide feedback, which is captured as internal
  refinement tasks rather than formal bug reports.
- **Lifecycle Transition:** A version in **Preview** must eventually transition to **Released** or
  revert to **In Progress** if significant regressions are identified.

