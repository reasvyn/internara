# ADR-008: Flat RBAC with Functional Roles

> **Last updated:** 2026-07-05
>
> **Changes:** sync — add ADMIN as functional role for role grouping; fix role names; add
> functionalRolesFor() documentation

## Description

A flat role-based access control system with five static roles (super_admin, admin, teacher,
supervisor, student) plus three runtime functional roles (admin-group, mentor, mentee).

## Context

The system has five user types (super_admin, admin, teacher, student, supervisor) and three
behavioral concepts (admin-group, mentor, mentee) that describe what a user does, not who they are.
A teacher and a supervisor both act as mentors; a student is a mentee; super_admin and admin share
the "admin" functional grouping for permission checks.

Two RBAC approaches were considered:

1. **Hierarchical roles** — ADMIN > TEACHER > STUDENT with inheritance. Intuitive, but creates
   permission leakage. Adding a permission to TEACHER that ADMIN shouldn't have requires
   workarounds.

2. **Flat roles with explicit permissions** — each role has exactly its own permissions, no
   inheritance. More verbose, but no accidental leakage. Adding a permission to one role never
   affects another.

The functional role concept adds another dimension: a user's functional role is derived from their
user role, not stored independently. This decouples the mentoring system from specific user types.

## Decision

### Flat User Roles

Each user has exactly one user role. Roles are flat — no inheritance:

| Role        | Scope   | Description                                                          |
| ----------- | ------- | -------------------------------------------------------------------- |
| super_admin | Global  | Bypasses all gates. Manages system settings, all accounts, all data. |
| admin       | School  | Manages users, programs, companies, departments.                     |
| teacher     | School  | Academic supervision: grades, assesses, verifies journals.           |
| student     | Self    | Participates in programs: attendance, journals, assignments.         |
| supervisor  | Company | Industry supervision: verifies journals, evaluates students.         |

### Functional Roles (Derived, Not Stored)

Functional roles are runtime groupings that allow role-agnostic permission checks. They are resolved
via `Role::resolvesTo()`:

| Functional Role | Resolves From       | Purpose                                       |
| --------------- | ------------------- | --------------------------------------------- |
| admin-group     | super_admin, admin  | Administrative grouping for permission checks |
| mentor          | teacher, supervisor | Anyone who supervises students                |
| mentee          | student             | Anyone being supervised                       |

The `functionalRoles()` method returns all possible functional roles for enum-wide checks. The
`functionalRolesFor()` method maps a concrete user role to its functional role at runtime:

```php
SUPER_ADMIN → [ADMIN]     // super_admin maps to admin functional group
ADMIN       → [ADMIN]     // admin maps to admin functional group
TEACHER     → [MENTOR]    // teacher maps to mentor
SUPERVISOR  → [MENTOR]    // supervisor also maps to mentor
STUDENT     → [MENTEE]    // student maps to mentee
```

This enables code like `$user->role->is(Role::ADMIN)` to match both super_admin and admin without
explicit `||` checks. Route middleware still uses concrete roles only; functional roles are
evaluated at the policy level.

### Super Admin Bypass

`super_admin` bypasses all authorization gates via `Gate::before()` returning `true` immediately. No
permission check runs against super admins — distinct from giving them "all permissions" in the
database.

### Authorization Layers (Three-Level)

| Layer    | Mechanism                        | Example                                    |
| -------- | -------------------------------- | ------------------------------------------ |
| Routes   | CheckRoleMiddleware              | `role:super_admin\|admin`                  |
| Livewire | authorize() in component methods | `$this->authorize('create', Model::class)` |
| Policies | BasePolicy traits                | `isAdmin()`, `isOwner()`                   |

`BasePolicy` provides `AuthorizesRoles` (role check methods) and `AuthorizesOwnership` (ownership
check methods) traits.

## Consequences

- **Positive**: Role inheritance is explicit and testable — no accidental permission leakage through
  hierarchy.
- **Positive**: Functional roles decouple the mentoring system from specific user types. Adding a
  new mentor-like role requires only updating `Role::resolvesTo()`.
- **Positive**: ADMIN functional role groups super_admin and admin under one check, eliminating
  duplicate `||` conditions in policies.
- **Positive**: `super_admin` bypass is fast — one `Gate::before()` check instead of enumerating
  database permissions.
- **Positive**: Three-layer enforcement provides defense in depth.
- **Negative**: Flat roles require explicit permission lists per role in every policy — more verbose
  than hierarchy.
- **Negative**: Functional role derivation can be confusing — "why is this supervisor allowed to
  access mentor features?"

## References

- `app/Auth/Permissions/Enums/Role.php` — Role definitions with resolvesTo(), functionalRoles(),
  functionalRolesFor()
- `app/Core/Policies/BasePolicy.php` — Base authorization class
- `app/Core/Policies/Concerns/AuthorizesRoles.php` — Role check methods
- `app/Core/Policies/Concerns/AuthorizesOwnership.php` — Ownership check methods
- `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php` — Route-level gating
- `docs/adr/adr-cross-role-proxy.md` — Cross-role proxy (separate from functional roles)
- `docs/foundation/rbac.md` — Detailed RBAC documentation
- `docs/architecture.md` — Authorization section
