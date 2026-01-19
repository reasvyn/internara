# Release Guidelines: Navigating the Product Lifecycle

This document defines the protocols for versioning, changelog maintenance, and the final release of
software within the Internara project. These standards ensure that our development history is
descriptive, analytical, and professional.

---

## 1. Versioning Standard

We strictly adhere to **Semantic Versioning (SemVer)**: `MAJOR.MINOR.PATCH`.

- **MAJOR**: Incompatible API changes or significant architectural shifts.
- **MINOR**: New functionality in a backward-compatible manner.
- **PATCH**: Backward-compatible bug fixes or security patches.
- **Pre-releases**: During Alpha/Beta, we append a suffix (e.g., `v0.6.0-alpha`).

---

## 2. Artifact: `app_info.json`

The `app_info.json` file at the project root is the **Machine-Readable Identity** of the
application. It must be updated manually when a version milestone is reached.

**Required Fields:**

- `version`: The SemVer string.
- `series_code`: The unique identifier for the current development series (e.g., `ARC01-FEAT-01`).
- `status`: The release state (`Stable`, `Beta`, `Released`).

---

## 3. Changelog Management (`CHANGELOG.md`)

Our changelog is more than a list of commits; it is a human-readable history of progress.

### 3.1 Structure

Follow the **[Keep a Changelog](https://keepachangelog.com/)** standard:

- `[Unreleased]`: For changes not yet part of a tagged version.
- `Added`: For new features.
- `Changed`: For changes in existing functionality.
- `Deprecated`: For soon-to-be-removed features.
- `Removed`: For now-removed features.
- `Fixed`: For bug fixes.
- `Security`: In case of vulnerabilities.

### 3.2 Tone

Keep entries technical but accessible. Focus on the **Impact** of the change.

---

## 4. Analytical Version Notes (`docs/versions/{subdir}/`)

For every version (unreleased, archived, or released), we produce an **Analytical Version Note**
(Deep Analytical Narrative). This document resides in `docs/versions/releases/` or
`docs/versions/unreleases/` and serves as the "Technical Bible" for that specific state. Note that
this is distinct from **Engineering Plans** which reside in `docs/internal/plans/`.

**Each narrative must include:**

1.  **Version Details**: Metadata and status.
2.  **Goals & Philosophy**: The strategic "Why" behind the release.
3.  **Production Keystones**: Detailed implementation deep-dives for major features.
4.  **Verification Analysis**: Results of tests, security audits, and linting.
5.  **Roadmap Strategy**: High-level direction for the next version.

---

## 5. The Release Checklist

Before marking a version as `Released`:

1.  **Iterative Sync**: Ensure all code, tests, and documentation are synchronized.
2.  **Core Artifacts**: Update `app_info.json` and the root `README.md`.
3.  **Changelog**: Move entries from `[Unreleased]` to the version header.
4.  **Verify**: Run `php artisan app:info` to confirm identity.
5.  **Tag**: Create a Git tag matching the version string.

---

_Consistent release management allows our team and users to understand the evolution of Internara
with minimal friction. Documentation is the bridge between code and product._
