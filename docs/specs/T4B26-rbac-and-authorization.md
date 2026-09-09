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

An admin correcting a mis-assigned teacher during enrollment week opens user management, picks the target user, chooses the new role from the dropdown, and the assignment path validates the transition, persists it, updates the Spatie permission, and invalidates the user's cached roles under FR-RBAC-015. On the very next request the user reaches exactly the new role's resources. That round trip exercises the role set in FR-RBAC-001 with the cache invalidation in FR-RBAC-015, proven by the assignment feature test.

#### UC-RBAC-002 — Teacher Accesses Supervision Resources

A supervising teacher opening the supervision log during visit season must see her mentees and nobody else's. `CheckRoleMiddleware` first verifies the `teacher` role, then `SupervisionLogPolicy::viewAny()` checks the mentor relationship through `MentorEntity`, and the component renders supervised students' data only. The journey binds FR-RBAC-007 for the middleware layer, FR-RBAC-009 for the policy layer, and FR-RBAC-006 for the bridge, proven by the supervision feature tests.

#### UC-RBAC-003 — Student Views Own Profile

The probing story this journey guards is simple: a curious student edits the profile URL to another student's id and hopes for a render. `CheckRoleMiddleware` verifies the `student` role, `ProfilePolicy::view()` checks `isOwner()` against the authenticated user, and the component renders own profile data or nothing at all. FR-RBAC-012 owns the ownership trait behind that refusal, proven by the profile feature tests.

#### UC-RBAC-004 — Super Admin Bypasses All Checks

When every policy in the system is suspect after a bad deploy, the recovery account must still move. Authenticated as super-admin, any protected endpoint hits `BasePolicy::before()` first, which returns `Response::allow()` immediately with no further policy check executing and full access following. FR-RBAC-010 and FR-RBAC-011 own that bypass plus continuation pair, proven by unit tests that need no database beyond the model instance.

#### UC-RBAC-005 — Teacher Proxies a Supervisor Verification

A teacher staring at a pending logbook entry while the industry supervisor's phone stays off for the third day is the reason proxy exists. She opens the entry, the policy delegates to `Registration::asMentorEntity()->canVerifyLogbook($teacher)`, `MentorEntity` confirms mentorship and grants the proxy gate, and verification persists carrying `proxy_role` plus `proxy_reason` in `activity_log.properties`. The entry ends verified in the supervisor's stead with a complete audit trail, under FR-RBAC-017 for scope and FR-RBAC-021 for audit, proven by the proxy feature test.

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

A sixth role once slipped into a pilot as a well-meant admin-teacher hybrid and every policy matrix silently grew a hole where the new role matched nothing. `Role::userRoles()` therefore enumerates exactly the five concrete roles — super_admin for global recovery, admin for school management, teacher for academic supervision, student for self scope, supervisor for company supervision — with `excludeSuperAdmin()` and `excludeAdmin()` supporting the assignment dropdowns. No sixth role may appear without a spec amendment, and an enum unit test asserting the five-case set (layer U) locks that closed.

#### FR-RBAC-002 — One role per user

At SMK Negeri 4 Surabaya a teacher who also supervised a partner workshop held two roles, and when she verified a logbook nobody could tell whether she had acted as mentor or as industry — the audit row showed one identity with two hats. Every user therefore holds exactly one role, and cross-role needs are met by proxy under FR-RBAC-016 rather than stacking, so the trail always shows one identity plus optional proxy context. An assignment action test rejecting a second role (layer F) proves the single-role invariant.

#### FR-RBAC-003 — Role-name normalization

The codebase spells the recovery check `super_admin` while Spatie stores `superadmin` as the `Role::SUPER_ADMIN` value, and that one-underscore gap once failed closed an entire morning until someone diffed the strings. The mismatch is now contained inside the enum plus the normalization both layers share, as §10 R-1 records. A round-trip test from stored name to checked name and back (layer U) proves the two spellings can never diverge again.

### 4.2 Functional Roles

#### FR-RBAC-004 — Runtime derivation only

At runtime a policy asking whether the caller counts as a mentor should not spell out teacher-or-supervisor with an `||` that the next author forgets to copy. `functionalRoles()` enumerates the functional set while `functionalRolesFor()` maps each concrete role at runtime — admin-group from super_admin plus admin, mentor from teacher plus supervisor, mentee from student — derived, never stored in the database, and never used in route middleware, which stays concrete. Route middleware uses concrete roles while functional roles evaluate at the policy layer, the exact map lives in §6.2 under the [flat-RBAC ADR](../adr/adr-flat-rbac-with-functional-roles.md), and an enum unit test over the full map (layer U) proves it.

#### FR-RBAC-005 — Admin-group check

Shared admin permission checks used to sprawl as `||` branches across a dozen policies, and adding a member to the group meant editing every one of them. `$user->role->is(Role::ADMIN)` collapses all of that to a single call matching both super_admin and admin, so group membership edits the map rather than every policy. A unit test asserting both members match (layer U) is the proof.

#### FR-RBAC-006 — MentorEntity bridge

The edge case that breaks naive mentorship checks is a teacher who mentors one internship group but probes another group's supervision log — a bare role check would wave her through. Mentorship therefore resolves through the `MentorEntity` bridge built from internship-group membership via `Registration::asMentorEntity()`, whose `fromModel()` bridges the registration record while role queries (`isTeacher`, `isSupervisor`, `isMentor`) read the mentors collection. Because the bridge constructs from a fabricated collection, entity unit tests with fabricated collections (layer U) prove scoping without touching a database.

### 4.3 Three Authorization Layers

#### FR-RBAC-007 — Route middleware

When a request arrives, `CheckRoleMiddleware` (`role:...`) verifies concrete roles before any controller or component code runs, so guests bounce to login while authenticated-but-forbidden callers receive 401 for JSON and Livewire versus 403 for web — the distinction matters because a Livewire component retrying on 403 would loop where 401 tells it to re-authenticate. Functional roles never appear at this layer. Denied access is logged via SmartLogger with PII masking, and a middleware feature test per role matrix (layer F) proves the gating.

#### FR-RBAC-008 — Livewire authorization

A student once opened DevTools, flipped a hidden Verify button visible, and clicked it — the button had been hidden by CSS alone with no server-side gate behind it. Components therefore call `authorize()` so policy-driven visibility gates real behaviour such as `@can('verify', $logbook)`, and no Livewire change is needed for proxy visibility since policy gates drive it, with the explicit Act as Supervisor toggle and banner remaining optional and session-flagged via `session('proxy_mode')`. Component tests asserting 403 versus render (layer F) prove the gate is real.

#### FR-RBAC-009 — Policy base contract

Without a single inheritance root, nineteen modules grow nineteen policy dialects and the ownership check gets forgotten in the seventh. Every policy extends `BasePolicy`, which composes `AuthorizesRoles` plus `AuthorizesOwnership`, while supervisor-scoped methods delegate to `MentorEntity`; the mentor-adjacent helpers on `AuthorizesRoles` are deprecated in favour of the entity proxy methods, with `isAdmin()` and `hasAnyOfRoles()` retained for admin-only gates. A class-contract scan asserting the extends chain (layer A) proves the root holds.

#### FR-RBAC-010 — Super-admin bypass

During a broken deploy where the permissions table had not finished migrating, every permission lookup threw and the recovery account could not reach the fix. `BasePolicy::before()` therefore returns `Response::allow()` for the `super_admin` role with no permission lookup at all — a `Gate::before`-style early return, distinct from granting all permissions in the database. A policy unit test asserting allow without further evaluation (layer U) proves the bypass needs nothing else alive.

#### FR-RBAC-011 — Null continuation

The subtle failure here is an early deny in the before-hook that silently closes proxy paths a teacher legitimately needs when covering an inactive supervisor. For every role other than super_admin the hook returns `null` so evaluation falls through to the concrete ability method. A unit test asserting `null` for admin, teacher, student, and supervisor (layer U) proves continuation is unconditional.

### 4.4 Traits, Discovery, and Assignment

#### FR-RBAC-012 — Ownership trait

At SMKN 1 Gesi a student guessed another student's profile URL and, for one deploy, actually saw it — ownership had been checked in three policies three different ways, and the fourth had been forgotten. `AuthorizesOwnership` ends that drift with one shared vocabulary: `isOwner()` comparing `user_id`, `isRelatedThrough()` following a relation, and `isOwnerOrAdmin()` unioning ownership with the admin group. Trait unit tests with stub models (layer U) prove each helper.

#### FR-RBAC-013 — Role trait (admin gates)

This trait exists because admin-only gates kept collecting teacher and supervisor shortcuts until a bare role check waved a supervisor into an admin screen. Only the admin-gate helpers stay on the trait — `isAdmin()`, `canManageAnyRole()`, `hasAnyOfRoles()` — while every teacher, supervisor, and mentor question goes through `MentorEntity` so mentorship scope is never bypassed by a role alone. Unit tests for the three retained helpers (layer U) pin the boundary.

#### FR-RBAC-014 — Auto-discovery with cache

With nineteen modules contributing policies, manual registration rotted on the first forgotten entry — a new policy class shipped, nobody registered it, and its endpoints fell through to default-deny confusion for a week. Auto-discovery scans `Policies/` directories for `BasePolicy` subclasses instead, caching results for 24 hours and invalidating on cache clear, with mechanics owned by the module infrastructure ([B114U](B114U-module-manager.md)) on registered cache keys. A discovery feature test plus a TTL assertion (layer F) proves-scan and expiry both hold.

#### FR-RBAC-015 — UserPolicy exception

The user policy must exist before discovery runs, otherwise the very first authorization decision during boot races the scanner. `Gate::policy(User::class, UserPolicy::class)` is therefore registered manually in `AppServiceProvider` as the single exception to auto-discovery. A provider audit (layer A) proves the exception is present and singular.

#### FR-RBAC-016 — Assignment invalidates cache

The edge every role system dreads is the stale cache: an admin demotes a user, the cached roles still say teacher, and the next request serves the old permissions — a privilege vector in one direction, a lockout in the other. Assignment therefore invalidates the user's cached roles synchronously inside the assignment path, before the next request. An assignment feature test asserting fresh roles on the next request (layer F) proves the window is closed.

### 4.5 Cross-Role Proxy

> Implements the proxy matrix in the [cross-role-proxy ADR](../adr/adr-cross-role-proxy.md).
> Applies to: logbook verification ([1KSWL](1KSWL-daily-activity.md)), assessment grading
> ([ARDA6](ARDA6-assessment.md)), and supervision-log verification ([2EHSE](2EHSE-supervision.md)).

#### FR-RBAC-017 — Hierarchy and scope

A teacher covering for an industry supervisor who has not opened the app in nine days needs to verify her student's logbook tonight, but she must never verify a stranger's — while an admin stepping in during a cross-program audit legitimately acts on any record. The hierarchy encodes exactly that: `admin` may proxy as `teacher` or `supervisor` on any record, `teacher` may proxy as `supervisor` only for students in that teacher's mentorship bounded by `isTeacher($user)` on the registration's mentor set, and `supervisor` and `student` hold no proxy capability at all. `MentorEntity` unit tests per hierarchy cell (layer U) prove each grant and each refusal.

#### FR-RBAC-018 — Delegation pattern

At runtime the policy method stays a single delegation line — `$entry->registration?->asMentorEntity()->... ?? false` — naming one of `canProxyAsSupervisor`, `canProxyAsTeacher`, `canVerifyLogbook`, `canScoreCompetency`, `canReviewSupervisionLog`, or `canGradeSubmission` reached via `Registration::asMentorEntity()`, while every branch lives inside the entity and is tested once. That shape keeps six call sites from growing six dialects. Entity unit tests plus policy feature tests (layer U with spot F) prove delegation and branching together.

#### FR-RBAC-019 — Inactivity window

Today no `journals.proxy_inactivity_hours` key and no inactivity check exist in code — proxy is implicit via the policy gate — so a teacher could step in minutes after the supervisor's last heartbeat rather than after genuine abandonment. The specified gate requires supervisor inactivity across a configurable window stored in settings key `journals.proxy_inactivity_hours` with default `48` hours before teacher proxy activates. The row stays Planned until that settings key plus the gate land per §10 R-1, with a settings-key test and a stale-supervisor-fixture gate test as its pending proof.

#### FR-RBAC-020 — Per-domain coverage

If proxy coverage were one shared shortcut, a change to logbook verification would ripple into assessment finalization unreviewed. Each domain therefore wires its own policy method and no domain borrows another's: logbook verification with assessment scoring and finalization first, supervision-log review with submission grading next. One feature test per proxy path (layer F) proves every adoption independently.

#### FR-RBAC-021 — Audit properties

When an accreditation auditor asks who verified a logbook, caused-by must name the teacher who clicked while the stead and cause name the absent supervisor — a single actor column cannot carry both. Every proxy action therefore records `proxy_role` and `proxy_reason` in `activity_log.properties`, with `causedBy($user)` holding the acting teacher and `withProperties(['proxy_role' => ..., 'proxy_reason' => ...])` holding stead and cause, distinguishable primary versus proxy in every row with no schema change and no new columns. A feature test asserting the properties JSON on a proxied act (layer F) proves the trail.

#### FR-RBAC-022 — No role expansion

Proxy must never become a quiet second role: the day `model_has_roles` gains a proxy row, route middleware starts honouring it, or `BasePolicy::before()` widens beyond super-admin, the audit model collapses. What does not change is therefore stated plainly — single role assignment, no pivot columns, super-admin-only `before()`, role-based route middleware — with proxy living purely as a runtime permission check that replaces inline role checks with one testable source. A migration audit confirming no new columns plus a contract scan (layer A) prove the boundary holds.

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

A deploy that clears config but keeps yesterday's discovered policy map serves stale bindings — new policies ignored, removed ones still enforced — which is a silent authorization regression. Deploy-time `config:clear` and `cache:forget` must therefore flush the discovered bindings. A clear-then-rediscover feature test (layer F) proves stale maps cannot survive a deploy.

#### NFR-RBAC-002 — Next-request effect

At SMK Negeri 6 Malang an admin fixed a mis-assigned role during enrollment rush and watched the teacher's screen keep the old permissions for the rest of the session, sowing panic that the fix had failed. Role changes take effect on the next authenticated request because FR-RBAC-016 invalidates synchronously — session and cache must never pin the old role past one request. The assignment feature test (layer F) proves the freshness.

### 5.2 Bypass Robustness

#### NFR-RBAC-003 — Bypass survives broken policies

Today the bypass lives in `BasePolicy::before()`, which only runs when the policy class resolves — so a missing or broken policy class bypasses nothing and the recovery account loses its guarantee at the worst possible moment. The specified hardening is a framework-level `Gate::before` in the provider, not yet implemented, which is why the row stays Planned per §10 R-2. Its pending proof is a missing-policy fixture test asserting super-admin allow.

### 5.3 Testability

#### NFR-RBAC-004 — No-DB policy and entity tests

Slow authorization tests get skipped during enrollment-week pressure, and skipped tests hide regressions. `MentorEntity` constructs from a mentors collection while policy helpers take plain model instances, so the whole policy and entity surface stays pure logic with a fast suite and no database beyond the model instance. The existing entity unit tests running without a database (layer U) prove the suite stays fast.

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

Indonesian vocational schools draw crisp lines — the admin runs operations, the teacher supervises, the student participates — and an inheritance chain where admin silently absorbs teacher permissions would blur exactly the boundary principals rely on. Roles therefore stay flat, each owning precisely its own permissions with no silent leakage through a hierarchy. Some permissions are duplicated across roles, such as both admin and teacher viewing student data, and that duplication is accepted for clarity while the admin-group mapping removes the worst of it.

#### DD-RBAC-002 — Three-Layer Authorization Stack

A single missing authorization check once left an export endpoint open to every authenticated user, and nobody noticed for a term because each layer assumed another layer had checked. Enforcement at three layers — route middleware stopping unauthorized route access, Livewire authorization stopping component rendering, policy gates protecting individual methods — means a gap must slip past all three to become a vulnerability. Triple-checking costs minor overhead, mitigated by the super-admin bypass and framework-level caching.

#### DD-RBAC-003 — Super-Admin Bypass via Before-Hook

When a misconfigured policy deploy locked every teacher out of supervision logs, the only account that could revert the deploy was the one the policy had also locked out. `BasePolicy::before()` returning `Response::allow()` for super_admin exists so that emergency recovery account survives any misconfigured policy, bypassing all subsequent checks. A super admin consequently cannot be restricted from specific actions, which is acceptable for a single-purpose recovery account, with framework-level hardening tracked as NFR-RBAC-003.

#### DD-RBAC-004 — Cross-Role Proxy over Multi-Role

An earlier Dual Mentor Fallback tried to cover supervisor absence with a narrow second-role hack, and it produced exactly the audit confusion, workload obfuscation, policy duplication, scope creep, and pivot pollution the cross-role-proxy ADR names. Directed runtime delegation via `MentorEntity` replaces it: scope-isolated, auditable, schema-free, and adoptable per domain. Each policy method carries one proxy clause plus one test per path, which is the honest cost of explicit delegation, as the [cross-role-proxy ADR](../adr/adr-cross-role-proxy.md) records.

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
