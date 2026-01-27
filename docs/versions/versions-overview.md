# Application Versions Overview

This document provides a **strategic overview of Internara’s application versions**, covering
release lineage, maturity stages, and support policies.

Internara adopts **Analytical Versioning**, where each released version is accompanied by a
post-release release note documented in accordance with the
**[Release Guidelines](../internal/release-guidelines.md)**.

---

## 1. Version Lifecycle Model

We explicitly separate **Maturity (Stage)**, **Operational Status**, and **Support Policy** to avoid
semantic overlap.

### Maturity Stages (Quality & Completeness)

- **Experimental**: Architectural or conceptual validation.
- **Alpha**: Core features incomplete; breaking changes expected.
- **Beta**: Feature complete; focus on stabilization.
- **Release Candidate (RC)**: Potential final release; only critical fixes allowed.
- **Stable**: Production-ready with defined guarantees.

### Support Policies (Maintenance Contract)

- **Full Support**: Active maintenance and improvements (Bugs + Security).
- **Bugfix Only**: Maintenance without feature additions.
- **Security Only**: Critical vulnerability patches only.
- **Snapshot**: Point-in-time release provided as-is.
- **EOL (End of Life)**: No longer maintained.

### Operational Status (Current Reality)

- **Planned**: Identified in Specs but not implemented.
- **In Progress**: Under active construction.
- **Preview**: Accessible for demonstration or evaluation.
- **Released**: Publicly tagged and distributed.
- **Deprecated**: Accessible but no longer recommended.
- **Archived**: Closed and historically preserved.

---

## 2. Version Support Matrix

This table represents the **authoritative status** of each version series.

| Version    | Maturity (Stage) | Support Policy | Status      |
| :--------- | :--------------- | :------------- | :---------- |
| **v0.9.x** | Alpha            | Snapshot       | In Progress |
| **v0.8.x** | Alpha            | Snapshot       | Released    |
| **v0.7.x** | Alpha            | Snapshot       | Released    |
| **v0.6.x** | Alpha            | Snapshot       | Released    |

---

## 3. Release Notes

### Active Development

- **[v0.9.0](v0.9.0.md)** — _System Initialization_ Focus: Zero-config setup wizard.

### Latest Released Version

- **[v0.8.0](v0.8.0.md)** — _Reporting & Intelligence_ Focus: Competency achievement reports and
  on-site student condition visualization.

---

### Historical Archive

- **[v0.7.0](v0.7.0.md)** — _Administrative Automation_ Focus: Automated prerequisite checks and
  structured documentation management.

- **[v0.6.0](v0.6.0.md)** — _Assessment & Workspaces_ Introduced role-specific workspaces for
  Instructors, Staff, and Students.

- **[Archived Release Notes](archived/table-of-contents.md)** — Historical artifacts for legacy
  version series.

---
## 4. Relationship to Planning Artifacts

- **Analytical Release Notes**: Document **outcome** (located here in `docs/versions/`).
- **Application Blueprints**: Document **intent** (located in `docs/internal/blueprints/`).

> **Source of Truth:** All versions must fulfill the requirements of the
> **[Internara Specs](../internal/internara-specs.md)**.
