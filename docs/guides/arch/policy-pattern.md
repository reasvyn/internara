# Policy Pattern — Authorization Gates, RBAC & Functional Roles

## Description

Authorization reference for the Internara codebase. Describes the Flat RBAC model, the three-layer
authorization stack, the `BasePolicy` contract, policy traits, auto-discovery, and the complete
policy inventory across all modules. Grounded in **RBAC** (NIST), **Principle of Least Privilege**,
**Defence in Depth**, and **Gate Pattern** — all mapped to Internara's Laravel RBAC stack.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Three-layer authorization — no bypass.** Authorization is enforced at three independent levels: route middleware (`CheckRoleMiddleware`), Livewire `authorize()`, and Policy gates. No single layer can be skipped. This is Defence in Depth.

2. **Super Admin bypass via `Gate::before`.** The `super_admin` role bypasses all authorization checks through a `Gate::before` callback. This is more efficient and eliminates the risk of accidentally omitting a permission. The bypass is registered in both production (`config/permission.php`) and tests.

3. **Flat RBAC — no role inheritance.** Roles have explicitly enumerated capabilities. No role inherits permissions from another — adding a permission to one role never leaks to another. This is NIST RBAC (no hierarchical roles).

4. **Functional roles are derived, not stored.** `admin-group` (super_admin + admin), `mentor` (teacher + supervisor), `mentee` (student) are resolved at runtime — never stored in the database, never used in route middleware.

5. **Policy auto-discovery — no manual registration.** Policies are auto-discovered at boot time. The only exception is `UserPolicy` (registered explicitly because the User model lives in a different namespace). Re-discover after adding a policy: `php artisan cache:forget module_policies && php artisan module:discover`.

6. **Policy tests use no database.** Policy tests use direct instantiation with mock User models that override `hasRole`/`hasAnyRole`, eliminating the need for a database. This keeps policy tests fast and isolated.

---

## How to Apply

### 1. RBAC (NIST)

NIST RBAC defines four basic components: Users, Roles, Permissions, and Sessions. In Internara:
- **Users** are authenticated via Laravel Fortify
- **Roles** are flat (5 user roles + 2 functional roles)
- **Permissions** are managed by spatie/laravel-permission v8
- **Sessions** are the authenticated user's active role context

**Reference:** [NIST RBAC](https://csrc.nist.gov/publications/detail/sp/800-162/final)

### 2. Principle of Least Privilege

Each role has only the permissions needed for its function:
- **Student** — read/write own data only (attendance, logbooks, assignments)
- **Teacher** — read/write school-scoped data (journal review, grading, site visits)
- **Supervisor** — read/write company-scoped data (attendance verification, competency evaluation)
- **Admin** — read/write school-wide data (users, programs, companies, departments)
- **Super Admin** — bypasses all checks (system-wide access)

### 3. Defence in Depth (OWASP)

Three independent layers ensure no single point of failure:

| Layer | Mechanism | Failure Mode |
|-------|-----------|-------------|
| Route middleware | `CheckRoleMiddleware` | Returns 403 / redirects to login |
| Livewire | `$this->authorize()` | Throws `AuthorizationException` |
| Policy | `Gate::before` + policy methods | Returns `Response::deny()` |

### 4. Gate Pattern

Laravel's Gate pattern provides a centralized authorization mechanism. `Gate::before` registers a callback that runs before all policy checks — this is where Super Admin bypass lives. Individual policy methods (`view`, `create`, `update`, `delete`) implement granular authorization.

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| Checking `$user->role === 'superadmin'` in code | Use `$user->hasRole('super_admin')` with normalization | Role string inconsistency |
| Storing functional roles in database | Derive at runtime from user roles | Functional roles not derived |
| Policy with 10+ methods | Split into domain-specific policies | God Object — too many responsibilities |
| Route without `role:` middleware | Add `role:teacher\|admin` to route group | Missing Layer 1 authorization |
| Policy test with database queries | Use mock User with overridden `hasRole` | Slow, coupled policy tests |
| `Gate::after` for authorization | Use `Gate::before` for bypass, policy methods for checks | Wrong hook point |
| Authorization only in Livewire, not in routes | Add route middleware + policy | Missing Defence in Depth |
| Hardcoded role checks (`$user->hasRole('superadmin')`) | Use `allowIfAdmin()` convenience method | Duplicated role logic |

---

## Quick References

- `docs/conventions.md` §Authorization Conventions — RBAC, functional roles
- `docs/guides/rbac.md` — complete RBAC reference
- `docs/adr/adr-flat-rbac-with-functional-roles.md` — ADR for flat RBAC
- `app/Modules/Core/Policies/BasePolicy.php` — BasePolicy contract
- `app/Modules/Core/Policies/Concerns/AuthorizesRoles.php` — role-checking trait
- `app/Modules/Core/Policies/Concerns/AuthorizesOwnership.php` — ownership-checking trait
- [NIST RBAC](https://csrc.nist.gov/publications/detail/sp/800-162/final) — RBAC standard
- [Principle of Least Privilege](https://en.wikipedia.org/wiki/Principle_of_least_privilege) — security principle
- [Laravel — Policies](https://laravel.com/docs/authorization#creating-policies) — policy registration
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction) — RBAC package
