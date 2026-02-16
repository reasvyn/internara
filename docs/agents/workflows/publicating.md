# Engineering Protocol: Publication & Publication Record Synchronization

This document establishes the authoritative **Publication Record Protocol** for Internara, focused
on providing the authoritative documentation and metadata for new software baselines.

---

## ⚖️ Core Mandates & Prohibitions (The Publication Laws)

### 1. Operational Ethics

- **Publication Only Invariant**: The Agent provides the publication artifacts and metadata; it
  **DOES NOT** execute the actual release, tagging, or deployment.
- **No Batch Actions**: Performing bulk automated modifications across multiple publication records
  or metadata files in a single pass is **STRICTLY PROHIBITED**. Versioning and record updates MUST
  be atomic and verified.
- **Authoritative Versioning**: `app_info.json` is the single source of truth for the system
  version.

### 2. Communication Standards

- **Friendly & Transparent**: Publication documents (Pubs) MUST use language accessible to
  non-technical stakeholders while maintaining technical accuracy.
- **Categorization**: Changes must be categorized into: ✨ Features, 🐞 Bug Fixes, ⚡ Improvements,
  and 🔒 Security.

---

## 🎯 Scope & Authorized Actions

### 1. Protocol Scope

- **Release Finalization**: Preparing the authoritative records for a software baseline (Release
  Notes, Pubs).
- **Metadata Bumping**: Updating version and series codes in `app_info.json`, `module.json`, and
  `composer.json` for a specific version release.
- **Distinction**: This protocol focuses on **version baseline records**, whereas
  **[Documenting](./documenting.md)** focuses on **continuous engineering synchronization**.

### 2. Authorized Actions

- **Version Bumping**: Incrementing SemVer based on the impact of changes.
- **Pubs Creation**: Creating new Markdown files in the `pubs/` directory.

---

## Phase 0: Baseline Verification & Sync

- **Test Integrity**: Ensure a 100% pass rate in `composer test`.
- **Sync The Sweep**: Run `git fetch --all --tags --prune` to ensure the local repo is aligned with
  the remote.
- **Documentation Audit**: Verify that all code changes have corresponding technical documentation.

## Phase 1: Metadata & Version Synchronization

- **SemVer Application**: Determine if the release is Major, Minor, or Patch.
- **Strict Syntax Verification**: Ensure the version string strictly adheres to the anatomy defined
  in `docs/developers/releases.md` (e.g., lowercase `-alpha`, `-beta`, `-rc` with dot iterations).
- **Release Branch**: Switch to a release branch: `git checkout -b release/vX.Y.Z`.
- **Metadata Patch**: Update `app_info.json`. Synchronize version fields in `package.json` and
  module descriptors.

## Phase 2: Publication Artifact Generation (The "Pubs")

- **Release Note Construction**: Create/Update the release file (e.g.,
  `docs/pubs/releases/v0.13.0.md`).
- **GitHub Release Draft**: Construct the authoritative description for the **GitHub Release**
  interface.
- **Feature Highlighting**: Summarize key achievements and how they fulfill SyRS requirements.

## Phase 3: Project-Level Realignment

- **README Synchronization**: Update version badges and "Current Status" table in the root
  `README.md`.
- **Legal & Security Audit**: Ensure `LICENSE` and `SECURITY.md` reflect the current state.

## Phase 4: SSoT & Milestone Closure

- **Technical Index Update**: Ensure `docs/developers/README.md` reflects new modules or guides.
- **Milestone Finalization**: Verify all Issues linked to the **GitHub Milestone** are closed.
- **Wiki Update**: Update the Module Catalog in `docs/users/modules.md`.

## Phase 5: Final Review & Release Handover

- **Baseline Summary**: Provide a summary of all created artifacts and metadata.
- **Pull Request**: Create a PR from `release/*` to `main` via `gh pr create`.
- **Tagging Protocol**: Hand over the final baseline for user-led execution via **Annotated Tags**
  (e.g., `git tag -a v0.13.0 -m "Release description"`).
- **Cleanup**: Once the release is merged and tagged, delete the release branch:
  `git branch -d release/...`.

---

_Publicating is the formal transition of a baseline from construction to operation._
