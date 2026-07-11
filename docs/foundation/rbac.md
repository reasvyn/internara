# Role-Based Access Control — RBAC Implementation & Permission Model

> **Last updated:** 2026-07-11
>
> **Changes:** sync — add ADMIN functional role; add Cross-Role Proxy section; update functional
> role docs

## Description

Flat RBAC model with five static roles, three functional roles, Cross-Role Proxy for delegation,
permission registration, and the super_admin bypass.

---

## 1. Authentication Flow

```
Request → Authenticate middleware → Session created → RBAC gate
```

| Feature         | Implementation                                           |
| --------------- | -------------------------------------------------------- |
| Login           | Email or username + bcrypt password                      |
| Session         | Database driver, 120 min lifetime, HTTP-only cookie      |
| Password reset  | Token-based via `password_reset_tokens` table            |
| Account locking | `users.locked_at` + `locked_reason` columns              |
| Rate limiting   | Multi-layer: global (30/min/IP), per-endpoint throttling |

---

## 2. Flat Role Hierarchy

The application defines five user roles in a flat hierarchy. Roles do **not** inherit permissions
from parent roles. Each role has explicit, enumerated capabilities.

| Role            | Code          | Description                                                                                               |
| --------------- | ------------- | --------------------------------------------------------------------------------------------------------- |
| **Super Admin** | `super_admin` | Unrestricted access — bypasses all permission checks. Manages infrastructure, settings, and all accounts. |
| **Admin**       | `admin`       | School-level operations: user CRUD, programs, companies, departments, announcements, audit logs.          |
| **Teacher**     | `teacher`     | Academic supervision: journal review, assignment grading, site visits, grade card compilation.            |
| **Supervisor**  | `supervisor`  | Industry supervision: attendance verification, journal review, competency evaluation.                     |
| **Student**     | `student`     | Program participation: attendance, logbooks, assignments, certificate download.                           |

### Rationale for Flat Hierarchy

Hierarchical permission inheritance leads to unexpected behavior: adding a permission to a parent
role implicitly grants it to all children. Flat role definitions eliminate ambiguity — each role's
capabilities are explicitly enumerated and reviewed. Source: ADR-009 (Flat RBAC with Functional
Roles).

---

## 3. Functional Roles

A second family of **functional roles** exists for business logic only. These are logical groupings
resolved at runtime — never stored in the database, never used in route middleware.

| Functional Role | Resolves From           | Purpose                                              |
| --------------- | ----------------------- | ---------------------------------------------------- |
| `admin-group`   | `super_admin`, `admin`  | Administrative grouping for shared permission checks |
| `mentor`        | `teacher`, `supervisor` | Anyone who supervises students                       |
| `mentee`        | `student`               | Anyone being supervised                              |

The `functionalRoles()` method lists all functional role cases. The `functionalRolesFor()` method
maps a concrete user role to its functional role:

```
super_admin → admin-group
admin       → admin-group
teacher     → mentor
supervisor  → mentor
student     → mentee
```

This allows policy code to check `$user->role->is(Role::ADMIN)` instead of writing
`$user->hasRole('super_admin') || $user->hasRole('admin')`.

See `Role::resolvesTo()` and `Role::functionalRolesFor()` in `app/Auth/Permissions/Enums/Role.php`.

---

## 4. Permission Model

Permissions are checked at three levels:

| Level    | Mechanism                               | Syntax                                    |
| -------- | --------------------------------------- | ----------------------------------------- |
| Routes   | `CheckRoleMiddleware`                   | `role:{role1\|role2}` pipe-delimited      |
| Livewire | Component authorization methods         | Inline `$this->authorize()` calls         |
| Policies | `BasePolicy` with role/ownership traits | `AuthorizesRoles` + `AuthorizesOwnership` |

### Gate::before Bypass for Super Admin

`spatie/laravel-permission` auto-registers a `Gate::before` callback via
`register_permission_check_method` config (`config/permission.php`). For super_admin users, this
callback returns `true` (grant access to everything). For all other users, it returns `null` ("let
the policy decide").

This means super_admin is not a role with "all permissions" in the database — it skips the
permission system entirely. More efficient, zero chance of accidentally missing a permission.

In tests, `Gate::before` is additionally registered in `tests/TestCase.php`.

### CheckRoleMiddleware

Route-level role verification at `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php`.
After authentication, checks if the user has at least one required role. Pipe-delimited syntax:
`role:super_admin|admin`. Returns 403 for unauthorized authenticated users, redirects to login for
guests. Logs unauthorized access attempts.

### BasePolicy with AuthorizesRoles + AuthorizesOwnership

All policies extend `app/Core/Policies/BasePolicy.php` and use:

- `AuthorizesRoles` — checks if user has one of the allowed roles
- `AuthorizesOwnership` — checks if user owns the resource
- `HandlesAuthorizationErrors` — consistent 403 response format

---

## 5. Super Admin Integrity Rules

- **Uniqueness:** Exactly one super_admin account in the database
- **Immutability:** Name always "Administrator", username always "superadmin"
- **Non-deletable:** All delete operations throw `RuntimeException`
- **Role Mapping:** `super_admin` → `superadmin` mapping for spatie compatibility

---

## 6. Cross-Role Proxy

In addition to functional roles, Internara implements **Cross-Role Proxy** for operational
delegation. Unlike functional roles (which are static role groupings), proxy allows one user to act
on behalf of another for specific operations.

| Proxy Path           | Scope                                               |
| -------------------- | --------------------------------------------------- |
| Admin → Teacher      | Any student in any program                          |
| Admin → Supervisor   | Any student in any program                          |
| Teacher → Supervisor | Only students assigned to that teacher's mentorship |

Proxy is checked at the policy layer via `MentorEntity` (bridged from Registration model as
`asMentorEntity()`). The activity log records `proxy_role` metadata for audit trail.

See [ADR-014: Cross-Role Proxy](../adr/adr-cross-role-proxy.md) for full details.

---

## 7. Key Locations

| Component            | Path                                                           |
| -------------------- | -------------------------------------------------------------- |
| Role Enum            | `app/Auth/Permissions/Enums/Role.php`                          |
| CheckRoleMiddleware  | `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php` |
| BasePolicy           | `app/Core/Policies/BasePolicy.php`                             |
| Gate::before config  | `config/permission.php`                                        |
| RolePermissionSeeder | `database/seeders/RolePermissionSeeder.php`                    |
| Policies directory   | `app/*/Policies/` (27 policy files)                            |
