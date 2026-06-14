# Role-Based Access Control

> **Last updated:** 2026-06-10
>
> RBAC is fully implemented. See [Auth Module](../modules/auth-reference.md) and
> [Core Module](../modules/core-reference.md) reference docs.

---

## 1. Authentication Flow

```
Request → Authenticate middleware → Session created → RBAC gate
```

| Feature | Implementation |
|---------|---------------|
| Login | Email or username + bcrypt password |
| Session | Database driver, 120 min lifetime, HTTP-only cookie |
| Password reset | Token-based via `password_reset_tokens` table |
| Account locking | `users.locked_at` + `locked_reason` columns |
| Rate limiting | Multi-layer: global (30/min/IP), per-endpoint throttling |

---

## 2. Flat Role Hierarchy

The application defines five user roles in a flat hierarchy. Roles do **not** inherit permissions
from parent roles. Each role has explicit, enumerated capabilities.

| Role | Code | Description |
|------|------|-------------|
| **Super Admin** | `super_admin` | Unrestricted access — bypasses all permission checks. Manages infrastructure, settings, and all accounts. |
| **Admin** | `admin` | School-level operations: user CRUD, programs, companies, departments, announcements, audit logs. |
| **Teacher** | `teacher` | Academic supervision: journal review, assignment grading, site visits, grade card compilation. |
| **Supervisor** | `supervisor` | Industry supervision: attendance verification, journal review, competency evaluation. |
| **Student** | `student` | Program participation: attendance, logbooks, assignments, certificate download. |

### Rationale for Flat Hierarchy

Hierarchical permission inheritance leads to unexpected behavior: adding a permission to a parent
role implicitly grants it to all children. Flat role definitions eliminate ambiguity — each role's
capabilities are explicitly enumerated and reviewed. Source: ADR-009 (Flat RBAC with Functional
Roles).

---

## 3. Functional Roles

A second family of **functional roles** exists for business logic only. These are logical groupings
resolved at runtime — never stored in the database, never used in route middleware.

| Functional Role | Resolves From |
|----------------|--------------|
| `mentor` | `teacher`, `supervisor` |
| `mentee` | `student` |

Decouples the mentoring subsystem from specific user types. See `Role::resolvesTo()` in
`app/Auth/Permissions/Enums/Role.php`.

---

## 4. Permission Model

Permissions are checked at three levels:

| Level | Mechanism | Syntax |
|-------|-----------|--------|
| Routes | `CheckRoleMiddleware` | `role:{role1\|role2}` pipe-delimited |
| Livewire | Component authorization methods | Inline `$this->authorize()` calls |
| Policies | `BasePolicy` with role/ownership traits | `AuthorizesRoles` + `AuthorizesOwnership` |

### Gate::before Bypass for Super Admin

`spatie/laravel-permission` auto-registers a `Gate::before` callback via
`register_permission_check_method` config (`config/permission.php`). For super_admin users, this
callback returns `true` (grant access to everything). For all other users, it returns `null`
("let the policy decide").

This means super_admin is not a role with "all permissions" in the database — it skips the
permission system entirely. More efficient, zero chance of accidentally missing a permission.

In tests, `Gate::before` is additionally registered in `tests/TestCase.php`.

### CheckRoleMiddleware

Route-level role verification at `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php`. After
authentication, checks if the user has at least one required role. Pipe-delimited syntax:
`role:super_admin|admin`. Returns 403 for unauthorized authenticated users, redirects to login
for guests. Logs unauthorized access attempts.

### BasePolicy with AuthorizesRoles + AuthorizesOwnership

All policies extend `app/Core/Policies/BasePolicy.php` and use:

- `AuthorizesRoles` — checks if user has one of the allowed roles
- `AuthorizesOwnership` — checks if user owns the resource
- `HandlesAuthorizationErrors` — consistent 403 response format

### Super Admin Integrity Rules

- **Uniqueness:** Exactly one super_admin account in the database
- **Immutability:** Name always "Administrator", username always "superadmin"
- **Non-deletable:** All delete operations throw `RuntimeException`
- **Role Mapping:** `super_admin` → `superadmin` mapping for spatie compatibility

---

## 5. Key Locations

| Component | Path |
|-----------|------|
| Role Enum | `app/Auth/Permissions/Enums/Role.php` |
| CheckRoleMiddleware | `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php` |
| BasePolicy | `app/Core/Policies/BasePolicy.php` |
| Gate::before config | `config/permission.php` |
| RolePermissionSeeder | `database/seeders/RolePermissionSeeder.php` |
| Policies directory | `app/*/Policies/` (27 policy files) |
