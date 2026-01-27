# Release Guidelines: Executing the Product Lifecycle

This document defines the protocols for performing a release and synchronized deployment artifacts.
For definitions of versioning standards and support policies, see the
**[Version Management Guide](version-management.md)**.

> **Governance Mandate:** A version cannot be released if it does not fulfill the Spec Validation
> criteria defined in the **[Internara Specs](internara-specs.md)**.

---

## 1. Release Eligibility Criteria (The Exit Gate)

A version may be marked as `Released` only when the following conditions are met:

1. **Spec Compliance**: Features function exactly as described in `internara-specs.md`.
2. **Scope Closure**: The intended milestone scope is internally complete.
3. **Artifact Consistency**: Code, documentation, and metadata are synchronized.
4. **Testing Pass**: 100% pass rate on `composer test`.
5. **Linting Pass**: Clean `composer lint`.

---

## 2. Release Procedure

### 2.1 Identity Verification

Ensure `app_info.json` reflects the target version, series code, and intended support policy.

### 2.2 Release Note Creation

Produce user-centric release notes in `docs/versions/vX.Y.Z.md`. This must reflect the

**as-built reality**.

### 2.3 Tagging

Create a Git tag matching the version identifier (e.g., `git tag v0.9.0-alpha`).

---

## 3. Post-Release Management

### 3.1 GitHub Operations

Perform operations according to **[GitHub Protocols](github-protocols.md)**.

- Update release notes on GitHub using `gh release create`.
- Ensure the `Latest` label is moved to the new stable/active release.

### 3.2 Blueprint Archival

Marking a version as **Released** does NOT automatically archive its blueprint. Blueprints remain in
the active directory until explicit archival permission is granted.

---

_By following these protocols, we ensure that every release is a high-quality, traceable milestone
in the Internara lifecycle._
