# Release Publication Protocols: Baseline Management

This document formalizes the **Release Management** protocols for the Internara project,
standardized according to **ISO/IEC 12207** (Software Release Process) and **ISO/IEC 20000**
(Service Management). It defines the systematic procedures for promoting verified software
configurations to an authoritative delivery baseline.

> **Governance Mandate:** No release baseline may be authorized unless it demonstrates 100%
> compliance with the authoritative
> **[System Requirements Specification](system-requirements-specification.md)**.

---

## 1. Release Eligibility Criteria (Technical Exit Gates)

A software configuration is eligible for **Release Promotion** only when it satisfies the following
mandatory quality gates:

1.  **Validation Audit**: Every capability defined in the blueprint must be verified against the
    System Requirements Specification.
2.  **Configuration Integrity**: Artifacts (Code, Docs, Metadata) must be synchronized and
    identifiable in `app_info.json`.
3.  **Verification Pass**: Successful execution of the full verification suite via
    **`composer test`**.
4.  **Static Analysis Compliance**: Successful execution of the automated linting suite via
    **`composer lint`**.
5.  **Security Certification**: Zero critical/high vulnerabilities identified during the security
    audit phase.

---

## 2. Release Promotion Procedure

### 2.1 Identity & Baseline Synchronization

Verify that the `app_info.json` exactly matches the intended configuration baseline (Version,
Series, Policy).

### 2.2 Configuration Baseline Identification (Tagging)

Create an **Annotated Git Tag** to establish an immutable reference to the release baseline.

- **Protocol**: `git tag -a vX.Y.Z -m "Release description and series identifier"`

### 2.3 Documentation Finalization (Doc-as-Code)

Synchronize the analytical release notes in `docs/versions/` to reflect the realized outcome.

- **Requirement**: Documentation must be analytically precise and preserve all technical depth.

---

## 3. Delivery & Baseline Promotion (External)

### 3.1 GitHub Baseline Synchronization

Synchronize the configuration baseline with the remote repository according to the
**[GitHub Protocols](github-protocols.md)**.

- **Action**: Utilize `gh release create` to promote the baseline and its associated metadata.
- **Maturity Identification**: Explicitly mark non-stable baselines as "Pre-release."

### 3.2 Post-Promotion Audit

- **Artifact Locking**: Once promoted, the release baseline and its documentation are immutable.
- **Blueprint Retirement**: Blueprints associated with the realized baseline are moved to the
  `archived/` directory.

---

## 4. Integrity Maintenance & Corrective Action

### 4.1 Integrity Verification

Immediately following baseline promotion, the system auditor must verify the integrity of the
delivery package to ensure zero configuration drift.

### 4.2 Corrective Maintenance (Hotfix)

If a defect is identified in a released baseline, a **Hotfix** configuration must be established
targeting the current baseline tag, as defined in the
**[Software Life Cycle Processes](software-lifecycle-processes.md)**.

---

_By strictly adhering to these release protocols, Internara ensures that every version is a
stabilized, high-quality milestone that maintains the integrity of the modular monolith system._
