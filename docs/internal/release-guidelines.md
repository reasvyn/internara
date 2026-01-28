# Release Guidelines: Baseline Publication Protocols

This document formalizes the **Release Management** protocols for the Internara project, adhering
to **ISO/IEC 12207** (Release Process) and **ISO/IEC 20000** (Service Management). It defines the
systematic procedures for transitioning software from development to a stabilized delivery baseline.

> **Governance Mandate:** No release may be authorized unless it demonstrates 100% compliance with
> the **[Internara Specs](internara-specs.md)** through formal verification and validation.

---

## 1. Release Eligibility Criteria (The Technical Gate)

A version is eligible for **Release** only when it satisfies the following **Quality Gates**:

1.  **Requirement Fulfillment (Validation)**: Every feature specified in the blueprint must be
    verified against the SSoT.
2.  **Configuration Audit**: All artifacts (Code, Docs, Metadata) must be synchronized and correctly
    versioned in `app_info.json`.
3.  **Verification Pass**: 100% success rate across all Unit, Feature, and Architecture test suites.
4.  **Security Certification**: Zero "Critical" or "High" vulnerabilities identified during SAST and
    manual security audits.
5.  **Static Analysis Compliance**: Clean results from automated linting and strict type checking.

---

## 2. Release Execution Procedure

### 2.1 Identity & Metadata Synchronization
Verify that the `app_info.json` reflects the correct SemVer identifier, series code, and intended
support policy.

### 2.2 Configuration Baseline Creation (Tagging)
Create an **Annotated Git Tag** to establish an immutable configuration baseline.
- **Protocol**: `git tag -a vX.Y.Z -m "Release theme and series identification"`
- **Convention**: Tags must match the identifier in `app_info.json` exactly.

### 2.3 Documentation Finalization (Doc-as-Code)
Synthesize the technical execution history into a user-centric release note in `docs/versions/`.
- **Requirement**: Release notes must reflect the **as-built reality** and highlight strategic
  milestones from the blueprint.

---

## 3. Deployment & Baseline Promotion

### 3.1 GitHub Integration
Promote the configuration baseline to GitHub according to the **[GitHub Protocols](github-protocols.md)**.
- **Action**: Utilize `gh release create` to synchronize local release notes with the remote
  repository.
- **Visibility**: Mark non-stable releases as "Pre-release" to prevent accidental production
  consumption.

### 3.2 Post-Release Configuration Audit
- **Artifact Locking**: Once released, the associated release note becomes a permanent part of the
  system record.
- **Blueprint Lifecycle**: Blueprints associated with the release are moved to `archived/` upon
  successful baseline promotion.

---

## 4. Release Integrity & Rollback

### 4.1 Integrity Verification
Immediately following release, the system auditor must verify the integrity of the release package
to ensure no configuration drift occurred during the transition.

### 4.2 Emergency Corrective Action (Hotfix)
If a critical defect is identified post-release, a **Hotfix** baseline must be established targeting
the current release tag, as defined in the configuration management strategy.

---

_By strictly adhering to these release protocols, Internara ensures that every version is a
stabilized, high-quality milestone that maintains the integrity of the modular monolith system._