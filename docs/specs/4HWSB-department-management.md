# Department Management — CRUD, Deletion Guards & Cache Invalidation

> **Spec ID:** 4HWSB
> **Status:** Full
> **Owner:** Academics
> **Depends on:** 81SMS

## Description

Departments are the primary organizational unit inside a school: every student and teacher profile
belongs to exactly one department, and internship assignment, supervision, and reporting all filter
on it. This spec fixes the department lifecycle — create, read, update, guarded delete — plus the
profile-dependency guard that keeps assigned profiles from being orphaned, and the dashboard cache
invalidation that keeps aggregate counts honest. Bulk CSV import and export of departments belong to
the cross-cutting [csv-import-export](O2KCR-csv-import-export.md) spec, not here.

---

## 1. Problem Statements

### PS-1 — No Grouping Primitive for Profiles

A school with six majors and nine hundred students cannot run placement, supervision, or reporting
on an undifferentiated profile list. Without a department row to point at, every filter becomes a
naming-convention hack in a spreadsheet, and reassigning a student silently breaks whatever report
depended on the old spelling.
**→ Requirement:** FR-DEPT-001 (department schema), FR-DEPT-012 (manager list and search).

### PS-2 — Deleting a Department Orphans Its Profiles

Profiles carry a `department_id` foreign key. Deleting a department that still has students or
teachers attached would either null that key or violate the constraint, and neither outcome is
acceptable the week before placement. The guard must live where a direct Action call cannot dodge
it, not only behind the UI button.
**→ Requirement:** FR-DEPT-005 (entity deletion guard), FR-DEPT-008 (action enforcement),
FR-DEPT-009 (policy alignment).

### PS-3 — Dashboard Counts Go Stale After Department Changes

The admin dashboard aggregates department counts and per-department profile distributions from
cache. A department created at 08:00 that still does not appear at 10:00 teaches administrators to
distrust the dashboard and keep their own spreadsheet — the exact habit this system exists to kill.
**→ Requirement:** FR-DEPT-014 (domain events), FR-DEPT-016 (cache invalidation listener).

---

## 2. Goals & Non-Goals

### Goals

- **Full department CRUD** — create, read, update, and guarded delete through Command Actions. *Why:* placement, supervision, and reporting all join on departments, so the primitive must be solid.
- **Profile-dependency deletion guard** — a department with attached profiles cannot be deleted until they are reassigned. *Why:* referential integrity for nine hundred profiles cannot rest on administrator memory.
- **Domain events on every mutation** — created, updated, and deleted events from the Actions that perform them. *Why:* downstream consumers (dashboard cache today, notifications tomorrow) subscribe without touching business logic.
- **Automatic dashboard cache invalidation** — a single listener clears the cached aggregates on any department event. *Why:* stale counts on the admin landing page destroy trust in the whole system.
- **Bulk delete with per-item guards** — checkbox-selected departments are evaluated one by one, eligible rows deleted, blocked rows reported. *Why:* end-of-year cleanup touches dozens of rows and must not fail entirely on the first blocked one.

### Non-Goals

- **Department merge or transfer operations**. *Why:* reassignment happens profile by profile, where the receiving department choice is explicit and auditable.
- **Department hierarchy or nesting**. *Why:* a flat major list matches how SMK programs are actually organized; nesting adds UI and query cost with no placement use case.
- **Department-level user management**. *Why:* users are managed through the User module; departments only group profiles.
- **Soft deletes or archiving**. *Why:* departments carry no historical records worth preserving, and the activity log already records what was removed.
- **Cross-school sharing**. *Why:* single-tenant by product definition — one installation, one school.
- **CSV bulk import/export**. *Why:* owned by the cross-cutting [csv-import-export](O2KCR-csv-import-export.md) spec so every module shares one import pipeline.

---

## 3. User Stories / Use Cases

An administrator's whole relationship with departments fits in five journeys: create one, rename
one, bounce off the guard, delete a clean one, and sweep a batch. Each journey below is a
browser-verifiable flow through the manager component.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-DEPT-001 | Admin creates a department and sees it listed with a success notice | P0 | B | Full |
| UC-DEPT-002 | Admin renames a department without triggering a false uniqueness conflict | P0 | B | Full |
| UC-DEPT-003 | Admin attempts to delete a department that still has profiles and is blocked with the profile count | P0 | B | Full |
| UC-DEPT-004 | Admin deletes a department with no profiles and the dashboard counts refresh | P0 | B | Full |
| UC-DEPT-005 | Admin bulk-deletes selected departments; eligible rows go, blocked rows are reported | P1 | B | Full |

### 3.1 Single-Record Lifecycle

#### UC-DEPT-001 — Create a department

The new vice-principal for curriculum opens Academics → Departments on the first Monday of the
term, clicks Create, and types the new "Desain Komunikasi Visual" major that the school just
accredited. The form validates as she types, the save dispatches through the create Action, and
the row appears in the list before her toast fades. Behind that two-second interaction sits the
full mutation path — validated DTO into a Command Action, transaction, event, listener, cache
clear — which is exactly why the journey is the cheapest end-to-end proof that the path holds.

#### UC-DEPT-002 — Rename a department without a false conflict

"Teknik Komputer dan Jaringan" shortens its signboard to "TJKT" and the admin edits the name to
match. The trap this journey guards is the naive uniqueness check: the department's own existing
name must not count as a collision with itself. The form therefore validates uniqueness excluding
the current row, the update Action repeats the same exclusion at the persistence boundary, and a
genuine collision — renaming into another department's name — still fails loudly at both layers.

### 3.2 Deletion and Bulk Cleanup

#### UC-DEPT-003 — Bounce off the deletion guard

Mid-semester, an admin tries to remove "Akuntansi" because its classes moved buildings — forgetting
the building is not the department, and two hundred student profiles still point at the row. The
confirmation dialog appears, she confirms, and instead of a deletion she gets a plain sentence:
the department cannot go while it still holds that many profiles, and reassignment comes first.
Nothing was deleted, nothing was half-deleted, and the number in the message tells her exactly how
much reassignment work stands between her and the goal.

#### UC-DEPT-004 — Delete a department with no profiles

Contrast the previous journey: a pilot major that never enrolled a single student is removed in
one click. The guard evaluates, finds zero attached profiles, and the delete Action removes the
row inside a transaction, emits the deleted event, and clears the dashboard cache — so the
department count on the admin landing page drops immediately instead of lingering until the cache
TTL expires and starting a rumor that the deletion failed.

#### UC-DEPT-005 — Sweep a batch with mixed eligibility

End-of-year cleanup means a checkbox column: fourteen obsolete rows selected, Delete Selected,
one confirmation. Three have lingering teacher profiles from a reorganization nobody finished;
eleven are genuinely empty. The old all-or-nothing design would have aborted the entire batch on
the first blocked row and taught the admin to delete fourteen rows by hand. Instead each row is
evaluated on its own guard, the eleven go, the three stay, and the summary names both counts —
so the follow-up work (reassign three departments' teachers) is explicit rather than discovered
by trial and error.

---

## 4. Functional Requirements

Department behavior decomposes into schema, entity rules, three Command Actions plus bulk
handling, policy gating, DTO and form validation, the Livewire manager, and the event, audit,
and cache tail. Every row below is implemented and verified.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-DEPT-001 | `departments` table uses a UUID v7 primary key with a unique non-null `name` and nullable `description` | P0 | A | Full |
| FR-DEPT-002 | `Department` model extends `BaseModel` with `#[Fillable]` name/description, a `profiles()` hasMany relation, an entity bridge, and a factory | P0 | A | Full |
| FR-DEPT-003 | `DepartmentState` entity is `final readonly`, extends `BaseEntity`, and is constructed only via `fromModel()` | P0 | U | Full |
| FR-DEPT-004 | `DepartmentState::fromModel()` resolves `profileCount` and `hasProfiles` from the eager-loaded relation when available, falling back to an existence query | P0 | U | Full |
| FR-DEPT-005 | `DepartmentState::canBeDeleted()` returns false whenever any profile is attached and is the single authority for deletion eligibility | P0 | U | Full |
| FR-DEPT-006 | `CreateDepartmentAction` validates name uniqueness, persists inside a transaction, dispatches `DepartmentCreated`, and audit-logs | P0 | F | Full |
| FR-DEPT-007 | `UpdateDepartmentAction` validates name uniqueness excluding the current row, persists inside a transaction, dispatches `DepartmentUpdated`, and audit-logs | P0 | F | Full |
| FR-DEPT-008 | `DeleteDepartmentAction` re-checks the entity guard, throws `RejectedException` with a translatable message when profiles are attached, and deletes inside a transaction | P0 | F | Full |
| FR-DEPT-009 | `DepartmentPolicy` opens listing and viewing to every authenticated user, restricts create/update to the admin group, conditions delete on the entity guard, and forbids force-delete outright | P0 | U | Full |
| FR-DEPT-010 | `DepartmentData` DTO is `final readonly`, extends `BaseData`, and carries only `name`, `description`, and an optional `id` | P0 | U | Full |
| FR-DEPT-011 | Department form rules require a unique name capped at 255 characters and allow a description capped at 1000 characters | P0 | U | Full |
| FR-DEPT-012 | `DepartmentManager` provides the searchable, sortable record table and routes create/edit/save through the policy gate and the correct Command Action | P0 | F | Full |
| FR-DEPT-013 | Bulk delete evaluates `canBeDeleted()` per selected row, deletes only eligible rows, and reports deleted and blocked counts separately | P1 | F | Full |
| FR-DEPT-014 | Each mutation dispatches its domain event (`DepartmentCreated`, `DepartmentUpdated`, `DepartmentDeleted`) from the Action after commit | P0 | F | Full |
| FR-DEPT-015 | Every department mutation writes a SmartLogger activity entry with actor identity and change summary, masking PII before either sink | P0 | F | Full |
| FR-DEPT-016 | A single listener invalidates the registered dashboard-stats cache key on all three department events | P1 | F | Full |

### 4.1 Schema and Model

#### FR-DEPT-001 — Departments table with UUID key and unique name

Auto-increment ids would have leaked enrollment scale into every URL and collided on the first
staging-to-production merge, so departments follow the project-wide UUID v7 contract from the
[start](QLHDO-project-initialization.md): a UUID primary key, a unique non-null name, a nullable
description, and timestamps. Uniqueness is enforced at the database level rather than trusted to
application code, because two admins creating "RPL" in the same minute is a when, not an if —
and the losing request deserves a clean constraint-backed rejection, not a duplicate row.

#### FR-DEPT-002 — Thin persistence model with bridge and factory

The model does persistence and nothing else: it extends `BaseModel` for the UUID contract, carries
the `#[Fillable]` attribute for exactly `name` and `description`, exposes the `profiles()` hasMany
relation, bridges to its entity through `asDepartmentState()`, and ships a factory so tests build
rows without hand-rolled fixtures. A reviewer tracing a department write starts here, finds no
business logic hiding in accessors or scopes, and follows the bridge into the entity where the
rules actually live.

### 4.2 Entity Rules

#### FR-DEPT-003 — Entity form and construction discipline

The entity-model split from [entity-model-separation](../adr/adr-entity-model-separation.md) is
what lets the deletion rule run in a millisecond unit test with no database: `DepartmentState`
is `final readonly`, extends `BaseEntity`, and the only way to build one from persistence is
`fromModel()`. There is no public constructor path that accepts a hand-assembled profile count,
because a guard evaluated against invented numbers is theater — the numbers must always come from
the row under decision.

#### FR-DEPT-004 — Relation-aware count resolution

Counting attached profiles the naive way fires a query per row and turns the department list into
an N+1 showcase the first time a school passes fifty majors. The bridge therefore prefers the
already-loaded relation count when the caller eager-loaded it, and only falls back to an
existence query when it did not. Both paths answer the same question — is anything attached —
and the `exists()` fallback is deliberately cheaper than a full count, since the guard needs a
boolean, not a census.

#### FR-DEPT-005 — One guard to rule out orphans

`canBeDeleted()` is a single negation — no attached profiles, deletable — and its authority is
total: the policy consults it before authorizing, the delete Action consults it before deleting,
and the bulk flow consults it per row. Three call sites, one predicate, zero drift. The day
someone proposes a second, subtly different "is empty" check for a new feature is the day this
row earns its keep, because the answer will be to call this method rather than re-derive it.

### 4.3 Command Actions

#### FR-DEPT-006 — Create inside a transaction with event and audit

Two admins onboarding the same new major in parallel used to produce twin rows; now the second
request meets the uniqueness validation and the unique constraint, and loses gracefully. The
create Action takes the validated DTO, persists inside `$this->transaction()`, dispatches
`DepartmentCreated` after commit, and writes the activity entry — in that order, so a rolled-back
write never announces itself to listeners that already acted on it.

#### FR-DEPT-007 — Update with self-excluding uniqueness

The update Action mirrors creation with one critical difference: the uniqueness rule excludes the
row being edited, which is what makes UC-DEPT-002 possible instead of a frustrating false
collision on every save-without-changes. Everything else — transaction wrapping, post-commit
event, audit entry — is identical to creation, because a rename that appears in the list but
never reached the audit log is a ghost edit no accreditation folder can explain.

#### FR-DEPT-008 — Guarded delete that refuses loudly

The delete Action checks the entity guard first and, when profiles are attached, throws
`RejectedException` carrying a translatable message with the blocking count — per
[exception-hierarchy](../adr/adr-exception-hierarchy.md), a business-rule refusal the Livewire
layer renders as a toast, never a 500 page. The actual deletion runs in a transaction so the row
removal, the event dispatch, and the audit entry succeed or fail together. A direct Action call
from a console command or a test gets exactly the same refusal as the UI button, which is the
entire point of enforcing the rule here rather than in the component.

### 4.4 Policy and Data Shapes

#### FR-DEPT-009 — Open reading, gated writing, impossible force-delete

Every authenticated user — teacher planning supervision groups, student confirming their major —
can list and view departments, because hiding the organizational chart from the people inside it
helps nobody. Writes belong to the admin group (`super_admin` and `admin` via the flat-RBAC
functional role), deletion additionally requires the entity guard to pass, and force-delete
returns false unconditionally since hard delete is the only delete and there is nothing to
"force" past. The policy duplicates no queries: the eligibility half of the delete check
delegates to the same `canBeDeleted()` the Action uses.

#### FR-DEPT-010 — Minimal owned DTO

`DepartmentData` carries three fields — `name`, an optional `description`, and an optional `id`
that distinguishes create from update payloads — and nothing else, because a DTO that accretes
`created_by`, `school_id`, and other ambient context becomes a second model. It extends
`BaseData`, lives in the module that consumes it per the DTO-ownership rule, and crosses the
UI-to-Business boundary so raw request arrays never reach an Action.

#### FR-DEPT-011 — Shared length and uniqueness bounds

The 255-character name cap and 1000-character description cap match the column types exactly —
an earlier draft capped the Action layer lower than the form layer, so a name the form accepted
died in the Action with a confusing error. Both layers now validate the same bounds, the unique
rule excludes the current id on update, and any future third entry point inherits the same
numbers rather than inventing its own.

### 4.5 Livewire Manager and Bulk Handling

#### FR-DEPT-012 — Manager as thin traffic controller

`DepartmentManager` extends `BaseRecordManager` and behaves like every other record table in the
system: declared headers, a query builder with name search, and create/edit methods that
authorize through the policy before touching the form. The save method looks at whether the form
carries an id and dispatches to the create or update Action accordingly, then maps the
`ActionResponse` — including a caught `RejectedException` — onto toasts. No model calls appear
in the component; the C1 scan would fail the commit if they did.

#### FR-DEPT-013 — Per-row bulk evaluation with honest counts

Bulk delete loops the selected ids, asks `canBeDeleted()` about each one, deletes the eligible
rows through the single-delete Action (so each deletion still gets its own transaction, event,
and audit entry), and counts both outcomes. The summary toast reports deletions and blocks
separately, which matters more than it sounds: "11 deleted, 3 blocked (have profiles)" is a
work order, while a bare success message would have hidden three surviving rows until someone
tripped over them months later.

### 4.6 Events, Audit, and Cache

#### FR-DEPT-014 — Post-commit domain events from Actions

Each Command Action dispatches exactly one domain event naming what happened, and dispatches it
after the transaction commits — a listener that clears cache or notifies staff must never react
to a write that later rolls back. The events extend `BaseEvent`, expose `eventName()` for the
translation layer, and carry the affected model so listeners need no follow-up query to do
their job.

#### FR-DEPT-015 — Dual-channel audit with PII masking

Accreditation visits ask who changed what and when, and the answer comes from the activity
channel: every department mutation writes an entry with the acting user and a before/after
summary through SmartLogger, per
[smartlogger-dual-channel](../adr/adr-smartlogger-dual-channel.md). Payloads pass through PII
masking before reaching either sink, so an over-eager `->toArray()` in a log call cannot spill
emails or phone numbers into plaintext files. Unexpected failures take the other channel — a
generic user-facing message plus a fully-contextual system log — so operators debug from facts
while users see calm.

#### FR-DEPT-016 — One listener clears the dashboard aggregates

`ClearDashboardCacheOnDepartmentChange` handles all three department events with a single
`Cache::forget()` against the key registered in `config/cache-keys.php`. A listener rather
than an observer is deliberate here: the cached aggregates belong to the dashboard's module,
making this cross-module fan-out, which per
[eloquent-observers](../adr/adr-eloquent-observers.md) belongs in Events + Listeners rather
than a same-model observer. One class, one key, no event left unhandled — the rename that
forgot to clear cache is impossible by construction.

---

## 5. Non-Functional Requirements

Guards that hold under concurrency, feedback an administrator can act on, and the structural
hygiene the scans enforce. `Target` is `N/A` where enforcement is architectural rather than
measured at runtime.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-DEPT-001 | Mutations are authorized at both the policy gate and the Action layer | N/A | P0 | A | Full |
| NFR-DEPT-002 | Department names are unique at the database and Action layers simultaneously | 0 duplicates | P0 | F | Full |
| NFR-DEPT-003 | Force-delete is unconditionally forbidden | N/A | P1 | U | Full |
| NFR-DEPT-004 | Every mutation runs inside a database transaction with post-commit events | N/A | P0 | A | Full |
| NFR-DEPT-005 | Bulk delete reports deleted and blocked counts as separate numbers | both counts shown | P1 | F | Full |
| NFR-DEPT-006 | Blocked-deletion messages name the attached profile count and the reassignment remedy | count + remedy in message | P1 | F | Full |
| NFR-DEPT-007 | Uniqueness violations surface as inline form errors, announced to assistive technology | inline + announced | P1 | B | Full |
| NFR-DEPT-008 | Department lists resolve profile counts without N+1 queries | N/A | P1 | A | Full |
| NFR-DEPT-009 | All PHP files declare strict types; entities and DTOs stay `final readonly` and framework-pure | 0 violations | P0 | A | Full |
| NFR-DEPT-010 | Every user-facing string passes through `__()` with mirrored `en` and `id` keys | 0 missing keys | P0 | A | Full |

### 5.1 Safety and Integrity

#### NFR-DEPT-001 — Two gates, no back door

A policy that authorizes what the Action would refuse — or an Action that permits what the
policy denied — is a latent privilege bug wearing a passing test suite. The dual-layer rule
means the HTTP path meets the policy first and the business rule second, while a direct Action
call still meets the business rule, so there is no entry point that enforces neither.

#### NFR-DEPT-002 — Uniqueness at two altitudes

Application-level validation gives the fast, friendly error; the database constraint gives the
guarantee under concurrent double-submit. Either alone fails the story that matters — two
admins, one new major, the same second — and together they reduce the incident to a clean
rejection on the losing request with zero cleanup afterward.

#### NFR-DEPT-003 — No resurrection semantics to maintain

Because deletion is hard and final, there is no trashed scope to leak into queries, no restore
path to authorization-gate, and no "deleted but visible" state for reports to misinterpret.
The activity log entry written at deletion time is the entire recovery story: the name and
payload needed to recreate the row deliberately.

#### NFR-DEPT-004 — Atomic writes, ordered side effects

The transaction boundary wraps the write; the event and the audit entry follow the commit.
Reversing that order once produced the classic ghost — a dashboard that cleared its cache for
a department that was never actually created — and the ordering is now structural rather than
remembered, because ordering maintained by memory lasts exactly one onboarding cycle.

### 5.2 Feedback and Performance

#### NFR-DEPT-005 — Bulk honesty in numbers

A bulk operation that reports only its successes trains administrators to assume total success,
and the three surviving rows become next semester's mystery. Separate deleted and blocked
counts, with the blocked reason attached, turn the toast into a checklist instead of a
celebration.

#### NFR-DEPT-006 — Refusals that teach the remedy

"Cannot delete" without a count or a next step is a dead end that generates a support ticket;
"still holds 34 profiles — reassign them first" is a work order the admin can execute
immediately. The message is built from the entity's numbers and translated through `__()`,
so the Indonesian-speaking operator gets the same actionable sentence as the English-speaking
one.

#### NFR-DEPT-007 — Inline errors where the eyes already are

The uniqueness failure appears beneath the name field it belongs to, not as a detached banner
at the page top, and the error region is announced to screen readers so keyboard-only
administrators meet the same feedback sighted ones do. Form inputs carry associated labels
throughout, which is what makes the error-to-field association programmatically possible.

#### NFR-DEPT-008 — Count once, display everywhere

The department table shows per-row profile counts, and each count must resolve from the
eager-loaded aggregate rather than a per-row query — the morning routine of an admin scanning
forty majors must not cost forty queries. Read paths that need the numbers use the same
relation-aware resolution the entity bridge uses, so the fix and the display can never diverge.

### 5.3 Structural Hygiene

#### NFR-DEPT-009 — Strict types and pure boundaries

`declare(strict_types=1)` on every PHP file, entities and DTOs `final readonly` with no
framework imports beyond value types — these are scan-enforced facts, not aspirations, and the
pre-commit batch fails loudly on drift. The payoff compounds silently: coercion bugs never
enter, and the entity unit tests keep running in milliseconds because nothing in the boundary
objects can reach for the database.

#### NFR-DEPT-010 — No hardcoded user strings

Every string an administrator reads — headers, toasts, confirmation dialogs, validation
messages — passes through `__()` with keys present in both `lang/en/` and `lang/id/`. The D3
scan proves it on every commit, which is what lets a school toggle locale at runtime without
discovering a hardcoded English sentence in the one dialog the auditor opens.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 Department Model

```php
// app/Modules/Academics/Department/Models/Department.php
class Department extends BaseModel
{
    use HasFactory;

    // #[Fillable(['name', 'description'])]

    public function profiles(): HasMany;  // → Profile::class
    public function asDepartmentState(): DepartmentState;
    protected static function newFactory(): DepartmentFactory;
}
```

### 6.2 DepartmentState Entity

```php
// app/Modules/Academics/Department/Entities/DepartmentState.php
final readonly class DepartmentState extends BaseEntity
{
    public function __construct(
        private int $profileCount,
        private bool $hasProfiles,
    ) {}

    public static function fromModel(Model $model): static;
    // profileCount: loaded relation count when eager-loaded, else exists()-derived
    // hasProfiles: true when profileCount > 0

    public function canBeDeleted(): bool;  // !$this->hasProfiles (FR-DEPT-005)
}
```

### 6.3 DepartmentData DTO

```php
// app/Modules/Academics/Department/Data/DepartmentData.php
final readonly class DepartmentData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $id = null,
    ) {}
}
```

### 6.4 Actions

| Action | Accepts | Returns | Event |
| ------ | ------- | ------- | ----- |
| `CreateDepartmentAction` | `DepartmentData` | `ActionResponse` | `DepartmentCreated` |
| `UpdateDepartmentAction` | `Department, DepartmentData` | `ActionResponse` | `DepartmentUpdated` |
| `DeleteDepartmentAction` | `Department` | `ActionResponse` | `DepartmentDeleted` |

All extend `BaseCommandAction` (FR-DEPT-006/007/008). Name rule: required, string, max 255,
`unique:departments,name` (excluding current id on update); description: nullable, string,
max 1000 (FR-DEPT-011). Bulk deletion iterates through `DeleteDepartmentAction` per eligible
row (FR-DEPT-013).

### 6.5 Policy

```php
// app/Modules/Academics/Department/Policies/DepartmentPolicy.php
class DepartmentPolicy extends BasePolicy
{
    public function viewAny(?User $user): bool;                    // true
    public function view(?User $user, Department $department): bool; // true
    public function create(User $user): bool;                     // admin group
    public function update(User $user, Department $department): bool; // admin group
    public function delete(User $user, Department $department): bool;
        // admin group && $department->asDepartmentState()->canBeDeleted()
    public function forceDelete(User $user, Department $department): bool; // false, always
}
```

### 6.6 Form Object

```php
// app/Modules/Academics/Department/Livewire/Forms/DepartmentForm.php
class DepartmentForm extends Form
{
    public ?string $id = null;
    public string $name = '';
    public ?string $description = null;

    public function rules(): array;
    // name: required|string|max:255|unique:departments,name,{id}
    // description: nullable|string|max:1000
}
```

### 6.7 Events and Listener

```php
// Events — each extends BaseEvent with eventName(): string
final class DepartmentCreated extends BaseEvent { public function __construct(public Department $department) {} }
final class DepartmentUpdated extends BaseEvent { public function __construct(public Department $department) {} }
final class DepartmentDeleted extends BaseEvent { public function __construct(public Department $department) {} }

// Listener — handles all three events (FR-DEPT-016)
final class ClearDashboardCacheOnDepartmentChange
{
    public function handle(DepartmentCreated|DepartmentUpdated|DepartmentDeleted $event): void;
    // Cache::forget(config('cache-keys.admin_dashboard_stats'))
}
```

### 6.8 Routes

Single Livewire route behind authentication and the admin-group gate:
`GET /admin/departments` → `DepartmentManager` (middleware `auth`, `role:super_admin|admin`).

### 6.9 Database Schema

```
departments:
  id:          uuid (PK, v7)
  name:        varchar(255) (unique, not null)
  description: text (nullable)
  created_at:  timestamp (nullable)
  updated_at:  timestamp (nullable)

Migration: database/migrations/2026_01_03_000002_create_departments_table.php
```

Profiles reference departments through a `department_id` foreign UUID; the deletion guard
(FR-DEPT-005/008) guarantees the key never dangles, so no cascading delete is defined.

---

## 7. Design Decisions

One table of the calls that shaped this spec. Each decision below is load-bearing: reversing
any of them reopens the incident or the drift it was written to close.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-DEPT-001 | Deletion eligibility lives in the entity and is consulted by policy, Action, and bulk flow alike | P0 | — | — |
| DD-DEPT-002 | Deletion is a hard delete with no soft-delete machinery | P1 | — | — |
| DD-DEPT-003 | Dashboard invalidation is one listener on three events, not an observer | P1 | — | — |
| DD-DEPT-004 | Bulk delete evaluates per row and reports both outcomes instead of aborting | P1 | — | — |
| DD-DEPT-005 | Name caps at 255 and description at 1000 uniformly across form, Action, and schema | P1 | — | — |

### 7.1 Guards, Deletion, and Feedback

#### DD-DEPT-001 — The entity owns the guard, everyone else asks it

An early draft put the has-profiles check inside the policy, which worked right up until the
first console-driven cleanup script called the Action directly and deleted a populated
department without anything objecting. Moving the predicate into `DepartmentState` and having
the policy, the Action, and the bulk loop all consult it closed that hole three ways at once,
and the extra class paid for itself a second time when the dashboard reused the same profile
count. The ongoing cost is vigilance: every new deletion-adjacent feature must call the guard
instead of re-deriving it, and review is where that gets caught.

#### DD-DEPT-002 — Hard delete, with the log as the memory

Soft deletes were considered and rejected because departments carry no historical payload worth
resurrecting — unlike academic years, nothing joins to a department expecting its past states
to survive. The global scopes, trashed filters, and restore authorization that soft deletes
drag along would have touched every department query in the system to protect against an event
(the accidental deletion) already prevented by the guard. What is lost is undelete; what
replaces it is the audit entry holding everything needed to recreate the row on purpose.

#### DD-DEPT-003 — Cross-module cache news travels by event

The dashboard aggregates live in another module's territory, so synchronous same-model
observation would have coupled the wrong things: departments should not know or care how the
dashboard caches. The union-typed listener keeps the mapping explicit — three events in, one
key forgotten — and leaves room for future subscribers (a notification when a major is created,
an analytics tap) without reopening the Actions. The price is one deferred hop, measured in
milliseconds, against coupling that would have lasted years.

#### DD-DEPT-004 — Partial success beats pure abort for batches

Academic years abort bulk deletion on the first protected row, and that is correct there,
because year deletion is rare and every survivor deserves scrutiny. Departments are the
opposite: cleanup batches are large, blocked rows are routine unfinished reassignments, and
aborting eleven good deletions over three blocked ones punishes the admin for being thorough.
Per-row evaluation with dual counts matches the domain's tolerance — and the two specs
deliberately differ here, which is why the rationale is written down instead of assumed.

#### DD-DEPT-005 — One pair of length caps everywhere

The schema allowed 255 while an early Action draft enforced 100, so a legitimate long major
name passed the form and died in the Action — the worst kind of validation bug, where two
layers disagree and the user cannot tell which one to believe. Aligning form, Action, and
column on 255 for names and 1000 for descriptions removed the contradiction at the cost of
permitting longer names than any school will use, a trade nobody has regretted.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Orphaned profiles after department deletion | 0 | Guard unit tests plus delete-action feature tests |
| Duplicate department names | 0 | Unique constraint plus double-submit feature test |
| Mutations without an audit entry | 0 | Activity-channel assertion per mutation test |
| Stale dashboard counts after department change | 0 | Listener test asserting cache forget per event |
| Blocked deletions lacking a count and remedy | 0 | Message-content assertion on guard rejection |
| Hardcoded user-facing strings | 0 | D3 scan on the module |

---

## 9. Roadmap

### Prerequisites

The school profile spec ([81SMS](81SMS-school-profile.md)) must be complete first, since
departments belong to the school it defines, and the settings infrastructure
([YB22J](YB22J-settings-infrastructure.md)) underpins the configuration the manager relies on.

### Build Guide

With this spec implemented, the system can group profiles into majors with a guarded lifecycle
and trustworthy dashboard counts. Departments are the join key that companies hire against and
that academic years schedule within, so this spec unlocks both neighboring phases.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [academic-year-management](XW6F5-academic-year-management.md) | Programs scope to a year plus a department; years are the temporal twin of this spec's org chart |
| 2 | [company-management](XI3LB-company-management.md) | Companies offer internship slots against specific departments |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume department names are unique per school without needing a per-school scope, which holds while the product stays single-tenant | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — Action Triad, entity split, and event contracts this spec builds on
- [Spec-zero](QLHDO-project-initialization.md) — global requirements (FR-GLB-001/002/004/007/008/010) inherited here
- [Academic year management](XW6F5-academic-year-management.md) — temporal counterpart to departments
- [Company management](XI3LB-company-management.md) — slots offered against departments
- [CSV import/export](O2KCR-csv-import-export.md) — bulk department onboarding pipeline
- [ADR: entity-model separation](../adr/adr-entity-model-separation.md) — why the guard lives in the entity
- [ADR: action pattern over services](../adr/adr-action-pattern-over-services.md) — why one Action per operation
- [ADR: exception hierarchy](../adr/adr-exception-hierarchy.md) — `RejectedException` as the business-refusal voice
- [ADR: smartlogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — activity audit with PII masking
- [ADR: flat RBAC](../adr/adr-flat-rbac-with-functional-roles.md) — admin-group gating for writes
- [ADR: observers](../adr/adr-eloquent-observers.md) — why cache news travels by event here
