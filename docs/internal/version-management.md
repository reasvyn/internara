# Version Management: Configuration Baseline Standards

This document formalizes the **Version Management** standards for the Internara project, adhering to
**ISO/IEC 12207** (Configuration Management) and **ISO 10007** (Quality Management). It defines the
protocols for establishing **Configuration Baselines**, lifecycle classification, and support
governance to ensure systemic traceability and operational clarity.

> **Governance Mandate:** All versioning decisions and baseline promotions must be traceable to the
> milestones defined in the authoritative **[Internara Specs](internara-specs.md)**.

---

## 1. Versioning Standard: SemVer (ISO 10007 Alignment)

Internara utilizes **Semantic Versioning (SemVer)** to communicate the nature of configuration
changes within the system.

- **MAJOR**: Structural architectural shifts or incompatible API changes.
- **MINOR**: Functional capability additions (mapped to Spec Milestones).
- **PATCH**: Corrective maintenance and security patches.
- **Pre-release Identifiers**: Used to identify non-stable readiness stages (e.g., `v0.9.0-alpha`).

---

## 2. The 3-Axis Lifecycle Matrix

To ensure precise configuration identification, Internara separates **Readiness**, **Maintenance**,
and **Current Status**.

### 2.1 Readiness Levels (Baseline Stability)

- **Experimental**: Exploratory configuration.
- **Alpha**: Internal construction; baseline is unstable.
- **Beta**: Feature-complete; focusing on stabilization.
- **Release Candidate (RC)**: Final verification baseline.
- **Stable**: Certified production baseline.

### 2.2 Maintenance (Support Commitments)

- **Active Support**: Full maintenance and evolutionary updates.
- **Security Only**: Critical vulnerability patches exclusively.
- **Snapshot**: Point-in-time baseline provided without maintenance guarantees.
- **EOL (End of Life)**: No support provided; baseline is deprecated.

### 2.3 Current Status (Baseline State)

- **Planned** | **In Progress** | **Released** | **Deprecated** | **Archived**

---

## 3. Configuration Identification Artifacts

### 3.1 `app_info.json` (Machine-Readable Identity)

The authoritative technical baseline identifier.

- **Requirements**: Must include `version`, `series_code`, and `maintenance`.

### 3.2 Analytical Release Notes (The Engineering Record)

Located in `docs/versions/`, these document the **Realized Outcome** of a configuration baseline.

- **Requirement**: Must reflect the as-built reality and verify the fulfillment of spec milestones.
- **Metadata Invariant**: Must include **Series Code**, **Maintenance**, and **Status** at the
  top of the document.
- **SSoT Sync**: Blueprints document **Intent**; Release Notes document **Outcome**.

---

## 4. Baseline Promotion & Archival Protocols

### 4.1 Promotion to 'Released' (Baseline Stabilization)

1.  **Identity Audit**: Synchronize `app_info.json` with the target version and policy.
2.  **Narrative Finalization**: Set internal status to `Released` in the release note.
3.  **Registry Update**: Register the baseline in `docs/versions/versions-overview.md`.
4.  **Baseline Tagging**: Create an annotated Git tag according to
    **[GitHub Protocols](github-protocols.md)**.

### 4.2 Transition to 'Archived' (Baseline Retirement)

1.  **Metadata Update**: Reflect `Archived` status and `EOL` policy in all artifacts.
2.  **Relocation**: Move release notes to `docs/versions/archived/` and blueprints to
    `docs/internal/blueprints/archived/`.
3.  **Traceability Preservation**: Ensure Table of Contents (ToC) updates maintain links to the
    archived technical records.

---

_By adhering to these version management standards, Internara ensures that every system baseline is
disciplined, traceable, and compliant with international configuration management frameworks._