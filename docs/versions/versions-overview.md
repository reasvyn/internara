# Application Versions Overview

This document provides a **strategic overview of Internara’s application versions**, covering
release lineage, maturity stages, and support policies.

Internara adopts **Analytical Versioning**, where each released version is accompanied by a
post-release narrative documented in accordance with the
**[Release Guidelines](../internal/release-guidelines.md)**.

---

## 1. Version Lifecycle Model

We explicitly separate **Maturity (Stage)**, **Operational Status**, and **Support Policy**.

### Maturity Stages

- **Experimental**: Conceptual validation.
- **Alpha**: Core features incomplete.
- **Beta**: Feature complete, focus on stabilization.
- **Stable**: Production-ready.

### Support Policies

- **Snapshot**: Point-in-time release provided as-is.
- **Full Support**: Active maintenance and improvements.
- **EOL (End of Life)**: No longer maintained.

---

## 2. Version Support Matrix

This table represents the **authoritative status** of each version series.

| Version    | Maturity (Stage) | Support Policy | Status      |
| :--------- | :--------------- | :------------- | :---------- |
| **v0.9.x** | Alpha            | Snapshot       | In Progress |
| **v0.8.x** | Alpha            | Snapshot       | Released    |
| **v0.7.x** | Alpha            | Snapshot       | Released    |
| **v0.6.x** | Alpha            | Snapshot       | Released    |
| **v0.5.x** | Alpha            | EOL            | Deprecated  |
| **v0.4.x** | Alpha            | EOL            | Archived    |

---

## 3. Release Narratives

### Active Development

- **[v0.9.0-alpha](v0.9.0-alpha.md)** — _System Initialization_ Focus: Zero-config setup wizard.

### Latest Released Version

- **[v0.8.0-alpha](v0.8.0-alpha.md)** — _Reporting & Intelligence_ Focus: Competency achievement
  reports and on-site student condition visualization.

---

### Historical Archive

- **[v0.7.0-alpha](v0.7.0-alpha.md)** — _Administrative Automation_ Focus: Automated prerequisite
  checks and structured documentation management.

- **[v0.6.0-alpha](v0.6.0-alpha.md)** — _Assessment & Workspaces_ Introduced role-specific
  workspaces for Instructors, Staff, and Students.

- **[v0.5.0-alpha](v0.5.0-alpha.md)** — _Operational Layer_ Foundational daily journals and
  attendance tracking.

---

## 4. Relationship to Planning Artifacts

- **Analytical Version Notes**: Document **outcome** (located here in `docs/versions/`).
- **Application Blueprints**: Document **intent** (located in `docs/internal/blueprints/`).

> **Source of Truth:** All versions must fulfill the requirements of the
> **[Internara Specs](../internal/internara-specs.md)**.
