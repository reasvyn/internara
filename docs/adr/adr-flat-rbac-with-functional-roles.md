# ADR-008: Flat RBAC with Functional Roles

> **Status:** Accepted
> **Last updated:** 2026-06-14
> > **Changes:** sync — fix Auth module paths (Enums/Role.php → Permissions/Enums/Role.php, Http/Middleware → Permissions/Http/Middleware)

## Context

The system has five user types (super_admin, admin, teacher, student, supervisor) and two behavioral concepts (mentor, mentee) that describe what a user does, not who they are. A teacher and a supervisor both act as mentors; a student is a mentee.

Two RBAC approaches were considered:

1. **Hierarchical roles** — ADMIN > TEACHER > STUDENT with inheritance. Intuitive, but creates permission leakage. Adding a permission to TEACHER that ADMIN shouldn't have requires workarounds.

2. **Flat roles with explicit permissions** — each role has exactly its own permissions, no inheritance. More verbose, but no accidental leakage. Adding a permission to one role never affects another.

The functional role concept adds another dimension: a user's functional role is derived from their user role, not stored independently. This decouples the mentoring system from specific user types.

## Decision

### Flat User Roles

Each user has exactly one user role. Roles are flat — no inheritance:

| Role | Scope | Description |
|---|---|---|
| super_admin | Global | Bypasses all gates. Manages system settings, all accounts, all data. |
| admin | School | Manages users, programs, companies, departments. |
| teacher | School | Academic supervision: grades, assesses, verifies journals. |
| student | Self | Participates in programs: attendance, journals, assignments. |
| supervisor | Company | Industry supervision: verifies journals, evaluates students. |

### Functional Roles (Derived, Not Stored)

Functional roles are derived via `Role::resolvesTo()`:

| Functional Role | Resolves From | Purpose |
|---|---|---|
| mentor | teacher, supervisor | Anyone who supervises students |
| mentee | student | Anyone being supervised |

This keeps route security simple (concrete roles only) while allowing Actions to write role-agnostic logic.

### Super Admin Bypass

`super_admin` bypasses all authorization gates via `Gate::before()` returning `true` immediately. No permission check runs against super admins — distinct from giving them "all permissions" in the database.

### Authorization Layers (Three-Level)

| Layer | Mechanism | Example |
|---|---|---|
| Routes | CheckRoleMiddleware | `role:super_admin\|admin` |
| Livewire | authorize() in component methods | `$this->authorize('create', Model::class)` |
| Policies | BasePolicy traits | `isAdmin()`, `isOwner()` |

`BasePolicy` provides `AuthorizesRoles` (role check methods) and `AuthorizesOwnership` (ownership check methods) traits.

## Consequences

- **Positive**: Role inheritance is explicit and testable — no accidental permission leakage through hierarchy.
- **Positive**: Functional roles decouple the mentoring system from specific user types. Adding a new mentor-like role requires only updating `Role::resolvesTo()`.
- **Positive**: `super_admin` bypass is fast — one `Gate::before()` check instead of enumerating database permissions.
- **Positive**: Three-layer enforcement provides defense in depth.
- **Negative**: Flat roles require explicit permission lists per role in every policy — more verbose than hierarchy.
- **Negative**: Functional role derivation can be confusing — "why is this supervisor allowed to access mentor features?"

## References

- `app/Auth/Permissions/Enums/Role.php` — Role definitions with resolvesTo() mapping
- `app/Core/Policies/BasePolicy.php` — Base authorization class
- `app/Core/Policies/Concerns/AuthorizesRoles.php` — Role check methods
- `app/Core/Policies/Concerns/AuthorizesOwnership.php` — Ownership check methods
- `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php` — Route-level gating
- `docs/foundation/rbac.md` — Detailed RBAC documentation
- `docs/architecture.md` — Authorization section
