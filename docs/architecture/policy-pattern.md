# Policy Pattern

> **Last updated:** 2026-06-10

Authorization reference for the Internara codebase. Describes the Flat RBAC model, the three-layer
authorization stack, the `BasePolicy` contract, policy traits, auto-discovery, and the complete
policy inventory across all modules.

See also:
- [RBAC Foundation](../foundation/rbac.md) — authentication flow & role definitions
- [ADR-008: Flat RBAC with Functional Roles](../adr/adr-flat-rbac-with-functional-roles.md)
- [Modular Pattern Reference](modular-pattern.md)
- [Coding Conventions §9](../conventions.md) — Policy conventions

---

## 1. Flat RBAC — 5 User Roles + 2 Functional Roles

The system uses **flat** (non-hierarchical) roles. Each role has explicitly enumerated capabilities.
No role inherits permissions from another — adding a permission to one role never leaks to another.

### User Roles (Stored in Database)

| Role | Code (DB) | Scope | Description |
|------|-----------|-------|-------------|
| **Super Admin** | `superadmin` | Global | Bypasses all gates. Manages system settings, all accounts, all data. |
| **Admin** | `admin` | School | Manages users, programs, companies, departments, announcements, audit logs. |
| **Teacher** | `teacher` | School | Academic supervision: journal review, assignment grading, site visits, grade cards. |
| **Supervisor** | `supervisor` | Company | Industry supervision: attendance verification, journal review, competency evaluation. |
| **Student** | `student` | Self | Program participation: attendance, logbooks, assignments, certificate download. |

> **DB mapping note:** spatie/laravel-permission stores `superadmin` (no underscore). All User model
> methods (`hasRole`, `hasAnyRole`) transparently normalize `super_admin` → `superadmin` before
> delegating to the package. Use `super_admin` everywhere in application code; never reference
> `superadmin` directly. See `app/User/Models/User.php:55-80`.

### Functional Roles (Derived, Not Stored)

Functional roles exist for business logic only. They are resolved at runtime via
`Role::resolvesTo()` — never stored in the database, never used in route middleware.

| Functional Role | Code | Resolves From | Purpose |
|-----------------|------|--------------|---------|
| `mentor` | `func_mentor` | `teacher`, `supervisor` | Anyone who supervises students |
| `mentee` | `func_mentee` | `student` | Anyone being supervised |

Decouples the mentoring subsystem from specific user types. See
`app/Auth/Permissions/Enums/Role.php:50-58`:

```php
public function resolvesTo(): array
{
    return match ($this) {
        self::ADMIN => [self::SUPER_ADMIN, self::ADMIN],
        self::MENTOR => [self::TEACHER, self::SUPERVISOR],
        self::MENTEE => [self::STUDENT],
        default => [$this],
    };
}
```

---

## 2. Three-Layer Authorization

Authorization is enforced at three independent levels for defense in depth.

### Layer 1 — Routes (`CheckRoleMiddleware`)

Route-level role gating via `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php`. Applied
to route groups using the pipe-delimited `role:` syntax:

```php
Route::middleware(['role:super_admin|admin'])->group(function () {
    Route::resource('users', UserController::class);
});
```

After authentication, checks the user has **at least one** of the required roles. Returns 403 for
unauthorized authenticated users, redirects to login for guests. Logs all unauthorized access
attempts.

### Layer 2 — Livewire (Component Authorization)

Livewire components call `$this->authorize()` inline in their methods:

```php
public function delete(string $id): void
{
    $this->authorize('delete', Internship::class);
    // ...
}
```

Uses Laravel's built-in `AuthorizesRequests` trait that delegates to the registered policy.

### Layer 3 — Policies (`BasePolicy`)

All policies extend `App\Core\Policies\BasePolicy` and define granular `view`/`create`/`update`/
`delete` methods using the `AuthorizesRoles` and `AuthorizesOwnership` traits.

---

## 3. Gate::before Bypass for Super Admin

Super Admin bypasses all authorization checks through a `Gate::before` callback. Two registrations
exist:

### Production (`config/permission.php`)

```php
'register_permission_check_method' => true,
```

spatie/laravel-permission auto-registers a `Gate::before` callback. For users with
`superadmin` role, it returns `true` (grant). For all others, `null` ("let the policy decide").

### Test (`tests/TestCase.php:29-31`)

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super_admin') ? true : null;
});
```

Explicitly registered in the test base class because spatie's auto-discovery may not fire in certain
test configurations.

### Effect

Super Admin is **not** a role with "all permissions" in the database — it skips the permission
system entirely. This is more efficient and eliminates the risk of accidentally omitting a
permission.

---

## 4. BasePolicy Contract

**File:** `app/Core/Policies/BasePolicy.php`

```php
abstract class BasePolicy
{
    use AuthorizesOwnership;
    use AuthorizesRoles;

    public function before(Model $user): ?Response
    {
        if ($user->hasRole('super_admin')) {
            return Response::allow();
        }
        return null;
    }

    protected function allowIfAdmin(Model $user): Response;
    protected function allowIfAdminOrTeacher(Model $user): Response;
    protected function allowIfOwner(Model $user, Model $model, string $foreignKey = 'user_id'): Response;
}
```

### `before()` — Super Admin Short-Circuit

Every policy inherits a `before()` method that allows `super_admin` users unconditionally. Returns
`Response::allow()` for super admins, `null` for everyone else (delegates to the specific policy
method).

### Convenience Response Wrappers

| Method | API | Description |
|--------|-----|-------------|
| `allowIfAdmin()` | `→ Response` | Returns `allow()` if `isAdmin()`, else `deny()` |
| `allowIfAdminOrTeacher()` | `→ Response` | Returns `allow()` if `isAdminOrTeacher()`, else `deny()` |
| `allowIfOwner()` | `→ Response` | Returns `allow()` if `isOwner()`, else `deny()` |

These return `Illuminate\Auth\Access\Response` objects suitable for policy methods that use
`Response` return types instead of `bool`.

> [!IMPORTANT]
> All policy methods must mark their parameter types explicitly (`User $user`, `Model $model`).
> Laravel resolves the authenticated user as the first argument — use `?User $user` type-hints for
> guest-accessible methods.

---

## 5. AuthorizesRoles Trait

**File:** `app/Core/Policies/Concerns/AuthorizesRoles.php`

Provides role-checking methods that reduce duplication of
`$user->hasAnyRole(['super_admin', 'admin'])` across all policy classes.

```php
trait AuthorizesRoles
{
    protected function isAdmin(Model $user): bool;        // super_admin | admin
    protected function isTeacher(Model $user): bool;       // teacher only
    protected function isStudent(Model $user): bool;       // student only
    protected function isSupervisor(Model $user): bool;    // supervisor only
    protected function isAdminOrTeacher(Model $user): bool;// super_admin | admin | teacher
    protected function canManageAnyRole(Model $user): bool;// alias for isAdmin
    protected function hasAnyOfRoles(Model $user, array $roles): bool; // generic check
}
```

### Usage Pattern

| Pattern | Example |
|---------|---------|
| **Single role** | `return $this->isAdmin($user);` |
| **Composite role list** | `return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);` |
| **Owner or role** | `return $this->isAdmin($user) \|\| this->isOwner($user, $model);` |

Always write role checks against the conceptual role (`super_admin`), not the stored value
(`superadmin`). The User model handles normalization.

---

## 6. AuthorizesOwnership Trait

**File:** `app/Core/Policies/Concerns/AuthorizesOwnership.php`

```php
trait AuthorizesOwnership
{
    protected function isOwner(
        Model $user,
        Model $model,
        string $foreignKey = 'user_id',
    ): bool;

    protected function isRelatedThrough(
        Model $user,
        Model $model,
        string $relation,
        string $foreignKey = 'id',
    ): bool;

    protected function isOwnerOrAdmin(
        Model $user,
        Model $model,
        string $foreignKey = 'user_id',
    ): bool;
}
```

| Method | Description |
|--------|-------------|
| `isOwner()` | Direct foreign key match: `$model->{$foreignKey} === $user->id` |
| `isRelatedThrough()` | Ownership through a relation: `$model->relation->foreignKey === $user->id` |
| `isOwnerOrAdmin()` | Composite: owner OR admin (uses `isAdmin()` from `AuthorizesRoles`) |

### Examples

```php
// Direct ownership (default foreign key = user_id)
$this->isOwner($user, $entry);

// Custom foreign key
$this->isOwner($user, $request, 'requested_by');

// Ownership through a relation
$this->isRelatedThrough($user, $registration, 'mentor');

// Owner or admin shortcut
$this->isOwnerOrAdmin($user, $profile);
```

---

## 7. Policy Auto-Discovery

**File:** `app/Providers/AppServiceProvider.php:143-203`

Policies are **auto-discovered** at boot time — there is no manual `$policies` array in
`AuthServiceProvider`.

### How Discovery Works

1. Scans all PHP files under `app/` for directories named `Policies/` (skips `Concerns/`,
   `Traits/`)
2. Filters to classes whose name ends in `Policy` and extend `BasePolicy`
3. Derives the model class by convention:
   - `app/{Module}/{Submodule}/Policies/{Name}Policy.php`
     → `App\{Module}\{Submodule}\Models\{Name}`
   - `app/{Module}/Policies/{Name}Policy.php`
     → `App\{Module}\Models\{Name}`
4. Skips policies whose inferred model class does not exist
5. Registers via `Gate::policy($modelClass, $policyClass)`
6. Results are **cached for 24 hours** (keys: `module_policies`)

### Exception: User Policy

The `UserPolicy` is registered **explicitly** because the User model lives in a different namespace
structure:

```php
// AppServiceProvider.php:67
Gate::policy(User::class, UserPolicy::class);
```

### Re-discovering After Adding a Policy

```bash
php artisan cache:forget module_policies
```

Or run the console command that triggers re-discovery:

```bash
php artisan module:discover
```

---

## 8. Testing Policies

### Test File Locations

Policy tests follow the module-first test convention:

| Test | File |
|------|------|
| BasePolicy unit tests | `tests/Unit/Core/Policies/BasePolicyTest.php` |
| Module policy tests | `tests/Unit/{Module}/Policies/{Name}PolicyTest.php` |

### BasePolicy Unit Tests (`tests/Unit/Core/Policies/BasePolicyTest.php`)

Tests the two traits and convenience wrappers using mock models:

```php
class AdminUser extends Model
{
    public function hasAnyRole(...$roles): bool
    {
        return true;
    }
}
```

Covers: `isAdmin`, `isOwner`, `isOwnerOrAdmin`, `isRelatedThrough` — both positive and negative
cases.

### Module Policy Tests

Test policies by instantiating them directly with mock User models that override `hasRole` /
`hasAnyRole`:

```php
// tests/Unit/Settings/Policies/SettingPolicyTest.php
test('admin can view settings', function () {
    $user = new class extends User {
        public function hasRole($roles, ?string $guard = null): bool {
            return $roles === 'admin';
        }
        public function hasAnyRole(...$roles): bool {
            return true;
        }
    };
    $user->id = 1;

    expect((new SettingPolicy)->viewAny($user))->toBeTrue();
});
```

No database needed — override the spatie `hasRole`/`hasAnyRole` methods on anonymous subclasses.
This keeps policy tests fast and isolated.

### Testing Super Admin Bypass

The `Gate::before` callback is registered in `tests/TestCase.php`. Feature tests that perform
actions as super admin users automatically bypass all policy checks.

---

## 9. Complete Policy Inventory

### Convention Summary

| Aspect | Convention |
|--------|-----------|
| **Base class** | `BasePolicy` (in `app/Core/Policies/`) |
| **Traits** | `AuthorizesRoles`, `AuthorizesOwnership` |
| **Location** | `app/{Module}/{Submodule}/Policies/{Name}Policy.php` |
| **Naming** | `{Model}Policy` — matches model name in sibling `Models/` directory |
| **Registration** | Auto-discovered by `AppServiceProvider::discoverPolicies()` |
| **Return types** | `bool` (most common), `Response` (for custom deny messages) |
| **User type-hint** | `User $user` for authenticated-required, `?User $user` for guest-accessible |
| **Super admin** | Handled by `BasePolicy::before()` — individual policies never check `super_admin` |
| **ViewAny/view** | Often open (`return true`) for read-only resources; role-gated for sensitive data |

### Policy Table

| # | Policy | Module | Submodule | Resource | Methods | Who Can View? | Who Can Create? | Who Can Update? | Who Can Delete? | Notes |
|---|--------|--------|-----------|----------|---------|---------------|-----------------|-----------------|-----------------|-------|
| 1 | `UserPolicy` | Auth | Permissions | User | viewAny, view, viewAdmin, create, update, delete, restore, forceDelete | self & admin | admin | admin & self | admin (not self, not super_admin) | Super admin immutable: no one can delete super_admin |
| 2 | `ProfilePolicy` | User | Profile | Profile | viewAny, view, update | admin | — | admin & self | — | No create/delete — profiles auto-created |
| 3 | `NotificationPolicy` | User | Notifications | Notification | viewAny, view, create, update, delete | all | admin | owner only | admin | |
| 4 | `SettingPolicy` | Settings | — | Setting | viewAny, view, create, update, delete | admin | super_admin only | super_admin only | super_admin only | Most restrictive policy |
| 5 | `AcademicYearPolicy` | Academics | AcademicYear | AcademicYear | viewAny, view, create, update, activate, delete | all | admin | admin | super_admin only | Activate: super_admin only |
| 6 | `DepartmentPolicy` | Academics | Department | Department | viewAny, view, create, update, delete, forceDelete | all | admin | admin | admin (if canBeDeleted) | Business rule via `DepartmentState::canBeDeleted()` |
| 7 | `InternshipPolicy` | Program | Internship | Internship | viewAny, view, create, update, delete, forceDelete | all | admin | admin | admin (no placements/registrations) | Super admin can forceDelete |
| 8 | `InternshipPhasePolicy` | Program | InternshipPhase | InternshipPhase | viewAny, view, create, update, delete | all | admin | admin | super_admin only | |
| 9 | `InternshipGroupPolicy` | Program | InternshipGroup | InternshipGroup | viewAny, view, create, update, delete | all | admin | admin | admin | |
| 10 | `RegistrationPolicy` | Enrollment | Registration | Registration | viewAny, view, create, update, approve, delete | all | student | admin & owner (if pending) | admin & owner (if pending) | `approve`: admin only |
| 11 | `RegistrationDocumentPolicy` | Enrollment | Registration | RegistrationDocument | viewAny, view, create, update, delete | admin | student | admin | admin | |
| 12 | `PlacementPolicy` | Enrollment | Placement | Placement | viewAny, view, create, update, delete | admin | admin | admin | admin (no directPlacements) | |
| 13 | `PlacementChangeRequestPolicy` | Enrollment | Placement | PlacementChangeRequest | viewAny, view, create, update, delete | admin, teacher, student | student | admin | admin | |
| 14 | `AccountApplicationPolicy` | Enrollment | AccountApplication | AccountApplication | viewAny, view, create, update, delete | admin | all (unauthenticated) | admin | admin | `create`: truly public |
| 15 | `CompanyPolicy` | Partners | Company | Company | viewAny, view, create, update, delete, forceDelete | all | admin | admin | admin (no placements) | Super admin forceDelete |
| 16 | `PartnershipPolicy` | Partners | Partnership | Partnership | viewAny, view, create, update, delete | admin & teacher | admin | admin | admin | |
| 17 | `EvaluationPolicy` | Evaluation | — | Evaluation | viewAny, view, create, update, delete | all | all | admin & evaluator | admin | |
| 18 | `AssessmentPolicy` | Assessment | — | Assessment | viewAny, view, create, update, finalize, delete | admin & teacher | admin & teacher | evaluator (not finalized) | admin (not finalized) | Students see own via registration |
| 19 | `AssignmentPolicy` | Assignment | — | Assignment | viewAny, view, create, update, publish, delete | all | admin & teacher | admin & teacher | admin (no submissions) | |
| 20 | `SubmissionPolicy` | Assignment | Submission | Submission | viewAny, view, create, update, verify, delete | admin & teacher | student | owner (if submitted) | admin | `verify`: admin & teacher |
| 21 | `LogbookPolicy` | Journals | Logbook | Logbook | viewAny, view, addSupervisorNote, create, update, delete | all | student | admin & owner (not submitted) | admin & owner (not submitted) | Mentor-scoped view |
| 22 | `AttendancePolicy` | Journals | Attendance | Attendance | viewAny, view, create, verify, update, delete | all | student | admin | admin | Mentor-scoped view |
| 24 | `SupervisionLogPolicy` | Guidance | SupervisionLog | SupervisionLog | viewAny, view, create, update, verify, delete | admin, teacher, supervisor | teacher & supervisor | admin & supervisor (not verified) | admin & supervisor (not verified) | `verify`: admin & teacher |
| 25 | `IncidentReportPolicy` | Incident | IncidentReport | IncidentReport | viewAny, view, create, update, delete | admin, teacher, supervisor | all (unauthenticated) | admin | admin | Anyone can report |
| 26 | `DocumentPolicy` | Document | — | Document | viewAny, view, create, update, delete | admin, teacher, student | admin | admin | admin | Active documents visible to all |
| 27 | `CertificatePolicy` | Certification | Certificate | Certificate | viewAny, view, create, update, delete, revoke | admin & student | admin | `false` | `false` | Immutable after creation; revoke instead |
| 28 | `CertificateTemplatePolicy` | Certification | Certificate | CertificateTemplate | viewAny, view, create, update, delete | admin | admin | admin | admin | |
| 29 | `GdprDeletionLogPolicy` | SysAdmin | Observability/GdprDeletionLog | GdprDeletionLog | viewAny, view, create | admin | admin | — | — | Append-only log |

### Recurring Patterns

| Pattern | Example |
|---------|---------|
| **Read-only for all** | `viewAny()` / `view()` return `true` across all roles |
| **Admin-only mutations** | `create/update/delete` use `$this->isAdmin($user)` |
| **Super admin escalation** | `forceDelete()`, `activate()` use `$user->hasRole('super_admin')` |
| **Owner checks** | `$this->isOwner($user, $model)` or direct `$model->user_id === $user->id` |
| **Mentor-scoped view** | Logbook, Attendance, SupervisionLog: check mentor relationship via `registration->mentors()` |
| **Business rule guard** | Delete blocked if related records exist: `! $assignment->submissions()->exists()` |
| **Immutable after state** | Submitted/verified entries cannot be modified: `$entry->status !== 'submitted'` |
| **Open create** | `create()` returns `true` for public-facing forms (AccountApplication, IncidentReport) |

### Adding a New Policy

1. Create `app/{Module}/{Submodule}/Policies/{Name}Policy.php` extending `BasePolicy`
2. Ensure the sibling model exists at `App\{Module}\{Submodule}\Models\{Name}`
3. Clear the auto-discovery cache: `php artisan cache:forget module_policies`
4. Write tests in `tests/Unit/{Module}/Policies/{Name}PolicyTest.php`
