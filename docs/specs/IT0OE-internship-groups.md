# IT0OE — Internship Groups

> **Spec ID:** IT0OE
> **Status:** Full
> **Owner:** Program
> **Depends on:** [7C5WM](7C5WM-internship-lifecycle.md)

## Description

Defines cohort management inside internship programs: group definition with optional
placement association, role-based member management across students, teachers, and
supervisors, batch member intake through a repeater, and lifecycle guards around deletion
and deactivation. Program lifecycle, status, and readiness live in
[7C5WM](7C5WM-internship-lifecycle.md); enrollment intake lives in
[MBB5R](MBB5R-registration.md).

---

## 1. Problem Statements

### PS-1 — Group Member Management Needs Role-Specific Add Flows

Groups mix three populations with different identities: students arrive via their
registration, teachers and supervisors via their user accounts. One undifferentiated add
form either asks everyone for everything or guesses wrong, and duplicate memberships slip
through without pair-level uniqueness. The intake must adapt per role and refuse
duplicates at both the database and application layers.
**→ Requirement:** FR-GROUP-013–FR-GROUP-023 (member intake and validation).

### PS-2 — Group Deletion Must Not Orphan Members

Deleting a group that still holds members strands those member rows and anything pointing
at them. The system must see the members and refuse, forcing explicit removal first —
deletion becomes a conscious two-step act rather than a one-click accident.
**→ Requirement:** FR-GROUP-004–FR-GROUP-005, FR-GROUP-009 (deletion guard).

### PS-3 — Groups Reference Placements Without Owning Quotas

A group is a cohort attached to a company placement slot, but slot capacity belongs to
the Placement layer. If groups enforced their own capacity, two quota systems would drift
apart and disagree at enrollment time. The group holds a nullable reference; the
Placement layer owns the arithmetic.
**→ Requirement:** FR-GROUP-002 (placement association), DD-GROUP-001 (capacity placement).

### PS-4 — Single-Row Member Add Hinders Bulk Enrollment

Onboarding a thirty-student cohort one row per save means thirty open-fill-save cycles of
tedium and transcription risk. Admins need a repeater — as many rows as the cohort
requires, validated together, written atomically — so the batch either lands whole or not
at all.
**→ Requirement:** FR-GROUP-021–FR-GROUP-023 (batch repeater intake).

---

## 2. Goals & Non-Goals

### Goals

- **Role-adapted member intake** — student rows ask for a registration, mentor rows ask for a user account. *Why:* the three populations are identified by genuinely different records.
- **Deletion blocked while members exist** — explicit removal precedes deletion. *Why:* orphaned member rows break registration state downstream.
- **Pair-level uniqueness** — no duplicate group-plus-registration or group-plus-mentor pairs. *Why:* duplicates inflate member counts and confuse supervision assignments.
- **Group CRUD with placement association** — groups belong to internships and optionally reference placements. *Why:* cohorts need a program home and a company context.
- **Join timestamps on every membership** — `joined_at` recorded at creation. *Why:* cohort history and tenure questions need a start date.
- **Deactivation without deletion** — `is_active` retires groups while preserving history. *Why:* past cohorts remain queryable after they stop operating.
- **Batch intake in one save** — repeater rows validated together and written in one transaction. *Why:* cohort onboarding must be fast and atomic.

### Non-Goals

- **Program lifecycle and readiness**. *Why:* owned by [7C5WM](7C5WM-internship-lifecycle.md).
- **Group-level capacity enforcement**. *Why:* quotas live in the Placement layer; a second counter would drift.
- **Student self-service joining**. *Why:* membership is admin-managed; open enrollment invites quota chaos.
- **Bulk file import or export of members**. *Why:* manual intake covers MVP cohort sizes; file pipelines are post-MVP depth.

---

## 3. User Stories / Use Cases

Three admin journeys: create the cohort container, fill it with people, and retire it when
its season ends.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-GROUP-001 | Admin creates a group under an internship with an optional placement association | P0 | F | Full |
| UC-GROUP-002 | Admin adds members in a batch through role-adapted repeater rows, all-or-nothing, and removes members individually | P0 | F | Full |
| UC-GROUP-003 | Admin deactivates a group without deleting it, preserving members and history | P1 | F | Full |

### 3.1 Cohort Setup

#### UC-GROUP-001 — Admin Creates a Group

The coordinator has an internship with two company sites and needs two cohorts to match.
Creating a group means naming it, pointing it at the internship, and optionally pinning
the placement — thirty seconds per cohort, and the member count column starts at zero
waiting to be filled. The creation log entry records who set it up, because six months
later someone always asks.

### 3.2 Membership

#### UC-GROUP-002 — Admin Fills and Prunes the Cohort

Enrollment week looks like this: the admin opens member management, keeps clicking add-row
until the modal mirrors the paper roster, picks a role per row, and types registrations
for students and user accounts for mentors. One submit validates every row — a single bad
registration ID blocks the whole batch rather than landing a partial cohort — and the
transaction writes them all with the current timestamp. Later, individual departures are
removed one by one, and deleting the group itself stays refused until the last member is
gone.

### 3.3 Retirement

#### UC-GROUP-003 — Admin Deactivates a Group

Seasons end but history must not. Flipping `is_active` retires the group from active
views while members, logs, and timestamps stay exactly where they were. Deactivation is
reversible in a way deletion never is, which is why coordinators reach for it first and
why hard deletion keeps its member guard regardless.

---

## 4. Functional Requirements

The table below is the complete normative list. Groups in §4.1–§4.4 collect the detail
narratives; the table itself is the single source of requirement rows.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-GROUP-001 | `InternshipGroup` model uses `#[Fillable]` with name, internship_id, placement_id, description, is_active | P0 | A | Full |
| FR-GROUP-002 | `InternshipGroup` relates belongsTo Internship (cascade on delete), nullable belongsTo Placement, hasMany InternshipGroupMember | P0 | A | Full |
| FR-GROUP-003 | `InternshipGroup` exposes `asInternshipGroupState()` bridging to `InternshipGroupState` | P0 | U | Full |
| FR-GROUP-004 | `InternshipGroupState` tracks member count and active flag | P0 | U | Full |
| FR-GROUP-005 | `InternshipGroupState::canBeDeleted()` is false whenever members exist | P0 | U | Full |
| FR-GROUP-006 | `InternshipGroupState::isActive()` reflects the `is_active` boolean | P1 | U | Full |
| FR-GROUP-007 | `CreateInternshipGroupAction` creates a group with internship, name, and optional placement and description | P0 | F | Full |
| FR-GROUP-008 | `UpdateInternshipGroupAction` updates a group from a data array inside a transaction | P0 | F | Full |
| FR-GROUP-009 | `DeleteInternshipGroupAction` refuses via `RejectedException` when the group is not deletable | P0 | F | Full |
| FR-GROUP-010 | `InternshipGroupManager` lists name, internship title, member count, and actions | P1 | F | Full |
| FR-GROUP-011 | `InternshipGroupManager` extends `BaseRecordManager` with name search and member-count eager loading | P1 | F | Full |
| FR-GROUP-012 | `InternshipGroupData` DTO requires internshipId and name and accepts optional placementId and isActive | P0 | U | Full |
| FR-GROUP-013 | `InternshipGroupMember` model uses `#[Fillable]` with internship_group_id, registration_id, user_id, role, joined_at | P0 | A | Full |
| FR-GROUP-014 | `InternshipGroupMember` relates belongsTo InternshipGroup, Registration, and mentor User | P0 | A | Full |
| FR-GROUP-015 | `InternshipGroupRole` defines STUDENT, SCHOOL_TEACHER, INDUSTRY_SUPERVISOR and implements `LabelEnum` | P0 | U | Full |
| FR-GROUP-016 | `AddMemberToGroupAction` creates one member with role, registration or mentor reference, and current timestamp | P0 | F | Full |
| FR-GROUP-017 | Single member addition executes inside a transaction | P0 | F | Full |
| FR-GROUP-018 | `RemoveMemberFromGroupAction` logs the removal and deletes the member inside a transaction | P0 | F | Full |
| FR-GROUP-019 | Single member intake validates role, student registration reference, and mentor user reference per role | P0 | F | Full |
| FR-GROUP-020 | Member removal authorizes update permission on the parent group | P0 | F | Full |
| FR-GROUP-021 | `AddMembersToGroupAction` creates every batch row in one transaction and returns the created count | P0 | F | Full |
| FR-GROUP-022 | The manager exposes repeater row handling: row data array, add-row, remove-row by index, and form reset | P0 | F | Full |
| FR-GROUP-023 | Batch intake validates all repeater rows before writing; one invalid row fails the entire batch | P0 | F | Full |
| FR-GROUP-024 | `InternshipGroupPolicy` extends `BasePolicy` | P0 | A | Full |
| FR-GROUP-025 | Group view permissions allow all users | P1 | U | Full |
| FR-GROUP-026 | Group create, update, and delete require the admin role | P0 | U | Full |
| FR-GROUP-027 | `InternshipGroupManager` serves `/admin/internships/groups` behind admin middleware | P0 | F | Full |
| FR-GROUP-028 | `InternshipGroupManager` extends `BaseRecordManager` | P1 | A | Full |
| FR-GROUP-029 | The manager provides group CRUD modal, member modal, and delete confirmation states | P1 | F | Full |
| FR-GROUP-030 | The manager computes available internships and role options for its forms | P1 | F | Full |
| FR-GROUP-031 | `InternshipGroupForm` validates group fields before save | P1 | F | Full |

### 4.1 Group Definition

#### FR-GROUP-001 — Fillable contract on InternshipGroup

Five named attributes may be bulk-filled; the internship link, placement link, name,
description, and active flag cover everything a group creation form legitimately sends.
Anything beyond this list — member manipulation smuggled into a group payload, say —
takes the explicit per-member path instead. The allow-list is short on purpose.

#### FR-GROUP-002 — Relations with cascade intent

The internship link cascades on delete because a group without its program is
meaningless, while the placement link stays nullable because not every cohort maps to
a slot yet. Members hang off the group so the guard in FR-GROUP-005 has something to
count. Each relation's delete behavior answers "what should survive what" before the
question gets asked in production.

#### FR-GROUP-003 — State bridge off the group

Deletion and activity questions belong to an entity, not to attribute spelunking across
callers. The bridge hands back an `InternshipGroupState` built from the members
relation and the active flag, so every consumer reasons about the same snapshot shape.
Model changes ripple to one method instead of every place that ever counted members.

#### FR-GROUP-004 — What the state carries

Member count plus active flag: the two facts every lifecycle question reduces to.
Whether the count comes from a loaded relation or a fresh query is an implementation
detail callers never see. Keeping the state this thin is what makes it cheap to build
anywhere — lists, guards, policies — without performance anxiety.

#### FR-GROUP-005 — Deletable means empty

The predicate is a single negation: members present, deletion refused. No grace
thresholds, no "only students block but mentors don't" subtleties — any member blocks.
Simplicity here is protective; every exception anyone proposes later is a future
orphan story waiting to be written.

#### FR-GROUP-006 — Active reflects the flag

`isActive()` answers with the stored boolean, nothing derived, nothing clever. Derived
activity — "inactive because its internship completed" — was considered and rejected:
the flag means what the admin set, and other layers interpret context around it.
A predicate that sometimes means the flag and sometimes means the world is a liar.

#### FR-GROUP-007 — Creation vocabulary

Internship plus name, with placement and description optional: the whole creation
language in one call. Optionality mirrors reality — cohorts often exist before their
company slot is confirmed. The creation log entry captures the moment so the group's
origin never becomes tribal knowledge.

#### FR-GROUP-008 — Updates stay transactional

Group edits are small but never unguarded: the update runs inside a transaction with
logging, like every other Command. Today's edit changes a name; tomorrow's might touch
relations. Uniform ceremony means the second case inherits safety nobody had to
remember to add.

#### FR-GROUP-009 — Refusal with a reason

When members block deletion, the Action throws `RejectedException` rather than
returning a quiet false the caller might ignore. Exceptions cannot be shrugged off;
return codes can. The message names the situation plainly so the admin's next step —
remove members first — is obvious without consulting documentation.

#### FR-GROUP-010 — Manager list columns

Name, internship, member count, actions: the coordinator's scanning pattern compressed
into four columns. Member count earns its place because "which cohorts are still
empty" and "which are full" are daily questions during onboarding. Everything else
lives behind the row actions, one click away but not competing for attention.

#### FR-GROUP-011 — Record-manager inheritance and counting

Inheriting the base record manager buys search, sort, and pagination for free, while
`withCount('members')` keeps the count column to one query instead of one per row.
Without the eager count, a hundred-group list would fire a hundred queries every
morning of enrollment week — the classic N+1 wearing a cohort costume.

#### FR-GROUP-012 — Group DTO shape

Two required fields, two optional: the DTO mirrors the creation vocabulary exactly.
Forms map onto it without transformation gymnastics, and the Action receives one
typed object instead of an array whose keys might be misspelled. Small DTOs like this
are where the boundary discipline starts feeling effortless.

### 4.2 Member Intake

#### FR-GROUP-013 — Fillable contract on members

Group, registration, mentor, role, timestamp: the membership row's entire vocabulary.
Registration and mentor stay independently nullable because a row carries one identity
or the other depending on role, never both. The timestamp is filled by the Action,
not the form, so clients cannot backdate membership.

#### FR-GROUP-014 — Three belongs-to links

Each member points at its group, and at either a registration or a mentor user. The
dual identity looks odd until you meet the domain: students are enrollments first and
people second, mentors are people first. One table with two nullable links beats two
parallel tables that every query would have to union.

#### FR-GROUP-015 — Three roles, labeled

Student, school teacher, industry supervisor: the full cast of a cohort, each with a
translatable label for display. The enum closes over the set — a fourth population
would need a spec amendment, which forces the conversation about what the newcomer
even means for supervision assignments. Labels come from the enum so every dropdown
agrees on wording.

#### FR-GROUP-016 — Single add writes the timestamp

One call creates one member with its role-appropriate reference and stamps `joined_at`
with now. Server-side timestamping matters because membership tenure feeds reporting;
client-supplied dates would let a typo rewrite cohort history. The Action owns the
clock, full stop.

#### FR-GROUP-017 — Single add is transactional

Even a one-row write runs in a transaction with logging. Consistency of ceremony beats
optimizing the trivial case: when the single-add path later grows a side effect, the
safety is already there. Reviewers never have to ask whether this write is protected.

#### FR-GROUP-018 — Removal logs before deleting

Every departure writes its farewell note first — who removed whom, when — and then
deletes inside the same transaction. Cohort membership disputes ("I was never in that
group") get answered from the log instead of from memory. The ordering guarantees the
record exists exactly when the deletion it describes commits.

#### FR-GROUP-019 — Per-role validation

Student rows must name an existing registration; mentor rows must name an existing
user; the role itself must be a known enum case. Conditional requirements mirror the
conditional form, and validating at the manager before calling the Action keeps bad
rows from ever reaching the transaction. A wrong-ID typo fails fast with the row
identified, not deep inside a batch write.

#### FR-GROUP-020 — Removal needs group authority

Removing a member requires update permission on the parent group, checked before the
delete begins. Membership is a group-level concern, so group-level authority governs
it — no separate member permission matrix to keep in sync. An attacker holding a
member ID but no group rights gets nowhere.

### 4.3 Batch Intake

#### FR-GROUP-021 — All rows, one transaction, one count

The batch Action writes every row inside a single transaction and reports how many it
created. Partial landing — twenty rows written, three failed — would leave the cohort
in a state matching nobody's roster. Atomicity plus a returned count lets the UI
confirm "all thirty landed" in one breath.

#### FR-GROUP-022 — Repeater plumbing on the manager

The row array with add, remove-by-index, and reset operations is the entire repeater
contract. Rows are plain data until submit, so adding and removing them is cheap and
undoable. Reset clears the slate after a successful save, ready for the next cohort
without stale rows haunting the modal.

#### FR-GROUP-023 — All-or-nothing validation

Every row is validated before anything is written, and one invalid row vetoes the
batch. Validating up front — rather than failing midway through the transaction —
means the admin sees all problems at once instead of fixing them one painful retry at
a time. The cohort on paper and the cohort in the database stay identical by
construction.

### 4.4 Policy, Routing, and Forms

#### FR-GROUP-024 — Policy lineage

Extending the base policy inherits the role and ownership plumbing every policy in
the system shares. New policy authors start from working defaults instead of a blank
file where the ownership check is easy to forget. Lineage is how the flat RBAC model
stays flat instead of drifting into per-module dialects.

#### FR-GROUP-025 — Open read access

Any logged-in user may view groups — students finding their cohort, mentors checking
assignments. Group composition is organizational trivia, not sensitive data, and
gating it would add friction to every coordination conversation without protecting
anything real. Reads stay open; writes stay guarded.

#### FR-GROUP-026 — Admin-only mutation

Creating, editing, and deleting groups belongs to admins alone. Cohort structure
shapes supervision assignments and reporting, so it cannot be self-service without
inviting chaos. The role check runs in the policy and is re-asserted in the Actions,
so neither layer trusts the other to have asked.

#### FR-GROUP-027 — Route behind admin middleware

The manager lives under the admin prefix with role middleware, unreachable to students
and mentors even by direct URL. Route-level gating is the outermost shell: it turns
unauthorized visits away before any component state loads. Inner layers re-check, but
the door itself stays locked.

#### FR-GROUP-028 — Manager lineage

Inheriting the base record manager gives the groups table its search, filter, sort,
and pagination without bespoke code. The fortieth admin table behaving exactly like
the first is a feature, not a coincidence — coordinators learn one interface and
operate them all. Bespoke tables are where inconsistencies breed.

#### FR-GROUP-029 — Three modal states

Group editing, member intake, and delete confirmation each own a modal state on the
manager. Separating them keeps the flows from tangling — the delete confirm never
inherits half-filled member rows, the member modal never carries group-edit drafts.
Explicit states also make each flow independently testable through the component.

#### FR-GROUP-030 — Computed form options

The internship dropdown and role options compute from live data rather than hardcoded
lists. New internships appear as choices without a code change; role wording follows
the enum labels. Forms that derive their options cannot drift out of sync with the
domain they serve.

#### FR-GROUP-031 — Form validates before save

Group fields are validated in the form object before the Action ever runs, catching
missing names and bad references at the boundary. Early validation keeps invalid
payloads from consuming transactions and log entries. The Action trusts but verifies —
its own guards remain as the second line.

---

## 5. Non-Functional Requirements

Constraints on how cohort management behaves. `N/A` marks requirements enforced
structurally and verified via scans or tests rather than measured at runtime.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-GROUP-001 | Member uniqueness enforced at database constraints and application checks for group-plus-registration and group-plus-mentor pairs | N/A | P0 | F | Full |
| NFR-GROUP-002 | Member add and removal authorize update permission on the parent group | N/A | P0 | A | Full |
| NFR-GROUP-003 | Member modal adapts inputs per repeater row by role: registration reference for students, mentor reference otherwise | N/A | P1 | B | Full |
| NFR-GROUP-004 | Blocked deletions explain which members prevent the deletion | N/A | P1 | B | Full |
| NFR-GROUP-005 | Member counts refresh immediately after add and remove operations | N/A | P1 | B | Full |
| NFR-GROUP-006 | Delete confirmation names the group before the admin commits | N/A | P1 | B | Full |
| NFR-GROUP-007 | All user-facing strings pass through the `__()` helper with keys in both English and Indonesian files | N/A | P0 | A | Full |
| NFR-GROUP-008 | Role labels render through `LabelEnum::label()` with labeled, keyboard-navigable inputs throughout the modals | N/A | P1 | B | Full |

### 5.1 Safety

#### NFR-GROUP-001 — Uniqueness at two levels

Database constraints make duplicates impossible; application checks make them
explainable. The constraint is the backstop that survives race conditions during
enrollment-week double-clicks, while the application check turns the attempt into a
row-level error instead of a 500 page. Either alone would be half a solution.

#### NFR-GROUP-002 — Authority flows from the group

Nobody asks "may this user edit memberships" in the abstract — the question is always
"may they change this group." Anchoring member authorization to the parent group's
update permission keeps the model to one check instead of a parallel member-permission
universe. Simplicity here is what makes the authorization auditable.

### 5.2 Operability

#### NFR-GROUP-003 — Forms that reshape per row

Each repeater row shows the inputs its role needs and hides the rest, so a student row
asks for a registration while a mentor row asks for a user. Static forms showing both
fields invite the classic error — registration typed into the mentor box — that
per-row adaptation eliminates. The form mirrors the validation, and both mirror the
domain.

#### NFR-GROUP-004 — Refusals that teach

A blocked deletion names its blockers — how many members, of which kinds — turning a
dead end into a task list. The alternative, a bare refusal, reliably produces a
support ticket and a frustrated coordinator. Good error text is cheaper than support
time, and this is the screen where that trade pays off most visibly.

#### NFR-GROUP-005 — Counts that keep up

The member count requeries the moment rows change, so the list never shows a stale
zero beside a just-filled cohort. Stale counts erode trust fast: admins re-add members
they believe went missing and create the very duplicates the uniqueness rules then
have to refuse. Fresh counts close that loop.

#### NFR-GROUP-006 — Confirm with the name on it

The delete dialog shows the group's name in full before the irreversible click.
Coordinators managing similarly named cohorts — "Kelompok A" across three internships
— need that last-moment disambiguation. A generic "are you sure" protects against
nothing; a named confirmation protects against the specific, common, devastating
mistake.

### 5.3 Localization and Presentation

#### NFR-GROUP-007 — Bilingual by construction

Every string passes through the translation helper with mirrored English and
Indonesian keys. The school office works in Indonesian; the product's second audience
reads English. Missing keys render as raw identifiers in exactly the UI the staff
trusts most, so the mirrored-files rule exists to keep that trust intact.

#### NFR-GROUP-008 — Labels everywhere, keyboard throughout

Role wording comes from the enum's label method so dropdowns, badges, and exports
agree. Every modal input carries an associated label and a keyboard path — the office
computers vary wildly, and mouse-only flows strand the admins on older hardware. This
row folds the former separate labeling, focus, and role-label rules into one
operability standard.

---

## 6. API / Data Contracts

### 6.1 InternshipGroupRole Enum

```php
// app/Modules/Program/InternshipGroup/Enums/InternshipGroupRole.php
enum InternshipGroupRole: string implements LabelEnum
{
    case STUDENT = 'student';
    case SCHOOL_TEACHER = 'school_teacher';
    case INDUSTRY_SUPERVISOR = 'industry_supervisor';

    public function label(): string;
}
```

### 6.2 InternshipGroup Model

```php
// app/Modules/Program/InternshipGroup/Models/InternshipGroup.php
#[Fillable(['name', 'internship_id', 'placement_id', 'description', 'is_active'])]
class InternshipGroup extends BaseModel
{
    // Casts: is_active → boolean
    // Relations: belongsTo Internship (cascadeOnDelete), belongsTo Placement (nullable),
    //            hasMany InternshipGroupMember
    // Bridge: asInternshipGroupState() → InternshipGroupState
}
```

### 6.3 InternshipGroupMember Model

```php
// app/Modules/Program/InternshipGroup/Models/InternshipGroupMember.php
#[Fillable(['internship_group_id', 'registration_id', 'user_id', 'role', 'joined_at'])]
class InternshipGroupMember extends BaseModel
{
    // Casts: joined_at → datetime
    // Relations: belongsTo InternshipGroup, belongsTo Registration,
    //            belongsTo User (user_id — mentor)
}
```

### 6.4 InternshipGroupState Entity

```php
// app/Modules/Program/InternshipGroup/Entities/InternshipGroupState.php
final readonly class InternshipGroupState extends BaseEntity
{
    public function __construct(private int $memberCount, private bool $isActive) {}

    public static function fromModel(Model $model): static;
    public function isActive(): bool;
    public function hasMembers(): bool;       // memberCount > 0
    public function canBeDeleted(): bool;     // !hasMembers()
}
```

### 6.5 InternshipGroupData DTO

```php
// app/Modules/Program/InternshipGroup/Data/InternshipGroupData.php
final readonly class InternshipGroupData extends BaseData
{
    public function __construct(
        public string $internshipId,
        public string $name,
        public ?string $placementId = null,
        public ?bool $isActive = null,
    ) {}
}
```

### 6.6 Action Signatures

```php
// app/Modules/Program/InternshipGroup/Actions/CreateInternshipGroupAction.php
final class CreateInternshipGroupAction extends BaseCommandAction
{
    public function execute(array $data): InternshipGroup;
}

// app/Modules/Program/InternshipGroup/Actions/UpdateInternshipGroupAction.php
final class UpdateInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group, array $data): InternshipGroup;
}

// app/Modules/Program/InternshipGroup/Actions/DeleteInternshipGroupAction.php
final class DeleteInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group): void;
}

// app/Modules/Program/InternshipGroup/Actions/AddMemberToGroupAction.php
final class AddMemberToGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group, array $data): InternshipGroupMember;
}

// app/Modules/Program/InternshipGroup/Actions/AddMembersToGroupAction.php
final class AddMembersToGroupAction extends BaseProcessAction
{
    public function execute(InternshipGroup $group, array $rows): int;
}

// app/Modules/Program/InternshipGroup/Actions/RemoveMemberFromGroupAction.php
final class RemoveMemberFromGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroupMember $member): void;
}
```

### 6.7 Policy

```php
// app/Modules/Program/InternshipGroup/Policies/InternshipGroupPolicy.php
class InternshipGroupPolicy extends BasePolicy
{
    public function viewAny(?User $user): bool;  // all users
    public function view(?User $user, InternshipGroup $group): bool;  // all users
    public function create(User $user): bool;    // admin only
    public function update(User $user, InternshipGroup $group): bool; // admin only
    public function delete(User $user, InternshipGroup $group): bool; // admin only
}
```

### 6.8 Routes

```php
// routes/web/program.php
Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships/groups', InternshipGroupManager::class)->name('internships.groups');
    });
```

---

## 7. Design Decisions

Six recorded decisions, narrated inline.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-GROUP-001 | Group capacity left to the Placement layer; groups hold a reference, not a counter | P1 | — | — |
| DD-GROUP-002 | One member-intake Action with role branching instead of per-role Actions | P1 | — | — |
| DD-GROUP-003 | Membership stored as a dedicated model with role and timestamp, not a bare pivot | P0 | — | — |
| DD-GROUP-004 | Group read access open to all users while mutation stays admin-only | P1 | — | — |
| DD-GROUP-005 | Deactivation flag for retirement alongside guarded hard deletion | P1 | — | — |
| DD-GROUP-006 | Batch intake through a repeater with all-or-nothing transaction semantics | P0 | — | — |

### 7.1 Boundaries

#### DD-GROUP-001 — Capacity Belongs to Placements

Two quota systems inevitably disagree, so there is exactly one: the Placement layer's.
Groups reference the slot and let enrollment-time checks do the arithmetic. A group that
can technically overshoot its slot looks like a hole until you see the enrollment guard
standing behind it — the hole is fenced, just not at the group boundary.

#### DD-GROUP-002 — One Intake, Role Branches

Three near-identical Actions — add student, add teacher, add supervisor — would triple
the transaction and logging ceremony for logic that differs in one field. A single
Action with a student path and a mentor path keeps the surface small while the Livewire
validation keeps the branches honest. Conditional logic inside, conditional form
outside, each side doing what it is good at.

#### DD-GROUP-003 — Members as First-Class Rows

Role and join timestamp turned the membership from a link into a fact, and facts
deserve their own model with a UUID, timestamps, and room to grow. A bare pivot would
have forced role lookups elsewhere and a migration the moment anyone wanted member
notes or evaluation links. The extra table is the cheapest future-proofing in the
module.

### 7.2 Access and Lifecycle

#### DD-GROUP-004 — Open Reads, Guarded Writes

Cohort composition is coordination fuel — students finding teammates, mentors checking
rosters — and gating it would tax every conversation for zero security gain. The
school-internal visibility is accepted openly rather than leaked accidentally. Writes
stay admin-only, which is where the actual power to disrupt lives.

#### DD-GROUP-005 — Retire Softly, Delete Strictly

Deactivation preserves the past while removing the group from the present; hard
deletion, allowed only when empty, removes the row entirely. Both exist because
neither alone covers the lifecycle: history needs preserving and mistakes need
undoing. Inactive rows cost storage measured in kilobytes against the audit value
they retain.

#### DD-GROUP-006 — Repeater With Atomic Commit

Cohort onboarding one row per save was measured in frustration, so the modal grows
rows freely and commits them as one transaction. All-or-nothing keeps the database
cohort identical to the paper roster the admin copied — no partial landings to
reconcile. Serial row processing inside the batch is plenty fast at real cohort
sizes, and admin-only access bounds the abuse surface.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Deletion guard | Every deletion attempt on a non-empty group refused | `canBeDeleted()` tests with seeded members |
| Uniqueness enforcement | Zero duplicate registration or mentor pairs per group | Constraint plus Action tests |
| Role-correct intake | Each role resolves to its correct reference field | Per-role intake tests |
| Join timestamp coverage | Every member carries a creation timestamp | Creation path assertions |
| Removal audit | Every removal writes a log entry | Removal trail checks |
| Batch atomicity | No partial batch ever persists | Transaction plus validation tests |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [internship-lifecycle.md](7C5WM-internship-lifecycle.md) | Internship program entities — groups belong to programs |

### Build Guide

After this spec, the system holds group CRUD with student and mentor assignment,
deactivation, and guarded deletion. Groups organize students into cohorts for
supervision and scheduling. The next step is registration, which enrolls students into
programs and feeds members into these groups.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [registration.md](MBB5R-registration.md) | Registration creates enrollment records that are assigned to groups from this spec |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|----------------------------------|--------|-------|----------|
| A-1 | We assume cohort sizes stay within manual repeater intake until a bulk file flow is requested | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Programs and Enrollment phases, all specs in build order
- [Internship lifecycle](7C5WM-internship-lifecycle.md) — program status, windows, closure, archival
- [Registration](MBB5R-registration.md) — enrollment records assigned into groups
- [Placement](J9GBH-placement.md) — slot quotas that bound group intake
- [Flat RBAC with functional roles ADR](../adr/adr-flat-rbac-with-functional-roles.md) — role model behind group permissions
- [Action pattern](../guides/arch/action-pattern.md) — Command and Process contracts for member flows
- [Entity pattern](../guides/arch/entity-pattern.md) — state entity conventions
