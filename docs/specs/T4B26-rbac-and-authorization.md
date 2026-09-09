# RBAC & Authorization — Role-Based Access Control

> **Spec ID:** T4B26

## Description

Defines the flat role-based access control system: 5 concrete user roles plus 3 runtime-derived
functional roles resolved via `Role::resolvesTo()`, a three-layer authorization stack (route
middleware → Livewire → policy), the `BasePolicy` contract with role and ownership traits, the
super-admin bypass, policy auto-discovery, and the auditable Cross-Role Proxy delegated to
`MentorEntity`. This spec is the authoritative source for all authorization decisions in Internara.

---

## 1. Problem Statements

### PS-1 — Authorization Scattered Across Layers

Without a structured RBAC system, authorization checks are ad-hoc: some in routes, some in
Livewire components, some in Actions. This inconsistency leads to security gaps where certain
endpoints have no authorization at all.
**→ Requirement:** FR-RBAC-007/008/009 (three enforced layers).

### PS-2 — Super Admin Lockout Risk

If super-admin authorization is not handled before policy evaluation, a misconfigured policy could
lock out the only account capable of fixing the system.
**→ Requirement:** FR-RBAC-010/011 (bypass allow plus null-continuation).

### PS-3 — Role Explosion Without Hierarchy

Adding new roles without a clear framework leads to exponential permission combinations. A flat
role model with explicit capabilities per role — plus runtime functional groupings instead of
inheritance — prevents this.
**→ Requirement:** FR-RBAC-001/002 (flat roles, one per user), FR-RBAC-004/005 (derived groupings).

### PS-4 — Asymmetric Fieldwork Needs

Industry supervisors may not log in for days, yet student workflows (logbook verification,
assessments, supervision logs) cannot wait — and the school bears ultimate responsibility for
outcomes. Granting teachers a second role would unlock every supervisor feature and pollute the
audit trail; the need is directed, per-method delegation with full audit context.
**→ Requirement:** FR-RBAC-016/017/018 (proxy hierarchy and scope), FR-RBAC-021 (audit).

---

## 2. Goals & Non-Goals

### Goals

- **Every protected endpoint guarded** — at least one of the three authorization layers on every route, component, and policy method. *Why:* a single missing check is a security gap; defense in depth makes gaps visible at review.
- **Super-admin bypass** — `super_admin` short-circuits all checks before policy evaluation. *Why:* the recovery account must survive any misconfigured policy.
- **Flat roles, no inheritance** — each role owns exactly its permissions; no silent leakage through a hierarchy. *Why:* explicit lists are auditable; Indonesian vocational schools have crisp role boundaries.
- **Reusable ownership checks** — `isOwner()` family shared via the `AuthorizesOwnership` trait. *Why:* ownership logic duplicated per policy drifts; one trait keeps student-owns-own-data consistent.
- **Policy auto-discovery** — `Policies/` directories scanned with cached results instead of manual registration. *Why:* 19 modules mean dozens of policies; manual registration rots on the first forgotten entry.
- **Runtime functional roles** — `admin-group`, `mentor`, and `mentee` derived via `Role::resolvesTo()`, never stored. *Why:* teacher and supervisor both mentor; one mapping update beats an `||` in every policy.
- **Auditable cross-role proxy** — teachers act for supervisors (admins for both) with `proxy_role`/`proxy_reason` in the activity log. *Why:* fieldwork cannot stall on supervisor inactivity, but every proxied act must trace to actor and stead.

### Non-Goals

- **Hierarchical role inheritance**. *Why:* Admin-inherits-Teacher chains leak permissions silently; flat roles with functional groupings cover the need explicitly.
- **Granular permission-per-action model**. *Why:* `user.create`-style permission sprawl is ceremony the five-role domain does not need.
- **Role-based UI rendering**. *Why:* handled by Blade directives at the presentation layer, not by this authorization contract.
- **Multi-tenancy role isolation**. *Why:* single-tenant by product definition; one school per instance needs no tenant-scoped roles.
- **API token scoping**. *Why:* handled by access tokens, outside the role/policy stack.
- **Multi-role assignment (one role per user)**. *Why:* rejected by the cross-role-proxy ADR — second roles cause audit confusion, workload obfuscation, policy duplication, scope creep, and pivot pollution; proxy covers the need without them.

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-RBAC-001 | Admin assigns a role to a user and the new permissions apply on the next request | P0 | F | Full |
| UC-RBAC-002 | Teacher accesses supervision resources limited to mentored students | P0 | F | Full |
| UC-RBAC-003 | Student views own profile and nothing else | P0 | F | Full |
| UC-RBAC-004 | Super admin bypasses all authorization checks | P0 | U | Full |
| UC-RBAC-005 | Teacher verifies a logbook entry on behalf of an inactive supervisor via proxy | P0 | F | Full |

### 3.1 Authorization Journeys

#### UC-RBAC-001 — Admin Assigns Role to User

**Actor:** Admin / Super Admin
**Preconditions:** Target user exists; assigner holds admin or super-admin role.
**Flow:**
1. Admin navigates to user management
2. Selects the target user, chooses a role from the dropdown
3. Action validates the role transition and persists it
4. Spatie permission updated; the user's cached roles invalidated (FR-RBAC-015)
**Postconditions:** User can access resources permitted for the new role on the next request.
**Governing guidance:** FR-RBAC-001 (role set), FR-RBAC-015 (cache invalidation).

#### UC-RBAC-002 — Teacher Accesses Supervision Resources

**Actor:** Teacher
**Preconditions:** Teacher is assigned as mentor to an internship group.
**Flow:**
1. Teacher navigates to the supervision log page
2. `CheckRoleMiddleware` verifies the `teacher` role
3. `SupervisionLogPolicy::viewAny()` checks mentor relationship via `MentorEntity`
4. Component renders supervised students' data only
**Postconditions:** Teacher sees only mentored students' data.
**Governing guidance:** FR-RBAC-007 (middleware), FR-RBAC-009 (policy), FR-RBAC-006 (bridge).

#### UC-RBAC-003 — Student Views Own Profile

**Actor:** Student
**Preconditions:** Student is authenticated.
**Flow:**
1. Student navigates to the profile page
2. `CheckRoleMiddleware` verifies the `student` role
3. `ProfilePolicy::view()` checks `isOwner()` against the authenticated user
4. Component renders own profile data
**Postconditions:** Student sees only own data.
**Governing guidance:** FR-RBAC-012 (ownership trait).

#### UC-RBAC-004 — Super Admin Bypasses All Checks

**Actor:** Super Admin
**Preconditions:** Authenticated as super-admin.
**Flow:**
1. Super admin accesses any protected endpoint
2. `BasePolicy::before()` returns `Response::allow()` immediately
3. No further policy checks execute
**Postconditions:** Full access to all resources.
**Governing guidance:** FR-RBAC-010/011; unit-verified without DB beyond the model instance.

#### UC-RBAC-005 — Teacher Proxies a Supervisor Verification

**Actor:** Teacher (as proxy for supervisor)
**Preconditions:** Teacher mentors the student's registration; supervisor unreachable.
**Flow:**
1. Teacher opens the pending logbook entry
2. Policy delegates to `Registration::asMentorEntity()->canVerifyLogbook($teacher)`
3. `MentorEntity` confirms mentorship and grants the proxy gate
4. Verification persists with `proxy_role` + `proxy_reason` in `activity_log.properties`
**Postconditions:** Entry verified in the supervisor's stead with a complete audit trail.
**Governing guidance:** FR-RBAC-017 (teacher scope), FR-RBAC-021 (audit).

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch
(structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-RBAC-001 | Exactly five concrete user roles exist: `super_admin` (global recovery), `admin` (school management), `teacher` (academic supervision), `student` (self scope), `supervisor` (company supervision) | P0 | U | Full |
| FR-RBAC-002 | Every user holds exactly one role; multi-role assignment is forbidden | P0 | F | Full |
| FR-RBAC-003 | Role naming is normalized: code checks `super_admin` while Spatie stores `superadmin` (Role enum value) | P1 | U | Full |
| FR-RBAC-004 | Three functional roles are derived at runtime via `Role::resolvesTo()` / `Role::functionalRolesFor()`: admin-group ← super_admin + admin; mentor ← teacher + supervisor; mentee ← student — never stored in the DB, never used in route middleware | P0 | U | Full |
| FR-RBAC-005 | `$user->role->is(Role::ADMIN)` matches both super_admin and admin with no `||` branches in policies | P1 | U | Full |
| FR-RBAC-006 | Mentor/mentee relationships resolve through the `MentorEntity` bridge (`Registration::asMentorEntity()`) built from internship-group membership | P0 | U | Full |
| FR-RBAC-007 | Route layer: `CheckRoleMiddleware` (`role:...`) verifies concrete roles before execution, redirecting guests to login and returning 401 for JSON/Livewire versus 403 for forbidden | P0 | F | Full |
| FR-RBAC-008 | Livewire layer: components call `authorize()` so policy-driven visibility gates buttons (e.g. `@can('verify', $logbook)`); proxy UI shows an optional badge only when `session('proxy_mode')` is active | P0 | F | Full |
| FR-RBAC-009 | Policy layer: every policy extends `BasePolicy` (composing `AuthorizesRoles` + `AuthorizesOwnership`); supervisor-scoped methods delegate to `MentorEntity` | P0 | A | Full |
| FR-RBAC-010 | `BasePolicy::before()` MUST return `Response::allow()` for the `super_admin` role with no permission lookup | P0 | U | Full |
| FR-RBAC-011 | `BasePolicy::before()` MUST return `null` for all other roles so evaluation continues | P0 | U | Full |
| FR-RBAC-012 | `AuthorizesOwnership` MUST provide `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()` | P0 | U | Full |
| FR-RBAC-013 | `AuthorizesRoles` MUST provide `isAdmin()`, `canManageAnyRole()`, `hasAnyOfRoles()` for admin-only gates (mentor-adjacent helpers delegate to `MentorEntity`) | P0 | U | Full |
| FR-RBAC-014 | Policy auto-discovery MUST scan `Policies/` directories for `BasePolicy` subclasses, cache results for 24 hours, and invalidate on cache clear | P1 | F | Full |
| FR-RBAC-015 | `UserPolicy` MUST be registered manually in `AppServiceProvider` as the single exception to auto-discovery | P0 | A | Full |
| FR-RBAC-016 | Role assignment MUST invalidate the user's cached roles before the next request | P0 | F | Full |
| FR-RBAC-017 | Proxy hierarchy: `admin` may proxy as `teacher` or `supervisor` on any record; `teacher` may proxy as `supervisor` only for students in that teacher's mentorship; `supervisor` and `student` have no proxy capability | P0 | U | Full |
| FR-RBAC-018 | Policies delegate proxy decisions to `MentorEntity` via `Registration::asMentorEntity()` (`canProxyAsSupervisor`, `canProxyAsTeacher`, `canVerifyLogbook`, `canScoreCompetency`, `canReviewSupervisionLog`, `canGradeSubmission`) | P0 | U | Full |
| FR-RBAC-019 | Proxy activation for teachers requires supervisor inactivity of a configurable window stored in settings key `journals.proxy_inactivity_hours` (default `48`) | P1 | F | Planned |
| FR-RBAC-020 | Proxy coverage spans logbook verification, assessment scoring and finalization, supervision-log review, and submission grading — each domain adopts its path independently | P0 | F | Full |
| FR-RBAC-021 | Every proxy action MUST record `proxy_role` and `proxy_reason` in `activity_log.properties` (caused-by stays the acting user); no schema change, no new columns | P0 | F | Full |
| FR-RBAC-022 | Proxy is a runtime permission check, never a role expansion: `model_has_roles` unchanged, `BasePolicy::before()` stays super-admin-only, route middleware stays role-based | P0 | A | Full |

### 4.1 Flat User Roles

#### FR-RBAC-001 — Five concrete roles

- `Role::userRoles()` enumerates the five; `excludeSuperAdmin()` / `excludeAdmin()` support
  assignment dropdowns. No sixth role may appear without a spec amendment.
- **Verification:** enum unit test asserting the five-case set (layer `U`).

#### FR-RBAC-002 — One role per user

- Cross-role needs are met by proxy (FR-RBAC-016), never by stacking roles — the audit trail
  must always show one identity plus optional proxy context.
- **Verification:** assignment action test rejects a second role (layer `F`).

#### FR-RBAC-003 — Role-name normalization

- `Role::SUPER_ADMIN` carries value `'superadmin'` (the stored Spatie name); policy and
  middleware checks spell `super_admin`. The mismatch is contained in the enum plus the
  normalization both layers share — see §10 R-1.
- **Verification:** round-trip test stored-name ↔ checked-name (layer `U`).

### 4.2 Functional Roles

#### FR-RBAC-004 — Runtime derivation only

- `functionalRoles()` enumerates the functional set; `functionalRolesFor()` maps a concrete role
  at runtime. Route middleware uses concrete roles; functional roles evaluate at the policy layer.
- Exact map in §6.2; governance is the [flat-RBAC ADR](../adr/adr-flat-rbac-with-functional-roles.md).
- **Verification:** enum unit test over the full map (layer `U`).

#### FR-RBAC-005 — Admin-group check

- Shared admin permission checks collapse to one `is()` call; adding a member to the group edits
  the map, not every policy.
- **Verification:** unit test both members match (layer `U`).

#### FR-RBAC-006 — MentorEntity bridge

- `MentorEntity::fromModel()` bridges the registration record; role queries (`isTeacher`,
  `isSupervisor`, `isMentor`) read the mentors collection, unit-testable without DB.
- **Verification:** entity unit tests with fabricated collections (layer `U`).

### 4.3 Three Authorization Layers

#### FR-RBAC-007 — Route middleware

- Middleware takes concrete role params (`role:super_admin|admin`); functional roles never appear
  here. Denied access is logged via SmartLogger with PII masking.
- **Verification:** middleware feature test per role matrix (layer `F`).

#### FR-RBAC-008 — Livewire authorization

- No Livewire change is needed for proxy visibility — policy gates drive it; the explicit
  "Act as Supervisor" toggle with banner is optional and session-flagged.
- **Verification:** component tests asserting 403 vs render (layer `F`).

#### FR-RBAC-009 — Policy base contract

- `BasePolicy` is the single inheritance root; `AuthorizesRoles` mentor-adjacent helpers are
  deprecated in favor of `MentorEntity` proxy methods (`isAdmin()` and `hasAnyOfRoles()` remain
  for admin-only gates).
- **Verification:** class-contract scan asserting the extends chain (layer `A`).

#### FR-RBAC-010 — Super-admin bypass

- Bypass is a `Gate::before`-style early return (`BasePolicy::before()`), distinct from granting
  "all permissions" in the database — no lookup runs.
- **Verification:** policy unit test asserting allow without further evaluation (layer `U`).

#### FR-RBAC-011 — Null continuation

- Non-super-admin evaluation must fall through to the concrete ability method; an early deny here
  would silently close proxy paths.
- **Verification:** unit test asserting `null` for admin/teacher/student/supervisor (layer `U`).

### 4.4 Traits, Discovery, and Assignment

#### FR-RBAC-012 — Ownership trait

- `isOwner()` compares `user_id`; `isRelatedThrough()` follows a relation; `isOwnerOrAdmin()`
  unions ownership with the admin group.
- **Verification:** trait unit tests with stub models (layer `U`).

#### FR-RBAC-013 — Role trait (admin gates)

- Only admin-gate helpers stay on the trait; teacher/supervisor/mentor questions go through
  `MentorEntity` so mentorship scope is never bypassed by a bare role check.
- **Verification:** unit tests for the three retained helpers (layer `U`).

#### FR-RBAC-014 — Auto-discovery with cache

- Discovery is owned by the module infrastructure ([B114U](B114U-module-manager.md)); the 24-hour
  TTL uses registered cache keys.
- **Verification:** discovery feature test + TTL assertion (layer `F`).

#### FR-RBAC-015 — UserPolicy exception

- `Gate::policy(User::class, UserPolicy::class)` is registered manually because the user policy
  must exist before discovery runs.
- **Verification:** provider audit (layer `A`).

#### FR-RBAC-016 — Assignment invalidates cache

- Stale role cache after assignment is a privilege-escalation vector in reverse (or a
  lockout); invalidation is synchronous in the assignment path.
- **Verification:** assignment feature test asserting fresh roles next request (layer `F`).

### 4.5 Cross-Role Proxy

> Implements the proxy matrix in the [cross-role-proxy ADR](../adr/adr-cross-role-proxy.md).
> Applies to: logbook verification ([1KSWL](1KSWL-daily-activity.md)), assessment grading
> ([ARDA6](ARDA6-assessment.md)), and supervision-log verification ([2EHSE](2EHSE-supervision.md)).

#### FR-RBAC-017 — Hierarchy and scope

- Admin scope is global; teacher scope is bounded by `isTeacher($user)` on the registration's
  mentor set — a teacher never proxies for unassigned students.
- **Verification:** `MentorEntity` unit tests per hierarchy cell (layer `U`).

#### FR-RBAC-018 — Delegation pattern

- Policies contain the one-line delegation (`$entry->registration?->asMentorEntity()->... ?? false`);
  all branching lives in the entity, tested once.
- **Verification:** entity unit tests + policy feature tests (layer `U` + spot `F`).

#### FR-RBAC-019 — Inactivity window

- No `journals.proxy_inactivity_hours` key or inactivity check exists in code — proxy today is
  implicit via the policy gate. The window is specified here and stays `Planned` until the
  settings key plus the gate land; see §10 R-1.
- **Verification (pending):** settings-key test + gate test with stale-supervisor fixture.

#### FR-RBAC-020 — Per-domain coverage

- Each adopting domain wires its own policy method; domains never share proxy shortcuts. Priority
  order: logbook verify and assessment score/finalize first, supervision review and submission
  grading next.
- **Verification:** one feature test per proxy path (layer `F`).

#### FR-RBAC-021 — Audit properties

- `causedBy($user)` records the teacher who acted; `withProperties(['proxy_role' => ...,
  'proxy_reason' => ...])` records the stead and cause — distinguishable primary vs proxy in
  every log row.
- **Verification:** feature test asserting properties JSON on a proxied act (layer `F`).

#### FR-RBAC-022 — No role expansion

- What does not change: single role assignment, no pivot columns, super-admin-only `before()`,
  role-based route middleware. Proxy replaces inline role checks with one testable source.
- **Verification:** migration audit (no new columns) + contract scan (layer `A`).

---

## 5. Non-Functional Requirements

`Target` = `N/A` means the requirement is enforced structurally and verified via scans/tests
rather than a runtime measurement.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-RBAC-001 | Policy auto-discovery cache invalidates on cache clear so stale bindings never survive a deploy | N/A | P1 | F | Full |
| NFR-RBAC-002 | Role changes take effect on the next authenticated request | N/A | P0 | F | Full |
| NFR-RBAC-003 | Super-admin bypass holds even when a policy class is missing or broken (framework-level guarantee) | N/A | P0 | F | Planned |
| NFR-RBAC-004 | All policies and entities are unit-testable without a database beyond the model instance | N/A | P1 | U | Full |

### 5.1 Cache and Session Freshness

#### NFR-RBAC-001 — Discovery invalidation

- Deploy-time `config:clear` / `cache:forget` must flush discovered bindings; a stale policy map
  after deploy is a silent authorization regression.
- **Verification:** clear-then-rediscover feature test (layer `F`).

#### NFR-RBAC-002 — Next-request effect

- Follows from FR-RBAC-016; session or cache must never pin the old role past one request.
- **Verification:** assignment feature test (layer `F`).

### 5.2 Bypass Robustness

#### NFR-RBAC-003 — Bypass survives broken policies

- Today the bypass lives in `BasePolicy::before()`, which only runs when the policy class
  resolves — a missing policy class bypasses nothing. A framework-level `Gate::before` in the
  provider is specified but not yet implemented; status `Planned` (see §10 R-2).
- **Verification (pending):** missing-policy fixture test asserting super-admin allow.

### 5.3 Testability

#### NFR-RBAC-004 — No-DB policy and entity tests

- `MentorEntity` constructs from a mentors collection; policy helpers take model instances —
  pure logic, fast suite.
- **Verification:** existing entity unit tests run without DB (layer `U`).

---

## 6. API / Data Contracts

### 6.1 Role Enum

```php
enum Role: string implements LabelEnum
{
    case SUPER_ADMIN = 'superadmin';
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case SUPERVISOR = 'supervisor';

    case MENTOR = 'func_mentor';   // functional only
    case MENTEE = 'func_mentee';   // functional only

    public static function userRoles(): array;          // the five concrete roles
    public static function functionalRoles(): array;    // [ADMIN, MENTOR, MENTEE]
    public function resolvesTo(): array;                // §6.2 map
    public static function functionalRolesFor(self $userRole): array;
    public function is(self $functionalRole): bool;     // in_array($this, $functionalRole->resolvesTo())
    public function label(): string;
}
```

### 6.2 Functional Resolution Map

```php
// Role::resolvesTo()
SUPER_ADMIN → [ADMIN]
ADMIN       → [ADMIN]
TEACHER     → [MENTOR]
SUPERVISOR  → [MENTOR]
STUDENT     → [MENTEE]

// Role::functionalRolesFor()
SUPER_ADMIN, ADMIN      → [ADMIN]
TEACHER, SUPERVISOR     → [MENTOR]
STUDENT                 → [MENTEE]
```

### 6.3 Super-Admin Bypass

```php
abstract class BasePolicy
{
    use AuthorizesOwnership, AuthorizesRoles;

    public const SUPER_ADMIN = 'super_admin';

    public function before(Model $user): ?Response
    {
        if ($user->hasRole(self::SUPER_ADMIN)) {
            return Response::allow();
        }
        return null;
    }
}
```

### 6.4 Three Authorization Layers

| Layer | Mechanism | Example |
|-------|-----------|---------|
| Routes | CheckRoleMiddleware | `role:super_admin\|admin` |
| Livewire | `authorize()` in component | `$this->authorize('create', Model::class)` |
| Policies | BasePolicy traits | `isAdmin()`, `isOwner()` |

`BasePolicy` composes `AuthorizesRoles` and `AuthorizesOwnership`.

### 6.5 Trait Contracts

| Method | Returns | Logic |
|--------|---------|-------|
| `isAdmin($user)` | `bool` | `hasAnyRole(['super_admin', 'admin'])` — retained for admin-only gates |
| `canManageAnyRole($user)` | `bool` | `isAdmin()` — admin gate |
| `hasAnyOfRoles($user, array)` | `bool` | Any role in array matches |
| `isOwner($user, $model)` | `bool` | `$model->user_id === $user->id` |
| `isRelatedThrough($user, $model, $relation)` | `bool` | Follows relationship to check ownership |
| `isOwnerOrAdmin($user, $model)` | `bool` | `isOwner() \|\| isAdmin()` |

Role table:

| Role | Capabilities |
|------|-------------|
| `super_admin` | Full system access, bypasses all checks |
| `admin` | User management, system settings, announcements |
| `teacher` | Student supervision, grading, logbook review |
| `supervisor` | Company-side: attendance verification, evaluation |
| `student` | Own profile, logbook, attendance, submissions |

### 6.6 Proxy Hierarchy and Audit

```
Admin ── can proxy ──> Teacher ── can proxy ──> Supervisor
  │                           │
  └────── can proxy ──────────┘
```

| Acting User | Can Proxy As | Scope |
|-------------|--------------|-------|
| `admin` | `teacher`, `supervisor` | Any student in any program |
| `teacher` | `supervisor` | Only students assigned to that teacher's mentorship |
| `supervisor`, `student` | — | No proxy capability |

```php
// Policy delegation via MentorEntity
public function verify(User $user, Logbook $entry): bool
{
    return $entry->registration?->asMentorEntity()->canVerifyLogbook($user) ?? false;
}

// Audit trail — proxy metadata in properties JSON (no new columns)
activity()
    ->causedBy($user) // teacher who acted
    ->performedOn($model)
    ->withProperties(['proxy_role' => 'supervisor', 'proxy_reason' => 'supervisor_inactive'])
    ->event('verified')->log('logbook_verified_via_proxy');
```

---

## 7. Design Decisions

Decisions are recorded rationale, not test rows — `Layer`/`Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-RBAC-001 | Flat roles without hierarchy | P0 | — | — |
| DD-RBAC-002 | Three-layer authorization stack | P0 | — | — |
| DD-RBAC-003 | Super-admin bypass via before-hook | P0 | — | — |
| DD-RBAC-004 | Cross-role proxy at the application layer (no multi-role) | P0 | — | — |

### 7.1 Authorization Shape

#### DD-RBAC-001 — Flat Roles Without Hierarchy

**Decision:** Roles are flat — no role inherits permissions from another role.
**Rationale:** Hierarchical roles create invisible permission chains that are hard to audit.
Indonesian vocational schools have clear role boundaries (admin ≠ teacher ≠ student).
**Trade-off:** Some permissions are duplicated across roles (e.g., both admin and teacher can
view student data). Acceptable for clarity; the admin-group mapping removes the worst of it.

#### DD-RBAC-002 — Three-Layer Authorization Stack

**Decision:** Authorization is enforced at three layers: route middleware, Livewire component,
and policy gate.
**Rationale:** Defense in depth. Middleware prevents unauthorized route access. Livewire
authorization prevents component rendering. Policy gates protect individual methods.
**Trade-off:** Triple-checking adds minor overhead. Mitigated by the super-admin bypass and
framework-level caching.

#### DD-RBAC-003 — Super-Admin Bypass via Before-Hook

**Decision:** `BasePolicy::before()` returns `Response::allow()` for super_admin, bypassing all
subsequent checks.
**Rationale:** Super admin is the emergency recovery account. A misconfigured policy must never
lock out the only account that can fix the system.
**Trade-off:** Super admin cannot be restricted from specific actions. Acceptable because super
admin is a single-purpose recovery account. Framework-level hardening is NFR-RBAC-003.

#### DD-RBAC-004 — Cross-Role Proxy over Multi-Role

**Decision:** Directed runtime delegation via `MentorEntity`, not a second role on the user.
**Rationale:** Multi-role assignment causes audit confusion, workload obfuscation, policy
duplication, scope creep, and pivot pollution; the earlier "Dual Mentor Fallback" was too narrow
and misnamed. Proxy is scope-isolated, auditable, schema-free, and adoptable per domain.
**Trade-off:** One proxy clause per policy method plus a test per path — the cost of explicit
delegation. See the [cross-role-proxy ADR](../adr/adr-cross-role-proxy.md).

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Super-admin lockout incidents | 0 | Incident log |
| Authorization bypass vulnerabilities | 0 | `scan_security.py` + review |
| Endpoints without any auth layer | 0 | Route + component audit |
| Proxy actions missing audit properties | 0 | `activity_log.properties` audit |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following spec is **fully complete**:

| Spec | What It Provides |
|------|------------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | `BasePolicy`, `AuthorizesRoles`, `AuthorizesOwnership` traits |

### Build Guide

After implementing this spec, the system has role-based access control with 5 roles plus 3
derived functional roles, per-route middleware enforcement, ownership-based policy checks, and
per-domain proxy delegation. Every protected route and Livewire component depends on these
policies.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [middleware-pipeline.md](2CF4Y-middleware-pipeline.md) | `CheckRoleMiddleware` uses the concrete roles from this spec; `BasePolicy::before()` auto-allows super_admin |
| 2 | [authentication.md](YB7RG-authentication.md) | Login and session flows run inside the role model defined here |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If the `super_admin` check spelling and the `superadmin` stored value diverge in a new call site, then role checks fail closed and teachers/admins lose access — mitigated by keeping all spelling inside the `Role` enum plus FR-RBAC-003 round-trip test | Open | Maintainer | — |
| R-2 | If a policy class is missing or broken, then `BasePolicy::before()` never runs and super-admin loses its guarantee — mitigated by implementing the framework-level `Gate::before` (NFR-RBAC-003) | Open | Maintainer | — |
| A-1 | We assume one-role-per-user holds across all future domains, with proxy (not stacking) as the only cross-role mechanism | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Base classes](SE5Q9-base-classes.md) — `BasePolicy` and trait contracts this spec consumes
- [Module manager](B114U-module-manager.md) — owns policy auto-discovery mechanics
- [Authentication](YB7RG-authentication.md) — login inside this role model
- [Middleware pipeline](2CF4Y-middleware-pipeline.md) — applies the route-layer checks
- [Daily activity](1KSWL-daily-activity.md) — logbook proxy adopter
- [Assessment](ARDA6-assessment.md) — grading proxy adopter
- [Supervision](2EHSE-supervision.md) — supervision-log proxy adopter
- [ADR: Flat RBAC](../adr/adr-flat-rbac-with-functional-roles.md) — role model rationale
- [ADR: Cross-role proxy](../adr/adr-cross-role-proxy.md) — proxy matrix and audit contract
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — global requirements these rows serve
