# Configuration Management Plan (CMP): Baseline & Change Control

This document defines the **Configuration Management Plan (CMP)** for the Internara system, standardized according to **ISO/IEC/IEEE 12207**.

---

## 1. Configuration Identification

Every release is a **Configuration Baseline** comprising:
- **Source Code**: Tracked via Git.
- **Documentation**: Managed as code in `docs/`.
- **Requirements**: Defined in `software-requirements.md`.
- **Verification Suite**: All tests in `tests/`.

---

## 2. Baseline Promotion (The SSoT)

The `main` branch serves as the authoritative **Single Source of Truth (SSoT)**.
- **Promotion Rule**: A commit can only reach `main` after passing the full CI pipeline and 3S Audit.
- **Versioning**: Versioning follows the **[Versioning Policy](versioning-policy.md)**.

---

## 3. Change Control Protocol

1.  **Proposal**: Changes must be linked to a SyRS requirement or bug report.
2.  **Development**: Performed in feature branches (`feature/`, `fix/`).
3.  **Review**: Mandatory 3S Audit by a Maintainer.
4.  **Integration**: Merged only after manual and automated verification.

---

## 4. Release Packaging

Releases are tagged using Git semver tags. Each tag represents an immutable baseline of the system at that point in time.
