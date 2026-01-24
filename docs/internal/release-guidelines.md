# Release Guidelines: Navigating the Product Lifecycle

This document defines the protocols for versioning, lifecycle classification, support policies, and
release artifacts within the Internara project. These standards ensure that our development history
is analytically precise, semantically consistent, and operationally unambiguous.

> **Governance Mandate:** All releases must strictly adhere to the milestones and requirements
> defined in the **[Internara Specs](../internal/internara-specs.md)**. A version cannot be
> released if it does not fulfill the Spec Validation criteria for its milestone.

---

## 1. Versioning Standard

Internara adheres to **Semantic Versioning (SemVer)**: `MAJOR.MINOR.PATCH`.

- **MAJOR**: Incompatible API changes or fundamental architectural shifts.
- **MINOR**: Backward-compatible feature additions or scope expansion (aligned with Spec Milestones).
- **PATCH**: Backward-compatible bug fixes or security patches.
- **Pre-release Identifiers**: During non-production stages, versions may include a qualifier (e.g.,
  `v0.6.0-alpha`, `v1.0.0-beta.1`) to indicate maturity—not support guarantees.

> Version numbers identify **artifacts**, not their operational state or support level.

---

## 2. Lifecycle Classification Model

Internara explicitly separates **maturity**, **support policy**, and **operational status** to avoid
semantic overlap and lifecycle ambiguity.

---

### 2.1 Development Stages (Maturity)

Stages describe the **quality and completeness** of a version. They do not imply whether a version
is currently being worked on or supported.

| Stage                  | Definition                                                      |
| ---------------------- | --------------------------------------------------------------- |
| Experimental           | Architectural or conceptual validation.                         |
| Alpha                  | Core features incomplete; breaking changes are expected.        |
| Beta                   | Feature complete; focus on stabilization and defect resolution. |
| Release Candidate (RC) | Potential final release; only critical fixes allowed.           |
| Stable                 | Production-ready with defined guarantees.                       |
| LTS                    | Stable release designated for long-term use.                    |

---

### 2.2 Support Policies (Maintenance Contract)

Policies define **what the project guarantees** after a version is released.

| Policy        | Definition                             | Guarantee      |
| ------------- | -------------------------------------- | -------------- |
| Snapshot      | Point-in-time release provided as-is.  | None           |
| Bugfix Only   | Maintenance without feature additions. | Bug fixes      |
| Security Only | Critical vulnerability patches only.   | Security fixes |
| Full Support  | Active maintenance and improvements.   | Bug + Security |
| EOL           | End of life.                           | None           |

> Support Policy is a **contract**, independent of Stage and Status.

---

### 2.3 Version Status (Operational State)

Status reflects the **current reality** of a version at a given time.

| Status      | Meaning                                     |
| ----------- | ------------------------------------------- |
| Planned     | Identified in **Specs** but not implemented.|
| In Progress | Under active development (Construction).    |
| Released    | Publicly tagged and distributed.            |
| Deprecated  | Still accessible but no longer recommended. |
| Archived    | Closed and historically preserved.          |

---

## 3. Artifact: `app_info.json`

The `app_info.json` file represents the **machine-readable identity** of the application and must
remain semantically strict.

**Required Fields:**

- `version`: The immutable SemVer identifier (e.g., `v0.7.0-alpha`).
- `series_code`: The architectural or business-value lineage identifier (e.g., `ARC01-ORCH-01`).
- `support_policy`: The maintenance contract level (`Snapshot`, `Full Support`, `EOL`, etc.).

> Operational status is managed centrally in **Versions Overview**.

---

## 4. Changelog Management (`CHANGELOG.md`)

The changelog provides a **human-readable delta narrative**, not a substitute for Git history.

### 4.1 Structure

Internara follows **Keep a Changelog** conventions:

- `[Unreleased]`
- `Added`
- `Changed`
- `Deprecated`
- `Removed`
- `Fixed`
- `Security`

### 4.2 Editorial Principle

Entries must describe **impact and intent**, not implementation minutiae.
- **Spec Compliance:** Mention if a change directly fulfills a requirement from `internara-specs.md`.

---

## 5. User-Facing Release Notes (`docs/versions/`)

For each significant milestone, Internara produces a **Release Note**—a human-centric narrative
describing the version's value.

> **Important:** Marking a version as **Released** does NOT automatically trigger the archival 
> of its blueprint. The Application Blueprint must remain in the active directory until 
> explicit permission for archival is granted.

### 5.1 Synchronization Rule

- The Release Note must reflect the **as-built reality**.
- Blueprints describe **intent**. Narratives describe **outcome**.

---

## 6. Release Eligibility Criteria

A version may be marked as `Released` when the following minimum conditions are met (Exit Gate):

1. **Spec Compliance**: The release has been validated against `internara-specs.md` (as per SDLC Phase 4).
2. **Scope Closure**: The intended milestone scope is internally complete.
3. **Artifact Consistency**: Codebase, documentation, and metadata are synchronized.
4. **Changelog Resolution**: `[Unreleased]` entries are reconciled.
5. **Identity Verification**: `app_info.json` reflects the intended version and stage.
6. **Tagging**: A Git tag is created matching the version identifier.

---

## 7. Handling EOL & Archived Releases

Versions transitioned to **EOL (End of Life)** or **Archived** status require specific technical
handling.

> **Mandatory Protocol:** Transitioning a version to **Archived** status and performing physical 
> artifact archival (e.g., moving blueprints to the archived directory) is strictly prohibited 
> without explicit, prior permission from the project lead.

### 7.1 GitHub Mechanics

- **Tag Preservation**: Tags for EOL/Archived versions are **immutable**.
- **Release Description**: Update release notes with a status banner (e.g., `**Status: EOL**`).
- **Latest Label**: Reserved for the most recent active release.

### 7.2 Issue & PR Management

- **Submission Policy**: New Issues/PRs targeting EOL versions will be closed.
- **Resolution**: Direct users to the latest active version series.

### 7.3 Artifact Synchronization

- **Blueprint Archival**: Formal Application Blueprints may be moved to 
  `docs/internal/blueprints/archived/` ONLY after receiving explicit permission.
- **Narrative Visibility**: User-facing release notes for EOL versions remain visible.

---

_Consistent lifecycle semantics transform releases from ad-hoc events into traceable, governable
system milestones, fully aligned with the product specifications._