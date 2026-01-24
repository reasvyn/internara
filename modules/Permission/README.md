# Permission Module

The `Permission` module implements Role-Based Access Control (RBAC) within Internara.

> **Spec Alignment:** This module enforces the **User Roles** and **Security** mandates of the
> **[Internara Specs](../../docs/internal/internara-specs.md)** (Section 10.6).

## Purpose

- **Authorization:** Defines granular access for Instructor, Staff, Student, and Industry Supervisor
  roles.
- **Portability:** Modular RBAC engine that supports UUID-based identity.
- **Standards:** Enforces standard **Policy Patterns** across all domain modules.

## Key Features

- **[RBAC Infrastructure](../../docs/internal/role-permission-management.md)**: Standardized modular
  access.
- **[Core Seeders](../../docs/internal/permission-seeders.md)**: Foundational role bootstrapping.
- **[Policy Patterns](../../docs/internal/policy-patterns.md)**: Logic for protecting resources.
- **[UI Components](../../docs/internal/permission-ui-components.md)**: Shared role/permission
  selectors.

---

_The Permission module is the security heart of Internara, ensuring that every action is
authorized._
