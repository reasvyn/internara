# Permission Module

The `Permission` module implements Role-Based Access Control (RBAC) within Internara. it wraps
`spatie/laravel-permission` into a modularized, portable system that supports UUIDs and cross-module
authorization checks.

## Purpose

- **Authorization:** Defines what actions users can perform.
- **Structure:** Groups permissions into roles for easier management.
- **Decoupling:** Allows domain modules to check for abilities without depending on concrete user
  management logic.

## Key Features

- **[RBAC Infrastructure](../../docs/main/role-permission-management.md)**: Full integration with
  UUID support.
- **[Core Seeders](../../docs/main/permission-seeders.md)**: Foundational roles like `super-admin`,
  `admin`, `teacher`, and `student`.
- **[Policy Patterns](../../docs/main/policy-patterns.md)**: Standardized patterns for implementing
  module-specific authorization.
- **[UI Components](../../docs/main/permission-ui-components.md)**: Shared elements like the
  `RoleBadge`.

---

**Navigation** [← Back to Module TOC](../../docs/main/modules/table-of-contents.md)
