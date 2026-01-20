# Application Versions Overview

This document provides a **strategic overview of Internara’s application versions**, covering
release lineage, lifecycle status, and support posture.

Internara adopts **Analytical Versioning**, where each released version is accompanied by a
post-release technical narrative that documents architectural decisions and realized outcomes.

---

## 1. Version Lifecycle Model

Each version series progresses through clearly defined **lifecycle stages**, independent from
development activity.

### Lifecycle Stages

* **Alpha**
  Early-stage product validation. APIs, schemas, and workflows are subject to change.

* **Beta** *(future)*
  Feature-complete with stabilization focus.

* **Stable** *(future)*
  Production-grade with defined support commitments.

---

### Support Policy Definitions

* **Snapshot**
  No ongoing maintenance. Code is preserved as-is for reference and continuity.

* **Supported** *(future)*
  Receives bug fixes and security updates within policy constraints.

* **EOL (End of Life)**
  No maintenance, fixes, or security updates.

---

## 2. Version Support Matrix

This table represents the **authoritative SDLC state** of each version series.

| Version    | Stage | Support Policy | Security Updates | Bug Fixes | Lifecycle Status   |
| :--------- | :---- | :------------- | :--------------- | :-------- | :----------------- |
| **v0.7.x** | Alpha | Snapshot       | ❌                | ❌         | Active Development |
| **v0.6.x** | Alpha | Snapshot       | ❌                | ❌         | Released           |
| **v0.5.x** | Alpha | EOL            | ❌                | ❌         | Archived           |
| **v0.4.x** | Alpha | EOL            | ❌                | ❌         | Archived           |

### Notes

* **Stage** reflects maturity level
* **Lifecycle Status** reflects current SDLC position
* *Active Development* and *Released* are **SDLC states**, not quality indicators

---

## 3. Analytical Release Narratives

Each released version has a dedicated **Analytical Narrative** documenting the final system state.

These documents serve as the **single source of truth** for:

* Architectural outcomes
* Domain evolution
* Trade-offs and constraints

---

### In Progress (Active Development)

* **[v0.7.0-alpha](v0.7.0-alpha.md)**
  **Administrative Automation Phase**
  Focused on bulk operations, queue-driven certificate generation, and national reporting exports.

---

### Latest Released Version

* **[v0.6.0-alpha](v0.6.0-alpha.md)**
  **Assessment & Workspaces Phase**
  Finalized the internship lifecycle with role-specific workspaces, unified assessment logic, and
  verifiable PDF reporting using QR codes.

---

### Historical Archive

These versions are preserved for architectural traceability.

* **[v0.5.0-alpha](archived/v0.5.0-alpha.md)** — *Operational Phase*
  Daily Journals, Attendance, and Supervisor Matching.

* **[v0.4.0-alpha](archived/v0.4.0-alpha.md)** — *Institutional Phase*
  Schools, Departments, and Placement Management.

* **[v0.3.0-alpha](archived/v0.3.0-alpha.md)** — *User Phase*
  Multi-role Profiles and Security Hardening.

* **[v0.2.0-alpha](archived/v0.2.0-alpha.md)** — *Core Phase*
  RBAC Infrastructure and Shared Eloquent Concerns.

* **[v0.1.1-alpha](archived/v0.1.1-alpha.md)** — *Genesis Phase*
  Initial Environment and Modular Monolith Foundation.

---

## 4. Immediate Roadmap (Current Series: v0.7.x)

The **v0.7.x** series concentrates on **Administrative Automation** and operational scalability.

Planned direction includes:

* **Batch Operations**
  Bulk certificate issuance and state transitions.

* **Advanced Reporting**
  Aggregated, institution-level performance metrics.

* **Infrastructure Hardening**
  Evaluation and preparation for Livewire v4 migration.

This roadmap is **directional**, not contractual, and may evolve as architectural findings emerge.

---

## 5. Relationship to Engineering Plans

This document provides **SDLC visibility only**.

For architectural intent, constraints, exit criteria, and forward-looking analysis, refer to:

* **[Engineering Plans Index](../internal/plans/dev-plans-guide.md)**

> Version Overviews describe **when and where a version exists**.
> Engineering Plans describe **why and how it was shaped**.
