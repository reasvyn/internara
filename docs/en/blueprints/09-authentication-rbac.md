# Blueprint 09: Authentication & RBAC

## Authentication

Internara uses Laravel's built-in session-based authentication with the
`database` session driver by default. Login is email/username + password.

### Auth Flow

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
| Suspicious detection | `suspicious_login_attempts` table with anomaly patterns |

## RBAC Model (See ADR-012)

### User Roles

Each user has exactly one user role. Roles are flat (no inheritance):

| Role | Scope | Description |
|---|---|---|
| `super_admin` | Global | Bypasses all gates. Manages system, admins, settings |
| `admin` | System | Manages users, internships, programs |
| `teacher` | School | Manages students, assignments, grades |
| `student` | Self | Participates in internship program |
| `supervisor` | Industry | Evaluates students at host company |

### Functional Roles

Functional roles are derived from user roles, not assigned directly:

| Functional Role | Resolves From |
|---|---|
| `mentor` | `teacher`, `supervisor` |
| `mentee` | `student` |

This decouples the mentoring subsystem from specific user types —
a `teacher` and a `supervisor` both resolve to `mentor` without sharing
the same user role. See `Role::resolvesTo()`.

### Permission Model

Permissions are assigned per role. The `super_admin` role bypasses all
authorization via `Gate::before()` — no permission check runs against
super admins. For all other roles, permissions are checked at:

1. **Routes** — `CheckRoleMiddleware` with `role:{role1|role2}` syntax
2. **Livewire components** — Authorization checks in component methods
3. **Policies** — Policy methods via `BasePolicy` traits

### Policy Structure

Policies extend `BasePolicy` and use two authorization traits:

| Trait | Methods |
|---|---|
| `AuthorizesRoles` | `isAdmin()`, `isTeacher()`, `isStudent()`, `isSupervisor()`, `hasAnyOfRoles()` |
| `AuthorizesOwnership` | `isOwner()`, `isOwnerOrAdmin()`, `isRelatedThrough()` |

## References

- `app/Domain/Auth/Enums/Role.php` — role definitions with functional mapping
- `app/Domain/Core/Policies/BasePolicy.php` — base authorization class
- `app/Domain/Core/Policies/Concerns/` — authorization traits
- `app/Domain/Auth/Http/Middleware/CheckRoleMiddleware.php` — route gating
- `docs/en/adr/adr-012-flat-rbac-with-functional-roles.md` — RBAC ADR
- `docs/en/rbac.md` — RBAC detailed documentation
