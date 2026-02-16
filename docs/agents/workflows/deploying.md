# Engineering Protocol: Library Distribution & Deployment

This document establishes the **Distribution Protocol** for Internara as a Library/Open-Source
project, focusing on package registry integrity and artifact availability.

---

## ⚖️ Core Mandates & Prohibitions (The Distribution Laws)

### 1. Library-Centric Invariants

- **Registry Alignment**: All distribution metadata MUST align with the requirements of **Packagist
  (Composer)** and **NPM**.
- **Lockfile Integrity**: Distribution artifacts MUST have verified and stable `composer.lock` and
  `package-lock.json` files.
- **Tagging Protocol**: Distribution is achieved via annotated Git tags (`git tag -a`).

### 2. Exclusion Laws

- **Artifact Purity**: Ensure that developer-only directories (`.temp/`, `tests/`, `node_modules/`
  in submodules) are correctly handled via `.gitattributes` and `composer.json` excludes.

---

## 🎯 Scope & Authorized Actions

### 1. Protocol Scope

- **Registry Metadata Management**: Synchronizing `composer.json` and `module.json` for external
  consumption.
- **Asset Finalization**: Running build scripts to ensure CSS/JS artifacts are distribution-ready.
- **GitHub Release Sync**: Preparing the meta-description for the GitHub Release interface.

### 2. Authorized Actions

- **Metadata Patching**: Updating registry-specific fields.
- **Tag Simulation**: Providing the exact Git commands for the user to execute.

---

## Phase 0: Security & Vulnerability Audit

- **Dependency Scan**: Run security audits on all third-party packages.
- **Lockfile Verification**: Ensure parity between `composer.json` and `composer.lock`.

## Phase 1: Distribution Metadata Synchronization

- **Module Sync**: Ensure each domain module's `module.json` has the correct version and
  description.
- **Composer Merge Audit**: Verify the `wikimedia/composer-merge-plugin` configuration for modular
  autoloading.

## Phase 2: Asset Production & Build

- **Final Build**: Execute `npm run build` to finalize TALL stack assets.
- **Asset Audit**: Verify that `public/build/` contains the necessary manifests.

## Phase 3: GitHub Release & Tagging Preparation

- **Draft Construction**: Prepare the **GitHub Release** description based on the Publication notes.
- **Tag Command Generation**: Suggest the exact command (e.g.,
  `git tag -a v0.13.0 -m "ARC01-FIX-01: System Stabilization"`).
- **Release Execution**: Utilize `gh release create` to automate artifact attachment and tagging
  upon user authorization.

## Phase 4: Installation Documentation Audit

- **Wiki Verification**: Ensure `docs/users/installation.md` is updated for the new distribution
  baseline.
- **Requirement Verification**: Verify that the minimum PHP and Laravel versions are correctly
  documented.

## Phase 5: Final Keypoints

- Report on the readiness of the package for public registry distribution.

---

_Distribution is the bridge between the repository and the community._
