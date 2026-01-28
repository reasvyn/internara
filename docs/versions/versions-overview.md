# Application Version Baselines: Strategic Overview

This document provides a **strategic registry of Internara configuration baselines**, detailing the
release lineage, maturity stages, and support policies for every developmental milestone.

Internara utilizes **Analytical Versioning**, where every promoted baseline is accompanied by an
as-built release record documented according to the
**[Release Guidelines](../internal/release-guidelines.md)**.

---

## 1. Baseline Lifecycle Model

We distinguish between **Maturity**, **Support Policy**, and **Operational Status** to ensure
precise configuration identification.

### Maturity Stages (Baseline Stability)
- **Experimental** | **Alpha** | **Beta** | **Release Candidate (RC)** | **Stable**

### Support Policies (Maintenance Commitments)
- **Active Support** (Bug + Security) | **Bugfix Only** | **Security Only** | **Snapshot** | **EOL**

### Operational Status (Baseline Reality)
- **Planned** | **In Progress** | **Preview** | **Released** | **Deprecated** | **Archived**

---

## 2. Configuration Baseline Matrix

The following table represents the **authoritative configuration status** of each version series.

| Baseline   | Maturity (Stage) | Support Policy | Status      |
| :--------- | :--------------- | :------------- | :---------- |
| **v0.9.x** | Alpha            | Snapshot       | In Progress |
| **v0.8.x** | Alpha            | Snapshot       | Released    |
| **v0.7.x** | Alpha            | Snapshot       | Released    |
| **v0.6.x** | Alpha            | Snapshot       | Released    |

---

## 3. Realized Release Baselines (Analytical Records)

### Active Development
- **[v0.9.0](v0.9.0.md)** — _System Initialization_
  - **Focus**: Automated CLI installation and Web Setup Wizard.

### Latest Stable Baseline
- **[v0.8.0](v0.8.0.md)** — _Reporting & Intelligence_
  - **Focus**: Competency achievement reports and on-site student monitoring.

---

### Historical Baselines
- **[v0.7.0](v0.7.0.md)** — _Administrative Automation_
- **[v0.6.0](v0.6.0.md)** — _Assessment & Workspaces_
- **[Archived Baseline Records](archived/table-of-contents.md)** — Historical artifacts for legacy
  version series.

---

## 4. Documentation Traceability
- **Release Baselines**: Document realized **outcomes** (located in `docs/versions/`).
- **Application Blueprints**: Document design **intent** (located in `docs/internal/blueprints/`).

> **SSoT Alignment:** All configuration baselines must demonstrate fulfillment of the
> requirements defined in the authoritative **[Internara Specs](../internal/internara-specs.md)**.