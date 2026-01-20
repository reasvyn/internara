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

## 2. Version Lifecycle & Policies

To manage expectations, we distinguish between the **maturity stage** of a release and its **support
policy**.

### 2.1 Development Stages (Maturity)

These stages define the completeness and stability of the features.

1.  **Planned**: Conceptual phase. Scope is defined, but implementation hasn't started.
2.  **In Progress**: Active development. Code is being written.
3.  **Alpha**: Feature exploration. The system is functional but APIs and features may change
    drastically.
4.  **Beta**: Feature complete (Feature Freeze). Focus shifts entirely to bug fixing and
    stabilization.
5.  **Release Candidate (RC)**: A potential final product. No code changes allowed except for
    critical blockers.
6.  **Stable**: Production-ready release.

### 2.2 Support Policies (Maintenance)

These policies define the guarantee of updates and patches for a specific release.

-   **Snapshot Policy**:
    -   **Definition**: A "point-in-time" release provided as-is.
    -   **Guarantee**: **Zero**. No hotfixes, no security patches.
    -   **Strategy**: **Fix-Forward**. Bugs found in this version will only be addressed in the
        *next* release.
    -   *Typical Usage*: Alpha releases, Nightly builds, or experimental branches.

-   **Standard Support**:
    -   **Definition**: A supported release meant for usage.
    -   **Guarantee**: Receives bug fixes and security patches until the next minor/major version.
    -   *Typical Usage*: Beta (critical bugs only), Stable releases.

-   **LTS (Long Term Support)**:
    -   **Definition**: An enterprise-grade release.
    -   **Guarantee**: Receives critical security patches for an extended period, regardless of newer
        versions.

-   **EOL (End of Life)**:
    -   **Definition**: Obsolete.
    -   **Guarantee**: None. The version is no longer maintained.

---

## 3. Artifact: `app_info.json`

The `app_info.json` file at the project root is the **Machine-Readable Identity** of the
application. It must be updated manually when a version milestone is reached.

**Required Fields:**

- `version`: The SemVer string.
- `series_code`: The unique identifier for the current development series (e.g., `ARC01-FEAT-01`).
- `status`: The release state (`Active Support`, `Released`, `EOL`).

---

## 4. Changelog Management (`CHANGELOG.md`)

Our changelog is more than a list of commits; it is a human-readable history of progress.

### 4.1 Structure

Follow the **[Keep a Changelog](https://keepachangelog.com/)** standard:

- `[Unreleased]`: For changes not yet part of a tagged version.
- `Added`: For new features.
- `Changed`: For changes in existing functionality.
- `Deprecated`: For soon-to-be-removed features.
- `Removed`: For now-removed features.
- `Fixed`: For bug fixes.
- `Security`: In case of vulnerabilities.

### 4.2 Tone

Keep entries technical but accessible. Focus on the **Impact** of the change.

---

## 5. Analytical Version Notes (`docs/versions/`)

For every version (unreleased or released), we produce an **Analytical Version Note** (Deep 
Analytical Narrative). This document resides directly in `docs/versions/` and serves as the 
"Technical Bible" for that specific milestone. Note that this is distinct from **Engineering 
Plans** which reside in `docs/internal/plans/`.

**Status Policy**: Individual version notes **must not** contain dynamic lifecycle status or 
support policy information. This data is managed exclusively in the 
**[Versions Overview](../versions/versions-overview.md)** to ensure a Single Source of Truth.

**Each narrative must include:**

1.  **Metadata**: **Series Code** and date.
2.  **Goals & Philosophy**: The strategic "Why" behind the milestone.
3.  **Production Keystones**: Detailed implementation deep-dives for major features.
4.  **Verification Analysis**: Results of tests, security audits, and linting.
5.  **Roadmap Strategy**: High-level direction for the next version.

---

## 6. The Release Checklist

Before marking a version as `Released`:

1.  **Iterative Sync**: Ensure all code, tests, and documentation are synchronized.
2.  **Core Artifacts**: Update `app_info.json` and the root `README.md`.
3.  **Changelog**: Move entries from `[Unreleased]` to the version header.
4.  **Verify**: Run `php artisan app:info` to confirm identity.
5.  **Tag**: Create a Git tag matching the version string.

---

_Consistent release management allows our team and users to understand the evolution of Internara
with minimal friction. Documentation is the bridge between code and product._
