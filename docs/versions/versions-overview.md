# Application Versions Overview

This document provides a **strategic overview of Internara’s application versions**, covering
release lineage, lifecycle status, and support posture.

Internara adopts **Analytical Versioning**, where each released version is accompanied by a
post-release narrative that documents what was delivered and why.

---

## 1. Version Lifecycle Model

Each version series progresses through clearly defined **lifecycle stages**.

### Lifecycle Stages

* **Alpha**
  Early-stage product validation. Features and designs may evolve.

* **Beta** *(future)*
  Feature-complete with stabilization focus.

* **Stable** *(future)*
  Production-grade with defined support commitments.

---

### Support Policy Definitions

* **Snapshot**
  No ongoing maintenance. Code is preserved as-is for reference.

* **Supported** *(future)*
  Receives bug fixes and security updates.

* **EOL (End of Life)**
  No maintenance, fixes, or security updates.

---

## 2. Version Support Matrix

This table represents the **authoritative status** of each version series.

| Version    | Stage | Support Policy | Security Updates | Bug Fixes | Lifecycle Status   |
| :--------- | :---- | :------------- | :--------------- | :-------- | :----------------- |
| **v0.7.x** | Alpha | Snapshot       | ❌                | ❌         | Active Development |
| **v0.6.x** | Alpha | Snapshot       | ❌                | ❌         | Released           |
| **v0.5.x** | Alpha | EOL            | ❌                | ❌         | EOL (End of Life)  |
| **v0.4.x** | Alpha | EOL            | ❌                | ❌         | Archived           |

---

## 3. Release Narratives

Each released version has a dedicated **Version Note** documenting the milestones.

### In Progress & Active Support

* **[v0.7.0-alpha](v0.7.0-alpha.md)** — *Administrative Automation*
  Transforming Internara into a powerful assistant that automates prerequisite checks and grading.

* **[v0.6.0-alpha](v0.6.0-alpha.md)** — *Assessment & Workspaces*
  Finalized the internship lifecycle with role-specific workspaces and verifiable certificates.

---

### Legacy Releases (No Support)

* **[v0.5.0-alpha](v0.5.0-alpha.md)** — *Operational Layer*
  The foundation of student tracking: Daily Journals and Attendance.

---

### Historical Archive

These versions are preserved for historical reference.

* **[v0.4.0-alpha](archived/v0.4.0-alpha.md)** — *Institutional Foundation*
  Schools, Departments, and Placement Management.

* **[v0.3.0-alpha](archived/v0.3.0-alpha.md)** — *Identity & Security*
  Multi-role Profiles and Security Hardening.

* **[v0.2.0-alpha](archived/v0.2.0-alpha.md)** — *Core Engine*
  RBAC Infrastructure and Universal Tools.

* **[v0.1.1-alpha](archived/v0.1.1-alpha.md)** — *Genesis*
  Initial Environment and Modular Monolith Foundation.

---

## 4. Immediate Roadmap (Current Series: v0.7.x)

The **v0.7.x** series concentrates on **Administrative Automation**.

Planned direction includes:

* **Batch Operations**
  Bulk certificate issuance and state transitions.

* **Advanced Reporting**
  Aggregated, institution-level performance metrics.

* **Infrastructure Hardening**
  Evaluation and preparation for Livewire v4 migration.

---

## 5. Relationship to Engineering Plans

This document provides **User-Facing Visibility**.

For architectural intent, constraints, and technical deep-dives, refer to:

* **[Engineering Plans Index](../internal/plans/table-of-contents.md)**

> Version Overviews describe **what the user gets**.
> Engineering Plans describe **how we built it**.
