# Policy Pattern — Authorization Gates, RBAC & Functional Roles

> **Last updated:** 2026-06-13 **Changes:** initial metadata — no content changes

## Description

Authorization reference for the Internara codebase. Describes the Flat RBAC model, the three-layer
authorization stack, the `BasePolicy` contract, policy traits, auto-discovery, and the complete
policy inventory across all modules.

See also:

- [RBAC Foundation](../foundation/rbac.md) — authentication flow & role definitions
- [ADR-008: Flat RBAC with Functional Roles](../adr/adr-flat-rbac-with-functional-roles.md)
- [Modular Pattern Reference](modular-pattern.md) (§7 Policy & Authorization Patterns)

---

## 1. Flat RBAC — 5 User Roles + 2 Functional Roles

The system uses **flat** (non-hierarchical) roles. Each role has explicitly enumerated capabilities.
No role inherits permissions from another — adding a permission to one role never leaks to another.

### User Roles (Stored in Database)

| Role            | Code (DB)    | Scope   | Description                                                                           |
| --------------- | ------------ | ------- | ------------------------------------------------------------------------------------- |
| **Super Admin** | `superadmin` | Global  | Bypasses all gates. Manages system settings, all accounts, all data.                  |
| **Admin**       | `admin`      | School  | Manages users, programs, companies, departments, announcements, audit logs.           |
| **Teacher**     | `teacher`    | School  | Academic supervision: journal review, assignment grading, site visits, grade cards.   |
| **Supervisor**  | `supervisor` | Company | Industry supervision: attendance verification, journal review, competency evaluation. |
| **Student**     | `student`    | Self    | Program participation: attendance, logbooks, assignments, certificate download.       |

> **Role normalization note:** spatie/laravel-permission stores `superadmin` (no underscore). All
> User model methods (`hasRole`, `hasAnyRole`) transparently normalize `super_admin` → `superadmin`
> before delegating to the package. Use `super_admin` everywhere in application code; never
> reference `superadmin` directly.

### Functional Roles (Derived, Not Stored)

Functional roles exist for business logic only. They are resolved at runtime — never stored in the
database, never used in route middleware.

| Functional Role | Code          | Resolves From           | Purpose                        |
| --------------- | ------------- | ----------------------- | ------------------------------ |
| `mentor`        | `func_mentor` | `teacher`, `supervisor` | Anyone who supervises students |
| `mentee`        | `func_mentee` | `student`               | Anyone being supervised        |

Decouples the mentoring subsystem from specific user types.

---

## 2. Three-Layer Authorization

Authorization is enforced at three independent levels for defense in depth.

### Layer 1 — Routes (`CheckRoleMiddleware`)

Route-level role gating via `CheckRoleMiddleware`. Applied to route groups using the pipe-delimited
`role:` syntax. After authentication, checks the user has **at least one** of the required roles.
Returns 403 for unauthorized authenticated users, redirects to login for guests.

### Layer 2 — Livewire (Component Authorization)

Livewire components call `$this->authorize()` inline in their methods. Uses Laravel's built-in
`AuthorizesRequests` trait that delegates to the registered policy.

### Layer 3 — Policies (`BasePolicy`)

All policies extend `BasePolicy` and define granular `view`/`create`/`update`/`delete` methods using
the `AuthorizesRoles` and `AuthorizesOwnership` traits.

---

## 3. Gate::before Bypass for Super Admin

Super Admin bypasses all authorization checks through a `Gate::before` callback. Two registrations
exist:

### Production (`config/permission.php`)

spatie/laravel-permission auto-registers a `Gate::before` callback. For users with `superadmin`
role, it returns `true` (grant). For all others, `null` ("let the policy decide").

### Test

Explicitly registered in the test base class because spatie's auto-discovery may not fire in certain
test configurations.

### Effect

Super Admin is **not** a role with "all permissions" in the database — it skips the permission
system entirely. This is more efficient and eliminates the risk of accidentally omitting a
permission.

---

## 4. BasePolicy Contract

Every policy inherits a `before()` method that allows `super_admin` users unconditionally. Returns
`Response::allow()` for super admins, `null` for everyone else (delegates to the specific policy
method).

### Convenience Response Wrappers

| Method                    | Description                                              |
| ------------------------- | -------------------------------------------------------- |
| `allowIfAdmin()`          | Returns `allow()` if `isAdmin()`, else `deny()`          |
| `allowIfAdminOrTeacher()` | Returns `allow()` if `isAdminOrTeacher()`, else `deny()` |
| `allowIfOwner()`          | Returns `allow()` if `isOwner()`, else `deny()`          |

These return `Illuminate\Auth\Access\Response` objects suitable for policy methods that use
`Response` return types instead of `bool`.

> [!IMPORTANT] All policy methods must mark their parameter types explicitly (`User $user`,
> `Model $model`). Laravel resolves the authenticated user as the first argument — use `?User $user`
> type-hints for guest-accessible methods.

---

## 5. AuthorizesRoles Trait

Provides role-checking methods that reduce duplication of
`$user->hasAnyRole(['super_admin', 'admin'])` across all policy classes.

| Method               | Description                                       |
| -------------------- | ------------------------------------------------- |
| `isAdmin()`          | `super_admin` \| `admin`                          |
| `isTeacher()`        | `teacher` only                                    |
| `isStudent()`        | `student` only                                    |
| `isSupervisor()`     | `supervisor` only                                 |
| `isAdminOrTeacher()` | `super_admin` \| `admin` \| `teacher`             |
| `canManageAnyRole()` | Alias for `isAdmin`                               |
| `hasAnyOfRoles()`    | Generic check against an arbitrary array of roles |

Always write role checks against the conceptual role (`super_admin`), not the stored value
(`superadmin`). The User model handles normalization.

---

## 6. AuthorizesOwnership Trait

| Method               | Description                                                                |
| -------------------- | -------------------------------------------------------------------------- |
| `isOwner()`          | Direct foreign key match: `$model->{$foreignKey} === $user->id`            |
| `isRelatedThrough()` | Ownership through a relation: `$model->relation->foreignKey === $user->id` |
| `isOwnerOrAdmin()`   | Composite: owner OR admin (uses `isAdmin()` from `AuthorizesRoles`)        |

---

## 7. Policy Auto-Discovery

Policies are **auto-discovered** at boot time — there is no manual `$policies` array in
`AuthServiceProvider`.

### How Discovery Works

1. Scans all PHP files for directories named `Policies/`
2. Filters to classes whose name ends in `Policy` and extend `BasePolicy`
3. Derives the model class by convention based on the module namespace
4. Skips policies whose inferred model class does not exist
5. Registers via `Gate::policy()`
6. Results are **cached for 24 hours**

### Exception: User Policy

The `UserPolicy` is registered **explicitly** because the User model lives in a different namespace
structure.

### Re-discovering After Adding a Policy

```bash
php artisan cache:forget module_policies
php artisan module:discover
```

---

## 8. Testing Policies

Policy tests use direct instantiation with mock User models that override `hasRole` / `hasAnyRole`,
eliminating the need for a database. This keeps policy tests fast and isolated.

No database needed — override spatie's `hasRole`/`hasAnyRole` methods on anonymous subclasses.

### Testing Super Admin Bypass

The `Gate::before` callback is registered in the test base class. Feature tests that perform actions
as super admin users automatically bypass all policy checks.
