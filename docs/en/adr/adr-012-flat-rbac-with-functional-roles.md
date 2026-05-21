# ADR-012: Flat RBAC with Functional Roles

## Status
Accepted

## Context
The system has five distinct user types — SUPER_ADMIN, ADMIN, TEACHER, STUDENT, SUPERVISOR —
plus two behavioral roles — MENTOR and MENTEE — that describe what a user does, not who they
are. A TEACHER and a SUPERVISOR can both act as a MENTOR; a STUDENT is a MENTEE.

Two common RBAC approaches were considered:
1. **Hierarchical roles**: ADMIN > TEACHER > STUDENT, where higher roles inherit lower role
   permissions. This is intuitive but creates problems — what happens when a TEACHER needs
   a specific permission that an ADMIN doesn't have? Inheritance leaks.
2. **Flat roles with explicit permission assignment**: Each role has exactly the permissions
   it needs, no inheritance. More administrative overhead but no permission leakage.

The functional role concept (MENTOR/MENTEE) adds another dimension — a user's functional role
is derived from their user role, not assigned. This decouples the mentoring subsystem from
specific user roles: a company supervisor and an academic teacher both resolve to MENTOR
without needing the same user role.

## Decision
Users have exactly one **user role** (SUPER_ADMIN, ADMIN, TEACHER, STUDENT, SUPERVISOR).
Functional roles (MENTOR, MENTEE) are derived via `Role::resolvesTo()`:

- MENTOR resolves from TEACHER and SUPERVISOR
- MENTEE resolves from STUDENT

`super_admin` bypasses all authorization gates via `Gate::before()` — no permission check
runs against super admins. Role enforcement happens at three layers: routes
(`CheckRoleMiddleware`), Livewire components (authorization checks), and policies (policy
methods via `BasePolicy`'s `AuthorizesRoles` trait).

## Consequences
- **Positive**: Role inheritance is explicit and testable — no accidental permission leakage
  through hierarchy.
- **Positive**: Functional roles decouple the mentoring system from specific user types.
  Adding a new mentor-like role (e.g., COACH) requires only updating `Role::resolvesTo()`.
- **Positive**: `super_admin` bypass is fast and simple — one `Gate::before()` check instead
  of enumerating permissions.
- **Negative**: Flat roles require explicit permission lists per role — more verbose than
  role hierarchy.
- **Negative**: Functional role derivation can be confusing at first — "why is this user a
  MENTOR when their role is TEACHER?"
- **Negative**: `super_admin` bypass means super admin actions are not validated against
  business rules defined in policies — they always pass.

## References
- `app/Domain/Auth/Enums/Role.php` — functional role mapping
- `app/Domain/Core/Policies/Concerns/AuthorizesRoles.php`
- `docs/en/rbac.md`
- `tests/TestCase.php` — `Gate::before()` registration
