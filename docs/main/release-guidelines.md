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

## 2. Pre-Release Checklist (The "Definition of Done")

Before tagging a release or merging to the `main` branch for a release, the following checks
**must** be passed:

### A. Code Quality & Security

- [ ] **Tests:** All Unit and Feature tests must pass (`php artisan test`).
- [ ] **Linting:** Code must be formatted using Pint/Prettier (`npm run format` &
      `./vendor/bin/pint`).
- [ ] **Static Analysis:** No critical issues found by Larastan (level 5+).
- [ ] **Debug Cleanup:** No `dd()`, `dump()`, `console.log()`, or commented-out "dead code" remains.
- [ ] **Secrets:** No hardcoded secrets or API keys in the codebase.

### B. Documentation

- [ ] **PHPDoc:** All new classes and methods have comprehensive PHPDoc.
- [ ] **Mandatory Synchronization:** All artifacts listed in the
      [Mandatory Documentation Artifacts](#21-mandatory-documentation-artifacts) section are updated
      and consistent.

### 2.1 Mandatory Documentation Artifacts

The following documents **must** be synchronized and updated before every release to maintain the
"Single Source of Truth":

1.  **`docs/versions/vX.Y.Z-stage.md`**: Dedicated release document telling the "Engineering Story"
    (Goals, Keystones, Verification).
2.  **`README.md`**: Update the "Version History" table and the "Current Status" indicator.
3.  **`CHANGELOG.md`**: Move the "In Progress" work to a permanent version entry using the standard
    template.
4.  **`docs/versions/table-of-contents.md`**: Add the new version link to the versions TOC.
5.  **`docs/versions/versions-overview.md`**: Update the release history list and the current
    operational phase details.
6.  **`docs/main/modules/{module-name}.md`**: Ensure technical guides for new or modified modules
    are complete (no stubs).
7.  **`docs/main/architecture-guide.md`**: Update if there are any shifts in communication patterns
    or system layers.
8.  **`docs/main/development-conventions.md`**: Update if new coding standards or mandatory patterns
    were introduced.
9.  **`SECURITY.md`**: Verify security protocols are still accurate for the new feature set.

### C. Build & Assets

- [ ] **Frontend:** Assets compile successfully (`npm run build`).
- [ ] **Migrations:** All migrations run cleanly (`php artisan migrate:fresh --seed` works).

---

## 3. The Release Process

1.  **Final Commit:** Ensure all changes are staged and committed.
2.  **Tagging:** Create a git tag for the version.
    ```bash
    git tag -a v0.4.0-alpha -m "Release v0.4.0-alpha: Institutional Phase"
    ```
3.  **Push:** Push the commit and the tag.
    ```bash
    git push origin main --tags
    ```
4.  **GitHub Release:** Create a release on GitHub matching the tag, pasting the content from the
    **Release Message Template**.

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
