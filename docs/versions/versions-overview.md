# Application Versions Overview

This document provides a strategic map of Internara's release history, support lifecycle, and
immediate roadmap. We prioritize **Analytical Versioning**, ensuring that every milestone is
documented with technical rationale and implementation deep-dives.

---

## 1. Version Support Lifecycle

To ensure system stability and security, we maintain a clear support schedule.

| Version    | Stage | Security | Bug Fixes | Status                 |
| :--------- | :---- | :------- | :-------- | :--------------------- |
| **v0.7.x** | Alpha | ✅       | ✅        | **Active Development** |
| **v0.6.x** | Alpha | ✅       | ✅        | **Released**           |
| **v0.5.x** | Alpha | ❌       | ❌        | EOL (End of Life)      |
| **v0.4.x** | Alpha | ❌       | ❌        | EOL (End of Life)      |

---

## 2. Release History (Deep Narratives)

These documents capture the final "as-built" technical narrative for each specific release.

### In Progress (Active Development)

- **[v0.7.0-alpha](unreleases/v0.7.0-alpha.md)**: **Administrative Automation**. Focusing on bulk
  operations, queue-based certificate generation, and national reporting exports.

### Latest Release

- **[v0.6.0-alpha](releases/v0.6.0-alpha.md)**: **Assessment & Workspaces Phase**. Finalized the core
  internship cycle by implementing role-specific workspaces, unified assessment logic, and
  professional PDF reporting with QR-code verification.

### Historical Archive

- **[v0.5.0-alpha](releases/v0.5.0-alpha.md)**: **Operational Phase**. Daily Journals, Attendance, and Supervisor Matching.
- **[v0.4.0-alpha](releases/v0.4.0-alpha.md)**: **Institutional Phase**. Schools, Departments, and Placement management.
- **[v0.3.0-alpha](releases/v0.3.0-alpha.md)**: **User Phase**. Multi-role profiles and security hardening.
- **[v0.2.0-alpha](releases/v0.2.0-alpha.md)**: **Core Phase**. RBAC Infrastructure and Shared Eloquent Concerns.
- **[v0.1.1-alpha](releases/v0.1.1-alpha.md)**: **Genesis Phase**. Initial environment and Modular Monolith setup.

---

## 3. Immediate Roadmap (v0.7.x)

The focus of the current development series is **Administrative Automation**.

- **Batch Operations**: Bulk certificate generation and status transitions.
- **Advanced Reporting**: Aggregated school-wide performance metrics.
- **Infrastructure Hardening**: Migrating to Livewire v4 (Draft phase).

---

_For detailed technical planning of future iterations, refer to the
**[Development Plans](../internal/plans/dev-plans-guide.md)** index._
