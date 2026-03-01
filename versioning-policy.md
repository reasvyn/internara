# Versioning Policy: Internara Project

Internara adheres to **Semantic Versioning 2.0.0 (SemVer)** and follows a structured release lifecycle to ensure architectural stability and predictable evolution.

---

## 1. Versioning Structure (X.Y.Z)

- **MAJOR (X)**: Introduced for incompatible architectural changes (e.g., breaking module contracts, fundamental tech stack upgrades).
- **MINOR (Y)**: Introduced for new functional capabilities (e.g., a new domain module, significant new SyRS requirement fulfilled) in a backward-compatible manner.
- **PATCH (Z)**: Introduced for backward-compatible bug fixes, security patches, and minor internal performance optimizations.

---

## 2. Release Lifecycle

Internara defines the following stability tiers for releases:

- **Alpha/Beta**: Experimental releases for early feedback. Not recommended for production environments.
- **RC (Release Candidate)**: Feature-complete versions undergoing final verification (3S Audit).
- **Stable**: Officially supported versions for production use.

---

## 3. Backward Compatibility & Deprecation

To maintain systemic integrity:

- **Deprecation Warning**: Breaking changes must be preceded by a deprecation warning in at least one MINOR version.
- **Documentation Parity**: Every MINOR and MAJOR release must be accompanied by updated documentation and a comprehensive `CHANGELOG.md`.
- **Breaking Changes**: Are only permitted in MAJOR versions.

---

## 4. Maintenance & Security Support

- **LTS (Long Term Support)**: Certain MAJOR versions may be designated as LTS, providing extended security and bug fix support.
- **Support Window**: Security support is typically provided for the current and previous MINOR versions within the latest MAJOR release.

---

## 5. Artifact Delivery

Every release is promoted as a **Configuration Baseline**, which includes:
- Source code.
- Verification tests (Pest).
- Aligned documentation (docs/).
- Updated metadata (app_info.json).
