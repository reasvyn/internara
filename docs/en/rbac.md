# Access Control

Internara uses **role-based access control (RBAC)** powered by `spatie/laravel-permission`.

## Two Role Families

Roles are categorized into two families, each serving a distinct purpose:

| Family | Role | Used for |
|--------|------|----------|
| **User Role** | `super_admin` | Route middleware, authentication, permission assignment |
| **User Role** | `admin` | Route middleware, authentication, permission assignment |
| **User Role** | `teacher` | Route middleware, authentication, permission assignment |
| **User Role** | `student` | Route middleware, authentication, permission assignment |
| **User Role** | `supervisor` | Route middleware, authentication, permission assignment |
| **Functional Role** | `admin` (contextual) | Business logic grouping — resolves to `super_admin` + `admin` |
| **Functional Role** | `mentor` | Business logic grouping — resolves to `teacher` + `supervisor` |
| **Functional Role** | `mentee` | Business logic grouping — resolves to `student` |

### When to Use Each Family

| Context | Use | Example |
|---------|-----|---------|
| **Route middleware** | User Roles only | `->middleware(['auth', 'role:teacher'])` |
| **Policy/Gate checks** | User Roles | `$user->hasRole('super_admin')` |
| **Permission assignment** | User Roles only | `$user->assignRole('student')` |
| **DB queries scoping** | User Roles | `User::role('supervisor')->get()` |
| **Business logic in Actions** | Functional Roles preferred | `$role->is(Role::MENTOR)` |
| **Feature gating by phase** | Functional Roles | `Period::current()->participants(Role::MENTEE)` |
| **Dashboard/route redirect** | Functional Roles | `getDashboardForUser()` returns per-functional-role |
| **Entity/business rules** | Functional Roles | `MentorRole::canVerifySupervisionLog()` |

### Key Principle

> **Functional Roles never appear in route middleware.** They are logical groupings resolved at runtime. Route security always uses concrete User Roles.

```php
// ✅ CORRECT — route uses User Roles
Route::prefix('teacher')
    ->middleware(['auth', 'role:teacher'])
    ->group(fn () => /* ... */);

// ✅ CORRECT — business logic uses Functional Roles
if (Role::functionalRolesFor($user->role())->contains(Role::MENTOR)) {
    // mentor-specific logic
}

// ❌ WRONG — never use Functional Roles in middleware
// ->middleware(['auth', 'role:mentor'])  // 'mentor' is not a DB role
```

## Role Definitions

| User Role | Domain | Functional Role | Purpose |
|-----------|--------|-----------------|---------|
| `super_admin` | Admin | Admin | System infrastructure, global configuration, user lifecycle |
| `admin` | Admin | Admin | School-level management |
| `teacher` | Mentor | Mentor | Academic supervision and assessment |
| `student` | Mentee | Mentee | Internship participants |
| `supervisor` | Mentor | Mentor | Industry supervisors and evaluation |

Role labels are translatable via language files.

## Enforcement Layers

| Layer | Mechanism |
|-------|-----------|
| Routes | Middleware: `->middleware(['auth', 'role:super_admin|admin'])` |
| Livewire | Policy, Gate checks, or `boot()` authorization before mutations |
| Actions | Authority verification over target data |
| Policies | Policy classes using shared `BasePolicy` with `AuthorizesRoles` and `AuthorizesOwnership` traits |

Users with the `super_admin` role bypass all Gate checks via `Gate::before`.

## Enum Reference

Both role families are defined in `App\Enums\Auth\Role`:

```php
// User Roles
Role::SUPER_ADMIN  // 'super_admin'
Role::ADMIN        // 'admin'
Role::TEACHER      // 'teacher'
Role::STUDENT      // 'student'
Role::SUPERVISOR   // 'supervisor'

// Functional Roles
Role::MENTOR       // 'func_mentor' → resolves to [teacher, supervisor]
Role::MENTEE       // 'func_mentee' → resolves to [student]

// Grouping methods
Role::userRoles()        // all 5 user roles
Role::functionalRoles()  // [admin, mentor, mentee]
$role->isUserRole()      // true for user roles, false for functional
$role->isFunctionalRole()
$role->resolvesTo()      // underlying user roles
Role::functionalRolesFor($userRole)  // e.g., TEACHER → [MENTOR]
$role->is(Role::MENTOR)  // true if this role resolves to MENTOR
```

## Account Lifecycle

User accounts follow a state machine with 8 statuses (PROVISIONED → ACTIVATED → VERIFIED → [RESTRICTED | SUSPENDED | INACTIVE] → ARCHIVED, with PROTECTED as an immutable status for super admins). See [Account Lifecycle](lifecycles/account-lifecycle.md) for the full state definitions and transition rules.

## Security Principles

- **IDOR protection** — every request verifies ownership of the target resource
- **Least privilege** — users receive only the permissions required for their role
- **Audit trail** — all role and permission changes are logged
