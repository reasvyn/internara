# Role-Based Access Control
> Last updated: 2026-05-31
> **Context:** ✅ RBAC is fully implemented — see [Auth](domain/auth-reference.md) and [User](domain/user-reference.md) reference docs.


## Authentication Flow

```
Request → Authenticate middleware → Session created → RBAC gate
```

| Feature | Implementation |
|---|---|
| Login | Email or username + bcrypt password |
| Session | Database driver, 120 min lifetime, HTTP-only cookie |
| Password reset | Token-based, stored in `password_reset_tokens` |
| Email verification | Token-based with `activation_tokens` table |
| Account locking | `users.locked_at` + `locked_reason` columns |
| Rate limiting | Login attempts throttled per IP |

## Role Hierarchy Design

The application defines five user roles in a flat hierarchy: superadmin,
admin, teacher, supervisor, and student. These are not arranged in a tree
where each role inherits the permissions of the roles below it. Instead,
each role has its own explicit set of permissions, and the superadmin role
bypasses all permission checks entirely.

Super admins manage the application infrastructure — they configure settings,
manage all user accounts, and have unrestricted access. There should be very
few super admin accounts.

Admins handle school-level operations: manage users, placement programs, companies,
departments, and the operational aspects of the placement program. They have
broad but not unrestricted access.

Teachers provide academic supervision: they can view and manage student
assignments and assessments, verify journals, and mentor students during
their placement program.

Supervisors provide industry-side supervision: they register attendance,
verify journals from the company perspective, and evaluate student
performance at the placement site.

Students are the participants: they submit assignments, write journals,
clock attendance, and view their own records.

### Functional Roles

A second family of functional roles exists for business logic only. These
are logical groupings resolved at runtime — never stored in the database
and never used in route middleware:

| Functional Role | Resolves From |
|---|---|
| `mentor` | `teacher`, `supervisor` |
| `mentee` | `student` |

This decouples the mentoring subsystem from specific user types —
a `teacher` and a `supervisor` both resolve to `mentor` without sharing
the same user role. See `Role::resolvesTo()` in code.

## Permission Model

Permissions are checked at three levels:

1. **Routes** — `CheckRoleMiddleware` with `role:{role1|role2}` syntax
2. **Livewire components** — Authorization checks in component methods
3. **Policies** — Policy methods via `BasePolicy` traits

## Why Flat Roles Instead of Hierarchical Permissions

A hierarchical permission system (where each role inherits from a parent) is
tempting but leads to unexpected behavior. When a permission is added to a
higher role, all subordinate roles implicitly gain it — which may or may not
be desired. When a permission is removed from a higher role, the effect on
child roles is unclear. Explicit flat role definitions eliminate this
ambiguity. Each role's capabilities are enumerated and reviewed.

## How Gate::before Bypass Works

Laravel's authorization system evaluates policies for each ability check.
The `spatie/laravel-permission` package auto-registers a `Gate::before`
callback via the `register_permission_check_method` config (enabled in
`config/permission.php`). For superadmin users, this callback returns
`true`, granting access to everything. For all other users, it returns
`null`, which means "I have no opinion — let the policy decide." This is
distinct from returning `false`, which would deny access even if the policy
would grant it.

This pattern means superadmin is not a role that has "all permissions"
assigned to it in the database. It simply skips the permission system
entirely. This is more efficient and guarantees that superadmin never
accidentally lacks a permission.

In tests, `Gate::before` is additionally registered in
`tests/TestCase.php` to ensure the bypass works during testing.

## What CheckRoleMiddleware Does

Route-level role verification is handled by `CheckRoleMiddleware`. This
middleware intercepts requests after authentication and checks whether the
authenticated user has at least one of the required roles. It accepts
pipe-delimited role names (e.g., `super_admin|admin`). If the user lacks any
of the required roles, the middleware returns a 403 response for
authenticated users or redirects to login for unauthenticated requests.

The middleware logs unauthorized access attempts for security monitoring.
It is applied to route groups in `routes/web.php` — each domain's routes are
gated by the roles that should have access.

## Where to Find It

Roles are defined in `app/Domain/Auth/Enums/Role.php`. Permissions are
managed dynamically via `spatie/laravel-permission` (database-driven, no
enum class). The seeder is at `database/seeders/RolePermissionSeeder.php`.
The middleware is at `app/Domain/Auth/Http/Middleware/CheckRoleMiddleware.php`.
The `Gate::before` bypass for `superadmin` is auto-registered by
`spatie/laravel-permission` via the `register_permission_check_method`
config in `config/permission.php`. Policies are in `app/Domain/*/Policies/`.
The spatie package configuration is in `config/permission.php`.
