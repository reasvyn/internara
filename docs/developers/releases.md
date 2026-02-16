# Release Publication Protocols: Baseline Management

This document formalizes the **Release Management** protocols for the Internara project,
standardized according to **ISO/IEC 12207** (Software Release Process) and **ISO/IEC 20000**
(Service Management). It defines the systematic procedures for promoting verified software
configurations to an authoritative delivery baseline.

> **Governance Mandate:** No release baseline may be authorized unless it demonstrates 100%
> compliance with the authoritative **[System Requirements Specification](specs.md)**.

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
Support Policy, Readiness). Prior to tag creation, execute the mandatory transition of the version
status to **Released** within the internal records and the Versions Overview table.

- **Readiness Invariant**: Version readiness (Alpha, Beta, RC, Stable) should reflect the actual
  maturity of the baseline. For the `0.x.y` development phase, clean versions are preferred for
  standard milestones.

### 2.2 Series-Based Release Relationship

A single **Blueprint Series** (e.g., `ARC01-ORCH`) may span multiple release cycles. The release
must identify which series it contributes to or fulfills.

- **Ongoing Series**: Release contributes to an `In Progress` blueprint.
- **Series Fulfillment**: Release marks the completion of a blueprint, triggering its transition to
  `Done`.

### 2.3 Configuration Baseline Identification (Tagging)

Create an **Annotated Git Tag** to establish an immutable reference to the release baseline. All
tags must strictly adhere to the **Semantic Versioning (SemVer)** standard.

- **Format**: `vX.Y.Z` or `vX.Y.Z-{pre-release}+{build}` (e.g., `v0.13.0`, `v1.0.0-rc.1`)
- **Pre-release Suffixes**: Use `-alpha`, `-beta`, or `-rc` only when specifically marking a
  stabilization track for a major milestone (e.g., preparing for `v1.0.0`). Standard development
  versions in the `0.x.y` range should remain clean.
- **Protocol**: `git tag -a vX.Y.Z -m "Release description and series identifier"`

### 2.4 Documentation Finalization (Doc-as-Code)

Synchronize the analytical release notes in `../pubs/releases/` to reflect the realized outcome.

- **Requirement**: Documentation must be analytically precise and preserve all technical depth.
- **Metadata Invariant**: Every release note must include **Series Code**, **Support Policy**, and
  **Readiness** at the header of the document.

---

## 3. Delivery & Baseline Promotion (External)

### 3.1 GitHub Baseline Synchronization

Synchronize the configuration baseline with the remote repository according to the
**[Git Protocols](git.md)**.

- **Action**: Utilize `gh release create` to promote the baseline and its associated metadata.
- **Title Convention**: Use the format `vX.Y.Z — {Release Theme}`.
- **Maturity Identification**: Explicitly mark non-stable baselines as "Pre-release."

### 3.2 Post-Promotion Audit

- **Artifact Locking**: Once promoted, the release baseline and its documentation are immutable.
- **Transparency**: Update the **Versions Overview** (`README.md` in `pubs/releases`) to reflect the
  newly released configuration.
- **Visibility Limit**: To maintain minimalist clarity, the Versions Overview table and detailed
  notes must be limited to the **6 most recent versions**. Older records remain available in the
  filesystem but are removed from the overview document.

---

## 4. Integrity Maintenance & Corrective Action

### 4.1 Integrity Verification

Immediately following baseline promotion, the system auditor must verify the integrity of the
delivery package to ensure zero configuration drift.

### 4.2 Corrective Maintenance (Hotfix)

If a defect is identified in a released baseline, a **Hotfix** configuration must be established
targeting the current baseline tag, as defined in the **[Software Lifecycle](lifecycle.md)**.

---

## 5. Support Cycle Transitions

When a new version reaches **Stable** readiness, the support policy for older versions must be
formally transitioned according to the following rules:

1.  **Promotion to Maintenance**: The previous stable version transitions to `Maintenance`.
2.  **Promotion to EOL**: Versions superseded by multiple major/minor baselines or containing
    unfixable architectural debt transition to `End of Life (EOL)`.
3.  **Experimental Removal**: Beta/Alpha releases associated with a completed series transition to
    `End of Life (EOL)`.

---

## 6. Release Notes Authoring Standards

To ensure transparency and accessibility for all stakeholders, Release Notes must adhere to the
following semantic standards:

- **Language**: All Release Notes must be authored in **English**.
- **Tone & Accessibility**: Content must be easily understandable by non-technical users
  (laypeople). Avoid excessive jargon unless necessary for technical context.
- **Mandatory Structure**:
    - **Overview**: A brief summary of the release's purpose and strategic impact.
    - **Key Features**: High-level descriptions of significant new capabilities.
    - **What's Changed? (Changelog)**: A concise list of functional improvements and fixes adhering
      to the **Keep a Changelog** convention (Added, Changed, Deprecated, Removed, Fixed, Security).
- **Inclusion Policy**: Only significant changes, features, and fixes should be recorded. Minor
  technical maintenance or trivial updates (e.g., "typo fix in internal comment") should be omitted
  to maintain signal quality.

---

## 6. Release Metadata Definitions

To ensure technical consistency, all releases must be classified according to the following
authoritative levels:

### 6.1 Readiness

- **Alpha**: Early testing phase. New features are being added rapidly.
- **Beta**: Feature complete. Focusing on fixing bugs and polishing the experience.
- **Release Candidate (RC)**: Final validation phase. Stable enough for evaluation, pending final
  sign-off.
- **Stable**: Production-ready. Verified baseline ready for everyday use in your institution.

### 6.2 Support Policy

- **Experimental**: Active development. No stability guarantees; not recommended for production.
- **Stable/LTS**: Long-term support. Guaranteed reliability and critical updates for a defined
  period.
- **Maintenance**: Actively maintained with regular improvements and security fixes.
- **Security Only**: Critical fixes only. No new features or general bug fixes will be provided.
- **End of Life (EOL)**: No longer supported. Institutions must upgrade to a newer version
  immediately.

---

## 7. Authoritative SemVer & Pre-release Syntax

To prevent versioning ambiguity and ensure systemic compatibility, Internara enforces a **Strict
SemVer Compliance** policy. Every version identifier MUST adhere to the following mathematical
structure.

### 7.1 The Version Anatomy

**Format**: `v<Major>.<Minor>.<Patch>[-<PreRelease>[.<Iteration>]][+<Build>]`

- **Major (`X`)**: Incremented for incompatible API changes.
- **Minor (`Y`)**: Incremented for functional additions.
- **Patch (`Z`)**: Incremented for bug fixes.

### 7.2 Pre-release Labels (Optional Suffixes)

Pre-release labels (e.g., `-alpha`, `-beta`) are optional and reserved for high-stakes release
tracks. For standard `v0.x.y` development, clean versioning is the baseline.

| Label       | Meaning                                            | Syntax Example                   |
| :---------- | :------------------------------------------------- | :------------------------------- |
| **`alpha`** | Initial construction; experimental and unstable.   | `v0.13.0`, `v0.13.0.1`           |
| **`beta`**  | Feature complete; focusing on bugs and stability.  | `v0.13.0-beta`, `v0.13.0-beta.2` |
| **`rc`**    | Release Candidate; final validation before stable. | `v0.13.0-rc`, `v0.13.0-rc.1`     |

### 7.3 Firm Invariants

1.  **Lower-Case Requirement**: All labels (`alpha`, `beta`, `rc`) MUST be lowercase.
2.  **Hyphen Requirement**: There MUST be exactly one hyphen between the patch version and the
    label.
3.  **Iteration Dot**: If an iteration is needed (e.g., multiple betas), it MUST use a dot separator
    (e.g., `-beta.1`, NOT `-beta1`).
4.  **Zero-Padding**: Do NOT use leading zeros for version segments (e.g., `v0.1.0` is correct,
    `v0.01.0` is FORBIDDEN).
5.  **Tagging Parity**: The Git tag MUST exactly match the `version` field in `app_info.json`.

---

_By strictly adhering to these release protocols, Internara ensures that every version is a
stabilized, high-quality milestone that maintains the integrity of the modular monolith system._
