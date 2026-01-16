# Release Guidelines & Protocols

This document establishes the strict protocols for versioning, preparing, and deploying releases for
the Internara project. Adherence to these guidelines ensures stability, traceability, and accurate
documentation.

---

## 1. Versioning Strategy

Internara follows **Semantic Versioning (SemVer 2.0.0)** with specific stage suffixes.

### Format: `vX.Y.Z-stage`

-   **Major (X):** Breaking changes to the core architecture or API.
-   **Minor (Y):** New features (backward compatible) or significant module additions.
-   **Patch (Z):** Bug fixes, hotfixes, or minor refinements.
-   **Stage:**
    -   `alpha`: In-development, feature incomplete, potential breaking changes.
    -   `beta`: Feature complete, testing phase, stable APIs.
    -   `rc`: Release Candidate.
    -   *(none)*: Production stable.

**Example:** `v0.4.0-alpha`

---

## 2. Pre-Release Checklist (The "Definition of Done")

Before tagging a release or merging to the `main` branch for a release, the following checks **must**
be passed:

### A. Code Quality & Security
- [ ] **Tests:** All Unit and Feature tests must pass (`php artisan test`).
- [ ] **Linting:** Code must be formatted using Pint/Prettier (`npm run format` & `./vendor/bin/pint`).
- [ ] **Static Analysis:** No critical issues found by Larastan (level 5+).
- [ ] **Debug Cleanup:** No `dd()`, `dump()`, `console.log()`, or commented-out "dead code" remains.
- [ ] **Secrets:** No hardcoded secrets or API keys in the codebase.

### B. Documentation
- [ ] **Version Document:** A dedicated file exists in `docs/versions/` (e.g., `v0.4.0-alpha.md`) detailing the scope.
- [ ] **README.md:** Updated with the new version status and any major changes in the project overview.
- [ ] **CHANGELOG.md:** Updated with all changes since the last version using the standard template.
- [ ] **SECURITY.md:** Reviewed and updated if there are changes to security protocols or contacts.
- [ ] **PHPDoc:** All new classes and methods have comprehensive PHPDoc.
- [ ] **Architecture:** If architecture changed, `docs/main/architecture-guide.md` is updated.

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
4.  **GitHub Release:** Create a release on GitHub matching the tag, pasting the content from the **Release Message Template**.

---

## 4. Post-Release Checklist

- [ ] **Update Current Version:** Update the status in `README.md` and `docs/versions/versions-overview.md`.
- [ ] **New Iteration:** Create a new "In Progress" section in `CHANGELOG.md` for the next version.
- [ ] **Cleanup:** Remove any temporary branches used for the release features.

---

## 5. Standard Release Message Template

Use the following template for `CHANGELOG.md` entries and GitHub Release descriptions.

```markdown
## [vX.Y.Z-stage] - YYYY-MM-DD (SERIES-CODE)

### 🚀 Overview
Brief, one-sentence summary of the release's primary goal (e.g., "Introduces the School and Department management modules.").

### ✨ Added
- **Feature Name:** Description of the feature.
- **Module Name:** Description of the new module.
- **Component:** Description of the UI component.

### 🛠 Changed
- Refactored `ClassName` to use `NewPattern`.
- Updated dependency `package-name` to `vX.X`.

### 🐛 Fixed
- Resolved issue where [Problem Description].
- Fixed IDOR vulnerability in [Module Name].

### ⚠️ Breaking Changes
- **Method Renamed:** `OldClass::method()` is now `NewClass::method()`.
- **Config:** `config/file.php` structure has changed.

### 📚 Documentation
- [Full Release Notes & Architecture Guide](docs/versions/vX.Y.Z-stage.md)

---
*Commit: [ShortHash]*
```

### Series Codes Reference
- **INIT:** Project Initialization
- **CORE:** Core Architecture & Shared Services
- **USER:** User Management & RBAC
- **INST:** Institutional (School/Dept)
- **ACAD:** Academic (Internship/Curriculum)
- **IND:** Industry Partner Management

---

**Navigation**

[← Previous: Workflow Developer Guide](modular-monolith-workflow.md) | [Next: Artisan Commands Reference →](artisan-commands-reference.md)
