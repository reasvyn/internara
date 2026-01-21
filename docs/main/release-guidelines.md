# Release Guidelines: Navigating the Product Lifecycle

This document defines the protocols for versioning, lifecycle classification, support policies, and
release artifacts within the Internara project. These standards ensure that our development history
is analytically precise, semantically consistent, and operationally unambiguous.

---

## 1. Versioning Standard

Internara adheres to **Semantic Versioning (SemVer)**: `MAJOR.MINOR.PATCH`.

- **MAJOR**: Incompatible API changes or fundamental architectural shifts.
- **MINOR**: Backward-compatible feature additions or scope expansion.
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
| Planned     | Identified but not yet implemented.         |
| In Progress | Under active development.                   |
| Stabilizing | Feature-frozen, hardening phase.            |
| Released    | Publicly tagged and distributed.            |
| Deprecated  | Still accessible but no longer recommended. |
| Archived    | Closed and historically preserved.          |

> **Note on Archiving:** The `Archived` status is applied based on **explicit developer decision**,
> not solely by lifecycle age. A version may remain visible in the main index if it serves as a
> critical reference.

---

## 3. Artifact: `app_info.json`

The `app_info.json` file represents the **machine-readable identity** of the application and must
remain semantically strict.

**Required Fields:**

* `version`
  The immutable SemVer identifier (e.g., `v0.7.0-alpha`).

* `series_code`
  The architectural or business-value lineage identifier (e.g., `ARC01-ORCH-01`).

* `support_policy`
  The maintenance contract level (`Snapshot`, `Full Support`, `EOL`, etc.).

> Operational status is managed centrally in Versions Overview.

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

Entries must describe **impact and intent**, not implementation minutiae. Low-level details belong
to commit history and analytical version notes.

---

## 5. User-Facing Release Notes (`docs/versions/`)

For each significant milestone, Internara produces a **Release Note**—a human-centric narrative
describing the version's value.

These documents:

- Reside in `docs/versions/`
- Are immutable once the version is released
- Must be written in **friendly, accessible language** suitable for non-technical stakeholders

Lifecycle classification is managed exclusively in **Versions Overview** to maintain a Single Source
of Truth.

**Writing Principle: "Benefits over Features"**

- **Don't say:** "Implemented Polymorphic ComplianceAggregator."
- **Do say:** "Grading is now automated based on attendance and journal data."

**Structure:**

1.  **Metadata**: Version, Series Code, release date.
2.  **Overview**: A high-level summary of "What's New".
3.  **Key Highlights**: The core value delivered to the user (use emojis and clear headers).
4.  **Stability & Quality**: Brief mention of reliability improvements.
5.  **Forward Outlook**: What users can expect next.

---

## 6. Release Eligibility Criteria

A version may be marked as `Released` when the following minimum conditions are met:

1. **Scope Closure**: The intended milestone scope is internally complete.
2. **Artifact Consistency**: Codebase, documentation, and metadata are synchronized.
3. **Changelog Resolution**: `[Unreleased]` entries are reconciled.
4. **Identity Verification**: Application identity reflects the intended version and stage.
5. **Tagging**: A Git tag is created matching the version identifier.

> A release does **not** imply stability, completeness, or long-term support—only that the artifact
> has been formally published.

---

_Consistent lifecycle semantics transform releases from ad-hoc events into traceable, governable
system milestones._
