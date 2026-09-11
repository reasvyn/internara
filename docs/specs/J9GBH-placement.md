# Placement — CRUD, Capacity Management & Change Requests

> **Spec ID:** J9GBH
> **Status:** Full
> **Owner:** Enrollment
> **Depends on:** [MBB5R](MBB5R-registration.md), [XI3LB](XI3LB-company-management.md)

## Description

Placement assigns enrolled students to partner-company slots and guards the two invariants the
whole PKL lifecycle stands on: no company ever holds more students than its quota, and no student
ever exists without exactly one accountable placement. It covers placement CRUD, atomic capacity
tracking, admin direct placement with mentor assignment, and the mid-program change-request
workflow. Registration and guest account applications are separate concerns — see
[registration](MBB5R-registration.md) and [account-application](920SO-account-application.md).

---

## 1. Problem Statements

### PS-1 — Placement Capacity Atomicity

Each company placement carries a finite quota. During enrollment week dozens of registrations
land within minutes, and an admin performing direct placement can collide with a student
self-registering into the same slot. A naive check-then-act (`if slots > 0 then increment`)
leaves a race window where two concurrent requests both observe a free slot and both succeed,
pushing the company over quota and forcing an embarrassing manual un-placement after the fact.
**→ Requirement:** FR-PLACE-010/011 (atomic direct placement), FR-PLACE-017 (atomic quota
transfer), NFR-PLACE-001/002 (quota integrity).

### PS-2 — Mid-Program Placement Change Requests

Students change companies mid-program: workplace conflict, family relocation, a supervisor who
stops showing up. Without a formal request workflow these moves happen over chat — the old slot
is never freed, the new slot is never decremented, and nobody can reconstruct afterward who
authorized the move. A structured request → review → approve/reject flow with atomic quota
transfer and a full audit trail is required.
**→ Requirement:** FR-PLACE-013–021 (change-request workflow), NFR-PLACE-004 (audit trail).

### PS-3 — Supervision Authority Must Follow the Student

A placement is not just a seat count; it determines who is accountable for the student. When a
teacher verifies a logbook or an admin reviews a change request, the system must resolve *which*
mentors own that registration — not by re-checking roles at the call site, but through the
single MentorEntity bridge every policy delegates to.
**→ Requirement:** FR-PLACE-026 (mentor bridge), FR-PLACE-027 (proxy pointer).

---

## 2. Goals & Non-Goals

### Goals

- **Enforce placement capacity atomically inside one transaction** — check and increment land together, so concurrent registrations can never overbook a company. *Why:* overbooking discovered after the fact means manually un-placing a student who already started work.
- **Run placement changes as request → review → approve/reject** — every move carries a reason, a reviewer, and a quota transfer. *Why:* chat-authorized moves desynchronize quota bookkeeping with no audit trail.
- **Reject duplicate placements for the same company and internship** — the unique constraint makes double-creation impossible, not merely unlikely. *Why:* duplicates split one company's students across two rows and corrupt every downstream count.
- **Give admins a stats dashboard with live available slots** — totals, filled, and remaining at a glance. *Why:* during enrollment week the coordinator's first question, asked fifty times a day, is "where is there still room?".
- **Support admin direct placement with mentor assignment** — the coordinator places a student and attaches mentors in one action. *Why:* phone-enrolled and walk-in students must enter the system without waiting for a self-service flow.
- **Resolve supervision through the MentorEntity bridge** — placement never invents its own role checks. *Why:* per the [cross-role proxy ADR](../adr/adr-cross-role-proxy.md), mentor authority has exactly one source of truth.

### Non-Goals

- **Registration workflow**. *Why:* owned by [MBB5R](MBB5R-registration.md); placement consumes registrations, it does not create the enrollment record itself (except the atomic direct-placement path).
- **Guest-to-student account application**. *Why:* owned by [920SO](920SO-account-application.md).
- **Automated placement matching**. *Why:* assignment is a human coordinator decision at MVP; algorithmic matching is post-MVP depth.
- **Student self-service peer swaps**. *Why:* every move needs admin review for quota and accountability; unsupervised swaps bypass both.
- **Real-time slot push notifications**. *Why:* polling the dashboard covers MVP; socket infrastructure is deferred per the [MVP trim ADR](../adr/adr-mvp-spec-trim.md).

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled
only where the UC has a code-testable consequence at this spec's scope.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-PLACE-001 | Student requests a placement change with a reason; admin approves or rejects with atomic quota transfer | P0 | F | Full |
| UC-PLACE-002 | Admin manages placements: CRUD, stats dashboard, and direct placement with mentor assignment | P0 | F | Full |

### 3.1 Change Requests

#### UC-PLACE-001 — Student Requests a Placement Change

A second-year student placed at a workshop across town learns the workshop is closing its
internship line halfway through the period. She opens the placement-change page, sees her
current placement alongside every other company in the same internship with free slots, picks
one, and writes two sentences about the closure. That reason text matters more than it looks:
three weeks later, when the coordinator audits why quota moved, the reason is the only thing
standing between a clean record and a mystery. The request lands as `PENDING`, the admin
reviews it with both placements visible, and approval swaps the registration and both quota
counters inside one transaction — or rejection records the rationale just as permanently.

### 3.2 Administration

#### UC-PLACE-002 — Admin Manages Placements

Enrollment-week morning: the coordinator opens the placement index before the first student
arrives. Totals, filled, available — one screen answering where room remains. A walk-in student
with a paper acceptance letter gets placed on the spot through the direct-placement form,
mentors attached, registration created, quota incremented, all in a single transaction. Later,
a company calls to raise its quota from four to six; the coordinator edits the row, and the
capacity entity immediately reports two new free slots. Nothing in this flow touches a second
screen or a manual recount, because every number on the dashboard derives from the same quota
columns the Actions guard.

---

## 4. Functional Requirements

A Functional Requirement is a verifiable behavior the system must support. `Priority` ranks
criticality on a P0–P3 scale; `Layer` declares the test layer that verifies it.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-PLACE-001 | Placement model uses a UUID v7 primary key with cascade delete on `company_id` and `internship_id` | P0 | A | Full |
| FR-PLACE-002 | Placement enforces a unique constraint on `(company_id, internship_id)` | P0 | F | Full |
| FR-PLACE-003 | Placement `quota` defaults to 1 and `filled_quota` defaults to 0 | P0 | F | Full |
| FR-PLACE-004 | `PlacementCapacity` entity exposes `isFull()`, `availableSlots()`, and `hasAvailableSlots()` | P0 | U | Full |
| FR-PLACE-005 | `PlacementState` entity exposes `registrationCount` and `canBeDeleted()` (true only when the count is zero) | P0 | U | Full |
| FR-PLACE-006 | `DeletePlacementAction` blocks deletion while any registration references the placement | P0 | F | Full |
| FR-PLACE-007 | `CreatePlacementAction` and `UpdatePlacementAction` perform validated CRUD on placement rows | P0 | F | Full |
| FR-PLACE-008 | `PlacementIndex` displays stats: total placements, total quota, filled slots, available slots | P1 | F | Full |
| FR-PLACE-009 | `PlacementIndex` supports search and filtering by company and internship | P1 | F | Full |
| FR-PLACE-010 | `DirectPlacementAction` atomically creates the registration and increments `filled_quota` in one transaction | P0 | F | Full |
| FR-PLACE-011 | `DirectPlacementAction` refuses targets with no available slots | P0 | F | Full |
| FR-PLACE-012 | `DirectPlacementManager` provides an admin form selecting student, placement, and mentors | P0 | F | Full |
| FR-PLACE-013 | `PlacementChangeStatus` enum implements the `LabelEnum` and `StatusEnum` contracts | P0 | U | Full |
| FR-PLACE-014 | Valid transitions are `PENDING` → `APPROVED` or `REJECTED`; both endpoints are terminal | P0 | U | Full |
| FR-PLACE-015 | `RequestPlacementChangeAction` refuses a new request while a `PENDING` one exists for the registration | P0 | F | Full |
| FR-PLACE-016 | `ApprovePlacementChangeAction` requires a non-terminal request and a target placement with free slots | P0 | F | Full |
| FR-PLACE-017 | `ApprovePlacementChangeAction` atomically decrements the old `filled_quota`, increments the new one, and repoints the registration | P0 | F | Full |
| FR-PLACE-018 | `RejectPlacementChangeAction` records `rejection_reason` and transitions to `REJECTED` | P0 | F | Full |
| FR-PLACE-019 | `PlacementChangeManager` lists pending requests for admin review | P0 | F | Full |
| FR-PLACE-020 | `StudentPlacementChangeRequest` lists same-internship placements with free slots, excluding the current one | P0 | F | Full |
| FR-PLACE-021 | `PlacementChangeRequestPolicy` governs who may create and who may review requests | P0 | U | Full |
| FR-PLACE-022 | `PlacementIndex` serves full CRUD at `/admin/internships/placements` behind admin middleware | P0 | F | Full |
| FR-PLACE-023 | `DirectPlacementManager` serves at `/admin/internships/placements/direct` behind admin middleware | P0 | F | Full |
| FR-PLACE-024 | `PlacementChangeManager` serves at `/admin/internships/placements/changes` behind admin middleware | P0 | F | Full |
| FR-PLACE-025 | `StudentPlacementChangeRequest` serves at `/student/internships/placement-change` behind `role:student` middleware | P0 | F | Full |
| FR-PLACE-026 | Mentor assignment on a placement resolves through the `MentorEntity` bridge on the registration | P0 | U | Full |
| FR-PLACE-027 | Supervisor-scoped checks on placed students delegate to `MentorEntity` proxy gates (teacher-as-supervisor) | P0 | U | Full |
| FR-PLACE-028 | Status-transition guards live in the `PlacementChangeStatus` entity via `canTransitionTo()`; Actions delegate, never reimplement | P0 | U | Full |
| FR-PLACE-029 | Business-rule violations throw `RejectedException` carrying a translatable user-facing message | P0 | A | Full |

### 4.1 Placement CRUD & Capacity

#### FR-PLACE-001 — UUID keys with cascading parents

Deleting a company that still owns placement rows must not leave orphans pointing at a
vanished partner, and deleting an internship must not strand its placements either. Cascade
delete on both foreign keys keeps the graph consistent, while the UUID v7 primary key keeps
placement URLs unguessable and join-compatible with every other table per the
[UUID ADR](../adr/adr-uuid-primary-keys.md).

#### FR-PLACE-002 — One row per company and internship

The coordinator once created the same company placement twice — once as "PT Maju Jaya" and
once with a trailing space — and the two rows quietly split the company's students, so neither
row ever looked full while the factory floor overflowed. The unique constraint on the pair
makes that shape structurally impossible: the second insert fails loudly instead of
corrupting every downstream count.

#### FR-PLACE-003 — Sane quota defaults

A placement row created in a hurry — company picked, internship picked, quota field skipped —
lands with room for exactly one student and zero already counted. Those defaults encode the
common case (one company line, one intern to start) while keeping the arithmetic honest from
the first row, since `filled_quota` starts at zero rather than null.

#### FR-PLACE-004 — Capacity predicates on the entity

Three tiny predicates carry the entire quota conversation: `isFull()` answers the guard, and
`availableSlots()` together with `hasAvailableSlots()` answer the dashboard and the student
facing list. They live on `PlacementCapacity`, a `final readonly` entity, so a reviewer can
unit-test the boundary (quota four, filled four, is full) in milliseconds without touching a
database — exactly the [entity-model separation](../adr/adr-entity-model-separation.md)
payoff.

#### FR-PLACE-005 — Deletability as a derived fact

Whether a placement may disappear is not stored anywhere; it is derived each time from the
live registration count. `canBeDeleted()` returns true only at zero, which means the delete
guard can never drift out of sync with reality the way a cached boolean would. Zero
registrations, deletable; one registration, untouchable — no third state.

#### FR-PLACE-006 — Deletion blocked while referenced

- The guard fires before the row disappears: the Action asks the entity, the entity counts registrations, and a nonzero count raises `RejectedException` with a message explaining the placement still has students.
- The alternative — cascading the delete into registrations — would silently un-place students, which is never an acceptable side effect of tidying a list.

#### FR-PLACE-007 — Validated create and update

Both write paths funnel through the same validation surface: quota is a non-negative integer,
the company and internship exist, the pair is unique. Because the Actions extend
`BaseCommandAction`, each write also runs inside a transaction with audit logging for free —
the CRUD pair stays thin while the base class carries the ceremony, per the
[action-pattern ADR](../adr/adr-action-pattern-over-services.md).

#### FR-PLACE-008 — Live stats on the index

Totals, quota, filled, available: four numbers the coordinator reads every morning of
enrollment week. They are computed from the same `quota` and `filled_quota` columns the
Actions guard, never from a cached rollup, so the dashboard can never disagree with the
enforcement. A placement edited a minute ago already shows its new free slots.

#### FR-PLACE-009 — Search and filter by company and internship

With two hundred partner companies, an unfiltered placement table is a wall of text nobody
scrolls. Typing three letters of a company name or narrowing to one internship collapses the
list to the rows that matter. The filters compose — company plus internship together — so the
exact row is reachable in two gestures during a phone call with a company liaison.

### 4.2 Direct Placement

#### FR-PLACE-010 — Atomic registration plus quota increment

The direct-placement Action creates the registration row and increments `filled_quota` inside
a single transaction. If the increment fails, the registration rolls back; if the
registration fails, the counter never moves. There is no observable instant where a student
is placed but uncounted, or counted but unplaced — the two writes are one fact.

#### FR-PLACE-011 — Full placements refuse direct entry

An admin placing a walk-in student into a company whose last slot filled an hour ago gets a
clear rejection, not a silent overbooking. The slot check runs inside the same transaction as
the write, closing the race window between the coordinator loading the form and submitting
it. Overbooking by administrators would be worse than overbooking by students, because it
carries institutional authority — so the guard is strictest exactly here.

#### FR-PLACE-012 — One form: student, placement, mentors

The direct-placement form binds three choices — who, where, supervised by whom — because a
placement without mentors is an accountability gap from day one. Mentor selection writes
through the registration's mentor bridge, the same bridge supervision later queries, so the
names attached at enrollment are the names the proxy checks consult all period long.

### 4.3 Change-Request Workflow

#### FR-PLACE-013 — Status enum honoring both contracts

`PlacementChangeStatus` implements `LabelEnum` for translated display and `StatusEnum` for
transition discipline. The dual contract means the admin screen renders human labels without
bespoke mapping code, while the transition map stays machine-checkable. A status value that
cannot label itself or cannot answer what comes next is a string, not a state — this enum is
a state.

#### FR-PLACE-014 — Pending branches once, then ends

From `PENDING` exactly two exits exist: approved or rejected, and both are terminal. That
diamond shape is the whole workflow's integrity in one line — no reopening an approved move,
no approving a rejected one, no third state smuggled in later without amending the map.
Terminal means the audit trail, once written, stays written.

#### FR-PLACE-015 — One pending request per registration

A student who taps submit twice on a slow connection must not create two pending requests for
the same move — the second attempt is rejected because the first is still open. The guard
queries by registration, not by student, so a student with history across periods is never
blocked by last year's resolved request. Idempotency here is kindness backed by a query.

#### FR-PLACE-016 — Approval demands a live request and a free target

Two preconditions, checked together: the request must still be pending (a colleague may have
processed it minutes ago), and the target placement must still have room (another approval
may have taken the last slot). If either fails, the admin gets a rejection explaining which
one — stale request or full target — because those demand completely different next steps.

#### FR-PLACE-017 — Three writes, one transaction

- Approval performs the quota transfer as an indivisible unit: decrement the old placement, increment the new one, repoint the registration's `placement_id`.
- A crash between the decrement and the increment would invent or destroy a slot out of thin air; the transaction makes the intermediate states unobservable.
- After commit, the sum of `filled_quota` across both placements is exactly what it was before — conservation of seats, enforced by the database.

#### FR-PLACE-018 — Rejections keep their reasons

A rejected request without a recorded reason is indistinguishable from neglect. The Action
requires the rationale, stores it on the row, and transitions to `REJECTED` in the same
write — so the student sees why, the auditor sees who decided, and nobody re-submits the
identical request next week expecting a different answer.

#### FR-PLACE-019 — Pending queue for reviewers

The change manager lists every open request with its reason and both placements side by
side. Review quality depends on context density: an admin who can see source, target, and
motive on one screen decides in a minute; one forced to open three tabs defers the decision
until the queue rots. The pending list is therefore a review instrument, not a table.

#### FR-PLACE-020 — Students see only real options

The student-facing list is pre-filtered three ways: same internship only (cross-program
moves are a different workflow), free slots only (choosing a full company wastes everyone's
time), and the current placement excluded (requesting a move to where you already are is
nonsense). Each exclusion removes a distinct class of invalid submission before the student
can make it.

#### FR-PLACE-021 — Policy gates creation and review

Students may request for their own registrations; admins and super-admins may review. The
policy draws that line at both the Livewire layer and the Action layer, so a crafted direct
call cannot bypass what the buttons already enforce. Ownership on creation, admin-group on
review — dual-layer authorization exactly as the
[flat-RBAC ADR](../adr/adr-flat-rbac-with-functional-roles.md) prescribes.

### 4.4 Supervision Bridge & Proxy

#### FR-PLACE-026 — Mentors resolve through MentorEntity

Placement attaches mentors at enrollment, but the authority question — "may this teacher act
for this student's supervision?" — is answered later by `MentorEntity`, bridged from the
registration via `asMentorEntity()`. Placement writes the mentor rows; it never duplicates
the resolution logic. One bridge, owned by the User module and documented in
[95EVB](95EVB-user-crud-and-status.md), keeps placement writes and supervision reads
consistent by construction.

#### FR-PLACE-027 — Teacher-as-supervisor checks delegate to proxy gates

When a supervisor is unreachable and the assigned teacher verifies a placed student's logbook
or scores a competency, the policy does not consult placement-local role flags — it calls the
`MentorEntity` proxy gates (`canProxyAsSupervisor`, `canVerifyLogbook`, …) with the acting
user. Scope stays tight: only teachers already mentoring that registration may proxy, admins
may proxy anywhere, and every proxied act lands in the activity log with its proxy context
per the [cross-role proxy ADR](../adr/adr-cross-role-proxy.md).

### 4.5 Transition & Failure Contracts

#### FR-PLACE-028 — Guards live in the entity, not the callers

The transition map appears exactly once, on the status entity's `canTransitionTo()`. Every
Action — request, approve, reject — asks the entity instead of re-encoding the map, so adding
a future state means editing one method, not hunting every call site. An Action that
implements its own transition check is a drift bug waiting for the next state addition.

#### FR-PLACE-029 — RejectedException is the only business-failure voice

Quota full, duplicate request, stale approval, illegal delete — all surface as
`RejectedException` with a message run through `__()`, rendered by Livewire as a toast. The
sibling-tree discipline from the
[exception-hierarchy ADR](../adr/adr-exception-hierarchy.md) holds: business rejections never
leak stack traces, and infrastructure failures never masquerade as friendly toasts. One
catch shape in the UI covers every rule in this spec.

### 4.6 Livewire Surfaces & Routing

#### FR-PLACE-022 — Placement index route

The CRUD screen lives at `/admin/internships/placements` behind admin middleware, so the
URL itself declares its audience. Route-level gating rejects students before any component
code runs; the policy layer then re-validates inside, because URLs are user input and
middleware is only the first of two locks.

#### FR-PLACE-023 — Direct placement route

Direct placement gets its own screen at `/admin/internships/placements/direct` rather than a
modal on the index, because the form's three-way binding (student, placement, mentors)
deserves full-page validation space. Same middleware, same audience, separate address —
deep-linkable from the registration review queue.

#### FR-PLACE-024 — Change-review route

Pending change requests live at `/admin/internships/placements/changes`, one click from the
index but a distinct surface with a distinct policy moment (review, not edit). Separating
the review screen from the CRUD screen keeps the approve/reject authority visible in the
route table instead of buried in component state.

#### FR-PLACE-025 — Student change-request route

Students reach their own flow at `/student/internships/placement-change` under
`role:student` middleware. The URL namespace split (`student.*` vs `enrollment.*`) mirrors
the policy split: students see their move options, never the admin queue, and the middleware
makes that true before a single query runs.

---

## 5. Non-Functional Requirements

Quota integrity is load-bearing for every downstream count; localization and access rules are
inherited global defaults tightened to this surface.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-PLACE-001 | `filled_quota` never goes negative; every decrement is guarded | Zero negative rows | P0 | F | Full |
| NFR-PLACE-002 | Concurrent registrations never exceed quota; check-and-increment is atomic | Zero overbookings | P0 | F | Full |
| NFR-PLACE-003 | The change-review screen shows the reason plus both source and target placements | Every pending row reviewable in one screen | P1 | F | Full |
| NFR-PLACE-004 | Placement mutations are audit-logged through SmartLogger with PII masking | Every mutation has an activity entry, zero plaintext PII | P0 | F | Full |
| NFR-PLACE-005 | Placement UI meets WCAG 2.1 Level AA | AA on interactive elements | P1 | B | Full |
| NFR-PLACE-006 | Placement form inputs carry associated labels | Every input labeled | P1 | B | Full |
| NFR-PLACE-007 | Placement UI text contrast meets 4.5:1 minimum | 4.5:1 | P1 | B | Full |
| NFR-PLACE-008 | User-facing strings use the `__()` helper | Zero hardcoded strings (D3 scan) | P0 | A | Full |
| NFR-PLACE-009 | Translation keys exist in both `lang/en/` and `lang/id/` | Zero missing keys | P0 | A | Full |

### 5.1 Quota Integrity

#### NFR-PLACE-001 — Counters never go below zero

A decrement that drives `filled_quota` negative does not merely look wrong — it manufactures
a phantom slot that the next registration happily occupies, converting one bookkeeping slip
into a real overbooking. The guard clamps at zero and rejects the operation instead, so the
counter is a floor the system defends, not a number it reports.

#### NFR-PLACE-002 — Atomicity under concurrency

Enrollment week is the load test: a hundred students submitting within the same minutes
against a handful of popular companies. Because the slot check and the increment share one
transaction, the database serializes what the application cannot coordinate — the hundredth
registration for a ninety-nine-seat company fails cleanly instead of squeezing through a
race window. Zero overbookings is the standing target, verified by the periodic audit that
recounts registrations against `filled_quota`.

### 5.2 Review & Audit

#### NFR-PLACE-003 — Review context on one screen

An admin deciding a placement change needs the student's reason and both placements visible
together — source context (why leave), target context (is there really room and fit). When
any of the three is a click away, reviews get deferred and the pending queue ages. The
screen therefore treats reason-plus-both-placements as a single review unit, not three
fields that happen to coexist.

#### NFR-PLACE-004 — Masked audit trail on every mutation

Every placement write — create, update, delete, direct placement, change approval or
rejection — passes through SmartLogger's dual channels with `withPiiMasking()`, so student
names, emails, and phone numbers land partially masked in both the system log and the
queryable activity table per the
[SmartLogger ADR](../adr/adr-smartlogger-dual-channel.md). An audit entry that leaks PII
into plaintext is worse than no entry; masking by default makes the safe path the easy one.

### 5.3 Localization & Access

#### NFR-PLACE-005 — AA-accessible placement UI

The coordinator who manages placements may navigate by keyboard and may read the screen at
reduced contrast — AA compliance on interactive elements is the floor, not an aspiration.
Target rows, action buttons, and the stats cards all stay operable and legible without a
mouse or perfect vision, because enrollment week does not pause for anyone's hardware.

#### NFR-PLACE-006 — Every input labeled

An unlabeled quota field is a mis-entry waiting to happen: is that number seats or filled
seats? Associated labels bind each input to its meaning for sighted users, screen readers,
and future translators alike. The rule is absolute — no placeholder-only inputs anywhere on
these forms.

#### NFR-PLACE-007 — Contrast floor for placement text

Small gray-on-gray quota figures become invisible in a sunlit school office. The 4.5:1
minimum keeps every number and label readable under real lighting on real aging monitors,
which is where this software actually runs.

#### NFR-PLACE-008 — No hardcoded strings

Every string the coordinator or student reads passes through `__()` — validation messages,
toast confirmations, rejection explanations. Hardcoded text is a localization dead end and a
D3 scan failure; the helper call costs nothing and keeps the Indonesian primary rendering
correct from day one.

#### NFR-PLACE-009 — Mirrored locale files

Each key used on these screens exists in both `lang/en/` and `lang/id/`, so toggling the
locale never surfaces a raw key to a user. The pair ships together and is reviewed together;
a key added in one file without its twin is an incomplete change, caught by the locale scan
rather than by a confused student.

---

## 6. API / Data Contracts

### 6.1 Placement Model

```php
// app/Modules/Enrollment/Placement/Models/Placement.php
// Table: placements
// PK: id (uuid v7); FK: company_id → companies (cascade delete)
// FK: internship_id → internships (cascade delete)
// Fillable: company_id, internship_id, name, address, quota (default 1),
//   filled_quota (default 0), description
// Unique: (company_id, internship_id)
```

### 6.2 Placement Entities

```php
// app/Modules/Enrollment/Placement/Entities/PlacementCapacity.php
final readonly class PlacementCapacity extends BaseEntity
{
    public static function fromModel(Model $model): static;
    public function isFull(): bool;            // filledQuota >= quota
    public function availableSlots(): int;     // max(0, quota - filledQuota)
    public function hasAvailableSlots(): bool; // availableSlots() > 0
}

// app/Modules/Enrollment/Placement/Entities/PlacementState.php
final readonly class PlacementState extends BaseEntity
{
    public static function fromModel(Model $model): static;
    public function canBeDeleted(): bool;      // registrationCount === 0
}
```

### 6.3 Placement Actions

```php
// CreatePlacementAction / UpdatePlacementAction / DeletePlacementAction
//   Delete guards via PlacementState::canBeDeleted() — RejectedException otherwise

// DirectPlacementAction
final class DirectPlacementAction extends BaseCommandAction
{
    public function execute(User $student, Placement $placement, array $mentors = []): Registration;
    // Atomic: create Registration + increment filled_quota (single transaction)
    // Guards: PlacementCapacity::hasAvailableSlots() or RejectedException
}
```

### 6.4 PlacementChangeRequest Model

```php
// app/Modules/Enrollment/Placement/Models/PlacementChangeRequest.php
// Table: placement_change_requests
// Fillable: registration_id, from_placement_id, to_placement_id, reason,
//   requested_by, status, processed_by, processed_at, rejection_reason
// Casts: status → PlacementChangeStatus, processed_at → datetime
```

### 6.5 PlacementChangeStatus Enum

```php
enum PlacementChangeStatus: string implements LabelEnum, StatusEnum
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    // PENDING → [APPROVED, REJECTED]; APPROVED, REJECTED terminal
    // canTransitionTo() is the single enforcement point (FR-PLACE-028)
}
```

### 6.6 Placement Change Actions

```php
// RequestPlacementChangeAction — guards: no PENDING request for the registration
// ApprovePlacementChangeAction — guards: non-terminal + target has slots;
//   atomic: decrement old, increment new, repoint registration
// RejectPlacementChangeAction — records rejection_reason, transitions to REJECTED
```

### 6.7 Supervision Bridge (Owned Elsewhere, Referenced Here)

Mentor resolution is owned by the User module and specified in
[95EVB](95EVB-user-crud-and-status.md) §6: `Registration::asMentorEntity(): MentorEntity`
with proxy gates (`canProxyAsSupervisor`, `canVerifyLogbook`, …). Placement writes mentor
rows at enrollment (FR-PLACE-012) and delegates every authority question to that bridge
(FR-PLACE-026/027) — this spec defines no parallel role logic.

### 6.8 Routes

```php
// routes/web/enrollment.php (placement portion)
// Student: /internships/placement-change → StudentPlacementChangeRequest (role:student)
// Admin:   /internships/placements         → PlacementIndex
//          /internships/placements/direct  → DirectPlacementManager
//          /internships/placements/changes → PlacementChangeManager
//          (admin group: role:super_admin|admin)
```

### 6.9 Database Migrations

| Migration | Table |
| --------- | ----- |
| `2026_01_04_000002_create_placements_table.php` | `placements` |
| `2026_01_05_000003_create_placement_change_requests_table.php` | `placement_change_requests` |

---

## 7. Design Decisions

Decisions record why the implementation looks the way it does. `Layer` / `Status` stay `—`
unless a decision carries a code-testable consequence.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-PLACE-001 | Capacity check-and-increment runs in an application transaction rather than row-level locking | P0 | — | — |
| DD-PLACE-002 | Placement changes are a dedicated request model with its own status enum, not in-place edits | P0 | — | — |
| DD-PLACE-003 | Supervision authority resolves through the shared MentorEntity bridge, not placement-local checks | P0 | — | — |

### 7.1 Capacity & Workflow Shape

#### DD-PLACE-001 — Application transactions over SELECT FOR UPDATE

The development database is SQLite, whose locking vocabulary is far poorer than the
production MySQL's — a design leaning on `SELECT ... FOR UPDATE` would behave differently
per environment, which is exactly the kind of surprise that bites during enrollment week.
Application-level transactions give identical semantics everywhere at single-tenant scale,
and the residual theoretical race under extreme concurrency is accepted explicitly rather
than hidden behind a lock the dev database cannot honor.

#### DD-PLACE-002 — Changes as first-class requests

Editing `placement_id` directly would move the student with no memory of the move: no
reason, no requester, no reviewer, no timestamp. The dedicated request model turns each move
into a small case file — who asked, why, who decided, when — at the price of one more model
and enum. For a system whose audit trail is a BAN-PDM evidence source, that price is not a
trade-off but the point.

### 7.2 Authority Shape

#### DD-PLACE-003 — One mentor bridge for the whole system

Placement could have grown its own "who supervises this student" helper in a dozen lines,
and for a month nobody would notice. Then supervision, assessment, and journals would each
consult slightly different logic, and a teacher mentoring a student in one module would lack
authority in another. Routing every question through `MentorEntity` costs one indirection
and buys a single answer everywhere — the [cross-role proxy ADR](../adr/adr-cross-role-proxy.md)
rationale, applied at the placement boundary.

---

## 8. Success Metrics

### 8.1 Capacity Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Overbooking incidents | 0 (quota never exceeded) | Audit: `count(registrations where placement_id=X)` vs `placements.filled_quota` |
| Quota accuracy | `filled_quota` matches actual registrations | Periodic recount per placement |
| Concurrent safety | No race under normal enrollment load | Transaction isolation in single-tenant deployment |

### 8.2 Placement Change Workflow

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Quota transfer accuracy | Both counters correct after every approval | Post-approval recount of old and new placement |
| Orphan request prevention | 0 duplicate pending requests per registration | `RequestPlacementChangeAction` guard |
| Admin review completeness | Pending requests reviewed within 48 hours | `PlacementChangeManager` queue age |

---

## 9. Roadmap

### Prerequisites

Placement builds on completed registration records and partner companies: it cannot be
implemented before [MBB5R](MBB5R-registration.md) provides enrollments to place and
[NTHQA](NTHQA-partnership-management.md) provides the company relationships placements
reference.

### Build Guide

Once this spec lands, enrolled students match to company slots as active placement records —
the trigger for daily operations. A placed student can log activities, record attendance, and
be assessed; nothing downstream starts before this spec's transaction commits.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [1KSWL](1KSWL-daily-activity.md) | Reads active placements for logbook and attendance tracking |
| 2 | [ARDA6](ARDA6-assessment.md) | Scores student work against rubrics using placement context |
| 3 | [AXKZW](AXKZW-evaluation.md) | Gathers industry feedback using placement context |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Registration](MBB5R-registration.md) — enrollment records placements consume
- [Account application](920SO-account-application.md) — guest entry pipeline
- [User CRUD & status](95EVB-user-crud-and-status.md) — owns the MentorEntity bridge
- [Partnership management](NTHQA-partnership-management.md) — company relationships
- [Daily activity](1KSWL-daily-activity.md) — first downstream consumer of placements
- [ADR: Cross-role proxy](../adr/adr-cross-role-proxy.md) — teacher-as-supervisor delegation
- [ADR: Entity-model separation](../adr/adr-entity-model-separation.md) — capacity/state entities
- [ADR: UUID primary keys](../adr/adr-uuid-primary-keys.md) — key strategy
- [ADR: MVP spec trim](../adr/adr-mvp-spec-trim.md) — trim rubric applied here
