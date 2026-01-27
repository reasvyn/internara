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

| Status      | Meaning                                     |
| ----------- | ------------------------------------------- |
| Planned     | Identified in Specs but not implemented.    |
| In Progress | Under active construction.                  |
| Preview     | Accessible for demonstration or evaluation. |
| Released    | Publicly tagged and distributed.            |
| Deprecated  | Accessible but no longer recommended.       |
| Archived    | Closed and historically preserved.          |

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

## 6. Artifact Synchronization Protocols

Work is strictly **incomplete** until all project artifacts converge with the current state. Every
lifecycle transition (e.g., Promotion, Archival) requires a mandatory audit of the following primary
artifacts:

- **`app_info.json`**: Machine-readable application identity and support policy.
- **`README.md`**: Public interface, project status, and version support matrix.
- **`docs/versions/versions-overview.md`**: Strategic registry of all version series.
- **`Architectural Blueprint`**: Tactical intent and phase sequence tracking.
- **`Release Note`**: Analytical outcome and spec milestone verification.

### 6.1 Checklist: Promotion to 'Released'

When a version moves from **In Progress** to **Released**:

1.  **Identity Alignment**: Ensure `app_info.json` reflects the stable version and policy.

2.  **Note Finalization**: Set header status to `Released` in the release note.

3.  **Registry Sync**: Ensure the link is active in the **Versions Overview**.

4.  **Public Interface**: Update the **README.md** status table.

5.  **Remote Execution**: Create Git tag and GitHub Release per protocols.

### 6.2 Checklist: Transition to 'Archived'

When a version moves from **Released** to **Archived**:

1.  **Metadata Alignment**: Update the header status to `Archived` in the release note.
2.  **Physical Relocation**: Move the release note to `docs/versions/archived/`.
3.  **Blueprint Archival**: Move the Architectural Blueprint to
    `docs/internal/blueprints/archived/`.
4.  **Index Synchronization**: Update all TOC files and the **Versions Overview** links.
5.  **Status Sync**: Update **README.md** to reflect archival and EOL policy.

### 6.3 Registry Streamlining (Cleanup Rules)

To prevent informational bloat and maintain tactical focus, primary registries must be pruned during
the archival process:

1.  **Registry Pruning**: Remove individual rows and links for archived versions from the **Version
    Support Matrix** in both `README.md` and `versions-overview.md`.
2.  **TOC Delegation**: Replace the list of archived releases with a single, clear link to the
    **Archived Release Notes TOC** (`archived/table-of-contents.md`).
3.  **Context Preservation**: Ensure that all removed details are preserved within the archived
    files and their respective Table of Contents.
4.  **Active Focus**: Primary project files must prioritize the **Active Development**, **Latest
    Released**, and **Recent Stable** milestones.
