# Flat RBAC with Functional Roles

## Status
Accepted

## Context

The system has five distinct user types — SUPER_ADMIN, ADMIN, TEACHER, STUDENT, SUPERVISOR —
and two behavioral roles — MENTOR and MENTEE — that describe what a user does, not who they
are. A TEACHER and a SUPERVISOR can both act as a MENTOR; a STUDENT is a MENTEE.

Two common RBAC approaches were considered:

1. **Hierarchical roles**: ADMIN > TEACHER > STUDENT, where higher roles inherit lower role
   permissions. This is intuitive but creates permission leakage — what happens when a TEACHER
   needs a specific permission that an ADMIN doesn't have, or when a new permission should
   apply to SUPERVISOR but not TEACHER? The hierarchy makes exceptions painful.

2. **Flat roles with explicit permission assignment**: Each role has exactly the permissions
   it needs, no inheritance. More verbose, but no accidental permission leakage. Adding a
   permission to one role never affects another.

The functional role concept (MENTOR/MENTEE) adds another dimension. A user's functional role
is derived from their user role, not assigned independently. This decouples the mentoring
subsystem from specific user types: a company supervisor and an academic teacher both resolve
to MENTOR without needing the same user role.

## Decision

### User Roles (Flat, Explicit)

Each user has exactly one user role. Roles are flat — no inheritance:

| Role | Scope | Description |
|---|---|---|
| `super_admin` | Global | Bypasses all gates. Manages system settings, all accounts, all data. |
| `admin` | School | Manages users, programs, companies, departments, operational features. |
| `teacher` | School | Academic supervision: grades assignments, assesses students, verifies journals. |
| `student` | Self | Participates in programs: clocks attendance, writes journals, submits assignments. |
| `supervisor` | Company | Industry supervision: verifies journals, evaluates student at host company. |

### Functional Roles (Derived, Not Stored)

Functional roles are derived from user roles via `Role::resolvesTo()`:

| Functional Role | Resolves From | Purpose |
|---|---|---|
| `mentor` | `teacher`, `supervisor` | Represents anyone who supervises students |
| `mentee` | `student` | Represents anyone being supervised |

This separation keeps the route security layer simple (concrete roles only) while allowing
Actions to write role-agnostic business logic:

```php
// An Action that needs the acting user's mentoring role:
$functionalRole = Role::from($user->roles->first()->name);
if ($functionalRole->is(Role::MENTOR)) {
    // This works for both teachers and supervisors
}
```

### Super Admin Bypass

`super_admin` bypasses all authorization gates via `Gate::before()`. No permission check
runs against super admins — the `Gate::before()` callback returns `true` immediately.

This is distinct from giving super_admin "all permissions" in the database. It simply skips
the permission system entirely, which is more efficient and guarantees that super_admin never
accidentally lacks a permission.

### Authorization Layers

Permissions are enforced at three levels:

| Layer | Mechanism | Example |
|---|---|---|
| **Routes** | `CheckRoleMiddleware` with `role:{role1|role2}` syntax | `middleware(['auth', 'role:super_admin|admin'])` |
| **Livewire** | Authorization checks in component methods | `$this->authorize('create', AcademicYear::class)` |
| **Policies** | Policy methods via `BasePolicy` traits | `AcademicYearPolicy::create()` → `$this->isAdmin($user)` |

The `BasePolicy` provides two traits that standardize authorization:
- `AuthorizesRoles` — `isAdmin()`, `isTeacher()`, `isStudent()`, `isSupervisor()`, `hasAnyOfRoles()`
- `AuthorizesOwnership` — `isOwner()`, `isOwnerOrAdmin()`, `isRelatedThrough()`

## Consequences

- **Positive**: Role inheritance is explicit and testable — no accidental permission leakage
  through hierarchy. Adding a permission to TEACHER never affects ADMIN or SUPERVISOR.
- **Positive**: Functional roles decouple the mentoring system from specific user types.
  Adding a new mentor-like role (e.g., COACH) requires only updating `Role::resolvesTo()`.
- **Positive**: `super_admin` bypass is fast and simple — one `Gate::before()` check returns
  `true` instead of enumerating permissions from the database.
- **Positive**: Three-layer enforcement (routes, Livewire, policies) provides defense in
  depth — a missing policy check can still be caught at the route middleware level.
- **Negative**: Flat roles require explicit permission lists per role in every policy —
  more verbose than role hierarchy where permissions cascade.
- **Negative**: Functional role derivation can be confusing at first — "why is this user
  allowed to access mentor features when their role is SUPERVISOR, not TEACHER?"
- **Negative**: `super_admin` bypass means actions performed by super admins are not validated
  against business rules defined in policies — they always pass. This is intentional but must
  be documented for auditors.

## References

- `app/Domain/Auth/Enums/Role.php` — role definitions with `resolvesTo()` mapping
- `app/Domain/Core/Policies/BasePolicy.php` — base authorization class
- `app/Domain/Core/Policies/Concerns/AuthorizesRoles.php` — role check methods
- `app/Domain/Core/Policies/Concerns/AuthorizesOwnership.php` — ownership check methods
- `app/Domain/Auth/Http/Middleware/CheckRoleMiddleware.php` — route-level gating
- `docs/rbac.md` — detailed RBAC documentation
- `docs/architecture.md` — Authorization (Layer 8) section
