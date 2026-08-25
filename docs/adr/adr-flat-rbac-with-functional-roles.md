# Flat RBAC with Functional Roles

> **Last updated:** 2026-08-25 **Changes:** rewrite to MADR-lite industry-standard format

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [RBAC Guide](../guides/rbac.md) and [Permissions Spec](../specs/T4B26-rbac-and-authorization.md) |

## Context and Problem Statement

The system has five user types (super_admin, admin, teacher, student, supervisor) and three
behavioral concepts (admin-group, mentor, mentee) describing what a user *does*, not who they
are. A teacher and a supervisor both act as mentors; super_admin and admin share an "admin"
grouping for permission checks. Conflating identity with function leaks into policies as
duplicated `||` branches and makes mentoring logic brittle to user-type changes.

**Decision Drivers:**

* No permission leakage through implicit hierarchy
* Role-agnostic mentoring checks — adding a mentor-like role updates one mapping, not every policy
* Defense in depth across route, Livewire, and policy layers

## Considered Options

* **Hierarchical roles (ADMIN > TEACHER > STUDENT)** — intuitive inheritance.
  *Pros:* concise. *Cons:* permission added to TEACHER that ADMIN must not have requires
  workarounds; leakage is silent.*
* **Flat roles with explicit permissions plus derived functional roles (chosen)** — each role
  owns exactly its permissions; functional roles are runtime groupings derived from the concrete
  role. *Pros:* explicit, testable, decoupled mentoring; no leakage.*

## Decision Outcome

**Chosen option: Flat roles with derived functional roles.**

**Flat User Roles** — one role per user, no inheritance:

| Role | Scope | Description |
|------|-------|-------------|
| super_admin | Global | Bypasses all gates; manages settings, accounts, data |
| admin | School | Manages users, programs, companies, departments |
| teacher | School | Academic supervision — grades, journals |
| student | Self | Attendance, journals, assignments |
| supervisor | Company | Industry supervision — journals, evaluations |

**Functional Roles (derived, not stored)** — resolved via `Role::resolvesTo()`:

| Functional Role | Resolves From | Purpose |
|---------------|---------------|---------|
| admin-group | super_admin, admin | Administrative grouping |
| mentor | teacher, supervisor | Anyone supervising students |
| mentee | student | Anyone being supervised |

```php
SUPER_ADMIN → [ADMIN]
ADMIN       → [ADMIN]
TEACHER     → [MENTOR]
SUPERVISOR  → [MENTOR]
STUDENT     → [MENTEE]
```

`$user->role->is(Role::ADMIN)` matches both super_admin and admin without `||`.
Route middleware uses concrete roles; functional roles are evaluated at the policy layer.
`functionalRoles()` enumerates all functional roles; `functionalRolesFor()` maps a concrete
role at runtime.

**Super Admin Bypass** — `Gate::before()` returns `true` for super_admin immediately; no
permission lookup runs — distinct from granting "all permissions" in the database.

**Three Authorization Layers:**

| Layer | Mechanism | Example |
|-------|-----------|---------|
| Routes | CheckRoleMiddleware | `role:super_admin\|admin` |
| Livewire | `authorize()` in component | `$this->authorize('create', Model::class)` |
| Policies | BasePolicy traits | `isAdmin()`, `isOwner()` |

`BasePolicy` composes `AuthorizesRoles` and `AuthorizesOwnership`.

### Positive Consequences

* Explicit, testable — no accidental leakage through hierarchy
* Mentoring decoupled from user types — new mentor-like role updates one mapping
* ADMIN group eliminates duplicate `||` conditions
* Gate bypass is fast and defense in depth spans three layers

### Negative Consequences

* Flat roles require explicit permission lists per policy — more verbose
* Functional derivation (`supervisor` → `mentor`) can surprise newcomers — requires onboarding

## Links

* [RBAC Guide](../guides/rbac.md) — detailed role and permission documentation
* [Policy Pattern](../guides/arch/policy-pattern.md) — BasePolicy and authorization layers
* [Cross-Role Proxy](adr-cross-role-proxy.md) — separate cross-role delegation mechanism
* [Architecture Overview](../architecture.md) — where authorization sits across layers
