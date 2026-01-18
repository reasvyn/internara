# Release Guidelines & Protocols

This document establishes the strict protocols for versioning, preparing, and deploying releases for
the Internara project. Adherence to these guidelines ensures stability, traceability, and accurate
documentation.

---

## 1. Versioning Strategy

Internara follows **Semantic Versioning (SemVer 2.0.0)** with specific stage suffixes.

### Format: `vX.Y.Z-stage`

- **Major (X):** Breaking changes to the core architecture or API.
- **Minor (Y):** New features (backward compatible) or significant module additions.
- **Patch (Z):** Bug fixes, hotfixes, or minor refinements.
- **Stage:**
    - `alpha`: In-development, feature incomplete, potential breaking changes.
    - `beta`: Feature complete, testing phase, stable APIs.
    - `rc`: Release Candidate.
    - _(none)_: Production stable.

**Example:** `v0.4.0-alpha`

---

## 2. The "Definition of Done" & Pre-Release Standard

Before tagging a release, the system must undergo an iterative cycle of verification and
documentation. Every technical change **requires** a subsequent artifact synchronization.

### A. Continuous Quality & Security Cycle

- **Repeated Testing:** All Unit and Feature tests must pass (`php artisan test`). This must be
  rerun after any post-development refinement.
- **Iterative Linting:** Code must be formatted using Pint/Prettier after every change.
- **Deep Clean:** No `dd()`, `dump()`, or dead code.
- **Security Posture:** Explicit verification of IDOR protection and PII handling.

### B. Analytical Documentation (Mandatory)

The Internara project rejects superficial checklists. Releases are documented through **deep
descriptive analysis** in the version notes.

- **Analytical Narrative:** Version documents (`docs/versions/vX.X.X.md`) must describe the
  _technical rationale_ and _architectural impact_ of every keystone.
- **Continuous Synchronization:** All artifacts listed below must be updated immediately as changes
  occur, not just at the end of the release cycle.

### 2.1 Mandatory Documentation Artifacts (The Artifact Sync)

The following documents must be synchronized iteratively to maintain the "Single Source of Truth":

1.  **`docs/versions/vX.Y.Z-stage.md`**: Deep analytical narrative of the version's evolution.
2.  **`app_info.json`**: Static application metadata (name, version, series_code, author).
3.  **`RELEASE_NOTES.md`**: Root-level highlights and links to deep analytical narratives.
4.  **`README.md`**: Update version indicators and project status.
5.  **`CHANGELOG.md`**: User-facing summary of technical additions and fixes.
6.  **`docs/versions/versions-overview.md`**: Historical context update.
7.  **`docs/main/modules/{module-name}.md`**: Comprehensive technical guides for modules.
8.  **`docs/main/architecture-guide.md` & `development-conventions.md`**: Updated to reflect shifts
    in patterns.

---

## 3. The Release Workflow (Unified Alur Kerja)

The release process is the final step of the **[Development Workflow](development-workflow.md)**.

1.  **Final Sync Cycle:** Perform one last QA and Artifact Sync cycle.
2.  **Commit All:** Stage and commit all changes (including documentation).
3.  **Tagging:** Create a git tag using SemVer.
4.  **GitHub Protocol:** Push tags and create a formal GitHub Release using the analytical narrative
    as the description basis.

---

## 4. Post-Release Checklist

- [ ] **Update Current Version:** Update the status in `README.md` and
      `docs/versions/versions-overview.md`.
- [ ] **New Iteration:** Create a new "In Progress" section in `CHANGELOG.md` for the next version.
- [ ] **Cleanup:** Remove any temporary branches used for the release features.

---

## 5. Standard Release Message Template

Use the following template for `CHANGELOG.md` entries and GitHub Release descriptions.

```markdown
## [vX.Y.Z-stage] - YYYY-MM-DD (SERIES-CODE)

### 🚀 Overview

Brief, one-sentence summary of the release's primary goal (e.g., "Introduces the School and
Department management modules.").
```

### Series Codes Reference

- **INIT:** Project Initialization

---

**Navigation**

[← Previous: Artisan Commands Reference](artisan-commands-reference.md) |
[Next: Package Integration Overview →](packages/packages-overview.md)
