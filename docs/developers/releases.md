# Release Publication Protocols: Baseline Management

This document formalizes the **Release Management** protocols for the Internara project,
standardized according to **ISO/IEC 12207** (Software Release Process) and **ISO/IEC 20000**
(Service Management). It defines the systematic procedures for promoting verified software
configurations to an authoritative delivery baseline.

> **Governance Mandate:** No release baseline may be authorized unless it demonstrates 100%
> compliance with the authoritative
> **[System Requirements Specification](specs.md)**.

---

## 1. Release Eligibility Criteria (Technical Exit Gates)

A software configuration is eligible for **Release Promotion** only when it satisfies the following
mandatory quality gates:

1.  **Validation Audit**: Every capability defined in the blueprint must be verified against the
    System Requirements Specification.
2.  **Configuration Integrity**: All system artifacts (Source Code, Engineering Records, Stakeholder
    Documentation) must be fully synchronized. The `README.md` must accurately reflect the target
    configuration baseline, project status, and version support matrix.
3.  **Status Transition Invariant**: The version status must be formally updated to **Released**
    within the strategic registries and analytical records prior to final baseline promotion.
4.  **Verification Pass**: Successful execution of the full verification suite via
    **`composer test`**.
5.  **Static Analysis Compliance**: Successful execution of the automated linting suite via
    **`composer lint`**.
6.  **Security Certification**: Zero critical/high vulnerabilities identified during the security
    audit phase.

---

## 2. Release Promotion Procedure

### 2.1 Identity & Baseline Synchronization

Verify that the `app_info.json` exactly matches the intended configuration baseline (Series Code,
Support Policy, Status). Execute the mandatory transition of the version status to **Released**
within the internal records and the strategic baseline overview prior to tag creation.

### 2.2 Configuration Baseline Identification (Tagging)

Create an **Annotated Git Tag** to establish an immutable reference to the release baseline.

- **Protocol**: `git tag -a vX.Y.Z -m "Release description and series identifier"`

### 2.3 Documentation Finalization (Doc-as-Code)

Synchronize the analytical release notes in `versions/` to reflect the realized outcome.

- **Requirement**: Documentation must be analytically precise and preserve all technical depth.
- **Metadata Invariant**: Every release note must include **Series Code**, **Support Policy**, and
  **Status** at the header of the document.

---

## 3. Delivery & Baseline Promotion (External)

### 3.1 GitHub Baseline Synchronization

Synchronize the configuration baseline with the remote repository according to the
**[Git Protocols](git.md)**.

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
**[Software Lifecycle](lifecycle.md)**.

---

## 5. Release Notes Authoring Standards

To ensure transparency and accessibility for all stakeholders, Release Notes must adhere to the following semantic standards:

- **Language**: All Release Notes must be authored in **English**.
- **Tone & Accessibility**: Content must be easily understandable by non-technical users (laypeople). Avoid excessive jargon unless necessary for technical context.
- **Mandatory Structure**:
    - **Overview**: A brief summary of the release's purpose and strategic impact.
    - **Key Features**: High-level descriptions of significant new capabilities.
    - **What's Changed? (Changelog)**: A concise list of functional improvements and fixes.
- **Inclusion Policy**: Only significant changes, features, and fixes should be recorded. Minor technical maintenance or trivial updates (e.g., "typo fix in internal comment") should be omitted to maintain signal quality.

---

_By strictly adhering to these release protocols, Internara ensures that every version is a
stabilized, high-quality milestone that maintains the integrity of the modular monolith system._
