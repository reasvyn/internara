# RBAC & Authorization — Role-Based Access Control

> **Last updated:** 2026-07-23 **Changes:** feat — initial RBAC and authorization specification

## Description

Defines the flat role-based access control system: 5 user roles, 2 functional roles, three-layer
authorization stack (middleware → Livewire → Policy), `BasePolicy` contract with role and
ownership traits, super admin bypass, and policy auto-discovery. This spec is the authoritative
source for all authorization decisions in Internara.

---

## 1. Problem Statements

### PS-1 — Authorization Scattered Across Layers

Without a structured RBAC system, authorization checks are ad-hoc: some in routes, some in
Livewire components, some in Actions. This inconsistency leads to security gaps where certain
endpoints have no authorization at all.

### PS-2 — Super Admin Lockout Risk

If super admin authorization is not handled at the framework level (`Gate::before`), a
misconfigured policy could lock out the only account capable of fixing the system.

### PS-3 — Role Explosion Without Hierarchy

Adding new roles without a clear framework leads to exponential permission combinations. A flat
role model with explicit capabilities per role prevents this.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Every protected endpoint has at least one authorization layer |
| G2  | Super Admin bypasses ALL authorization checks via `Gate::before` |
| G3  | Role assignments are flat — no hierarchical inheritance |
| G4  | Ownership checks are reusable via `AuthorizesOwnership` trait |
| G5  | Policy auto-discovery eliminates manual policy registration |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Hierarchical role inheritance (Admin inherits Teacher permissions) |
| NG2  | Granular permission-per-action model (e.g., `user.create`, `user.update`) |
| NG3  | Role-based UI rendering (handled by Blade directives) |
| NG4  | Multi-tenancy role isolation |
| NG5  | API token scoping (handled by access tokens) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Assigns Role to User

**Actor:** Admin / Super Admin
**Preconditions:** User exists, assigner has Admin or Super Admin role
**Flow:**
1. Admin navigates to user management
2. Selects target user, chooses role from dropdown
3. Action validates role transition, persists via `UserStatusAction`
4. Spatie permission updated, user's cached roles invalidated
**Postconditions:** User can now access resources permitted for new role

### UC-2 — Teacher Accesses Supervision Resources

**Actor:** Teacher
**Preconditions:** Teacher is assigned as mentor to an internship group
**Flow:**
1. Teacher navigates to supervision log page
2. `CheckRoleMiddleware` verifies `teacher` role
3. `SupervisionLogPolicy::viewAny()` checks `isTeacher()` or `isMentorOf()`
4. Component renders supervised students' data
**Postconditions:** Teacher sees only supervised students' data

### UC-3 — Student Views Own Profile

**Actor:** Student
**Preconditions:** Student is authenticated
**Flow:**
1. Student navigates to profile page
2. `CheckRoleMiddleware` verifies `student` role
3. `ProfilePolicy::view()` checks `isOwner()` against authenticated user
4. Component renders own profile data
**Postconditions:** Student sees only own data

### UC-4 — Super Admin Bypasses All Checks

**Actor:** Super Admin
**Preconditions:** Authenticated as superadmin
**Flow:**
1. Super Admin accesses any protected endpoint
2. `BasePolicy::before()` returns `Response::allow()` for super_admin
3. No further policy checks execute
**Postconditions:** Full access to all resources

---

## 4. Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| FR-AUTH1 | `BasePolicy::before()` MUST return `Response::allow()` for `super_admin` role |
| FR-AUTH2 | `BasePolicy::before()` MUST return `null` for all other roles (continue checks) |
| FR-AUTH3 | `AuthorizesRoles` trait MUST provide `isAdmin()`, `isTeacher()`, `isStudent()`, `isSupervisor()`, `isAdminOrTeacher()`, `canManageAnyRole()`, `hasAnyOfRoles()` |
| FR-AUTH4 | `AuthorizesOwnership` trait MUST provide `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()` |
| FR-AUTH5 | `CheckRoleMiddleware` MUST verify user has required role before route execution |
| FR-AUTH6 | Policy auto-discovery MUST scan `Policies/` directories for `BasePolicy` subclasses |
| FR-AUTH7 | Auto-discovery MUST cache results for 24 hours |
| FR-AUTH8 | `UserPolicy` MUST be registered manually in `AppServiceProvider` (exception to auto-discovery) |
| FR-AUTH9 | Role normalization: code uses `super_admin`, Spatie stores `superadmin` |
| FR-AUTH10 | Cross-Role Proxy: teachers may act as supervisors for assigned students |
| FR-AUTH11 | Functional roles (`mentor`, `mentee`) MUST be resolved at runtime via profile accessor |
| FR-AUTH12 | Every policy MUST extend `BasePolicy` |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-AUTH1 | Authorization check latency MUST be < 5ms per request |
| NFR-AUTH2 | Policy auto-discovery cache MUST invalidate on `php artisan cache:forget` |
| NFR-AUTH3 | Role changes MUST take effect on next authenticated request |
| NFR-AUTH4 | Super admin bypass MUST work even if policy class is missing or broken |
| NFR-AUTH5 | All policies MUST be testable without database beyond the model instance |

---

## 6. API / Data Contracts

### BasePolicy

```php
abstract class BasePolicy
{
    use AuthorizesOwnership, AuthorizesRoles;

    public function before(User $user, string $ability): ?Response
    {
        if ($user->hasRole('super_admin')) {
            return Response::allow();
        }
        return null;
    }
}
```

### AuthorizesRoles Trait Methods

| Method | Returns | Logic |
|--------|---------|-------|
| `isAdmin()` | `bool` | `$user->hasRole('admin')` |
| `isTeacher()` | `bool` | `$user->hasRole('teacher')` |
| `isStudent()` | `bool` | `$user->hasRole('student')` |
| `isSupervisor()` | `bool` | `$user->hasRole('supervisor')` |
| `isAdminOrTeacher()` | `bool` | `isAdmin() \|\| isTeacher()` |
| `canManageAnyRole()` | `bool` | `isAdmin() \|\| isSupervisor()` |
| `hasAnyOfRoles(array)` | `bool` | Any role in array matches |

### AuthorizesOwnership Trait Methods

| Method | Returns | Logic |
|--------|---------|-------|
| `isOwner($user, $model)` | `bool` | `$model->user_id === $user->id` |
| `isRelatedThrough($user, $model, $relation)` | `bool` | Follows relationship to check ownership |
| `isOwnerOrAdmin($user, $model)` | `bool` | `isOwner() \|\| isAdmin()` |

### Role Definitions

| Role | Capabilities |
|------|-------------|
| `super_admin` | Full system access, bypasses all checks |
| `admin` | User management, system settings, announcements |
| `teacher` | Student supervision, grading, logbook review |
| `supervisor` | Company-side: attendance verification, evaluation |
| `student` | Own profile, logbook, attendance, submissions |

### Functional Roles (Runtime-Derived)

| Role | Resolution | Scope |
|------|-----------|-------|
| `mentor` | Teacher assigned to internship group | Supervises specific students |
| `mentee` | Student assigned to internship group | Supervised by specific teacher |

---

## 7. Design Decisions

### DD-1 — Flat Roles Without Hierarchy

**Decision:** Roles are flat — no role inherits permissions from another role.

**Rationale:** Hierarchical roles create invisible permission chains that are hard to audit.
Indonesian vocational schools have clear role boundaries (admin ≠ teacher ≠ student).

**Trade-off:** Some permissions are duplicated across roles (e.g., both admin and teacher can
view student data). This is acceptable for clarity.

### DD-2 — Three-Layer Authorization Stack

**Decision:** Authorization is enforced at three layers: route middleware, Livewire component,
and Policy gate.

**Rationale:** Defense in depth. Middleware prevents unauthorized route access. Livewire
authorization prevents component rendering. Policy gates protect individual methods.

**Trade-off:** Triple-checking adds minor overhead. Mitigated by super admin bypass and
framework-level caching.

### DD-3 — Super Admin Bypass via Gate::before

**Decision:** `BasePolicy::before()` returns `Response::allow()` for super_admin, bypassing
all subsequent checks.

**Rationale:** Super admin is the emergency recovery account. A misconfigured policy must never
lock out the only account that can fix the system.

**Trade-off:** Super admin cannot be restricted from specific actions. Acceptable because super
admin is a single-purpose recovery account.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Protected endpoints with authorization | 100% |
| Policies extending BasePolicy | 100% |
| Super admin lockout incidents | 0 |
| Authorization bypass vulnerabilities | 0 |
| Policy test coverage | 100% |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [core-foundation.md](core-foundation.md) | `BasePolicy`, `AuthorizesRoles`, `AuthorizesOwnership` traits |

### Build Guide
After implementing this spec, the system has role-based access control with 5 roles (super_admin, admin, teacher, student, supervisor), per-route middleware enforcement, and ownership-based policy checks. Every protected route and Livewire component depends on these policies. The next step is to build the middleware pipeline that applies these role checks.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [middleware-pipeline.md](middleware-pipeline.md) | `CheckRoleMiddleware` uses roles from this spec, `BasePolicy.before()` auto-allows super_admin |

## Quick References

- `app/Core/Policies/BasePolicy.php` — Base policy with super admin bypass
- `app/Core/Policies/Concerns/AuthorizesRoles.php` — Role-checking trait
- `app/Core/Policies/Concerns/AuthorizesOwnership.php` — Ownership-checking trait
- `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php` — Route-level role check
- `docs/architecture/policy-pattern.md` — Architecture pattern documentation
- `docs/foundation/rbac.md` — Role definitions and capabilities
- `docs/adr/adr-flat-rbac-with-functional-roles.md` — ADR for flat role model
- `docs/adr/adr-cross-role-proxy.md` — Cross-role proxy protocol
- `docs/specs/authentication.md` — Login and session lifecycle
