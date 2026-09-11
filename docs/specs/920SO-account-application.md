# 920SO — Account Application

> **Spec ID:** 920SO
> **Status:** Full
> **Owner:** Enrollment
> **Depends on:** MBB5R

## Description

Guest-to-student account application pipeline for the Enrollment module: an unauthenticated
visitor applies through a public form, an admin reviews the request, and approval atomically
provisions a User, a Profile, and a Registration in one transaction. Registration and placement
management are separate initiatives — see [registration](MBB5R-registration.md) and
[placement](J9GBH-placement.md).

---

## 1. Problem Statements

### PS-1 — Guest-to-Student Account Application Pipeline

Prospective students who are not yet system users need a way to express intent to participate
in an internship program. The school admin must review each application, and upon approval,
the system must atomically create a User account, Profile, and Registration in a single
transaction. Manual provisioning is error-prone (forgot to create Profile, Registration
left in wrong status) and does not scale.
**→ Requirement:** FR-APPLY-009/010/011 (atomic approval pipeline), UC-APPLY-001/002.

### PS-2 — Duplicate Application Prevention

Multiple students may share the same email during application, or a student may re-apply
after rejection. The system must prevent duplicate pending/approved applications per email
while allowing rejected applications to be re-activated on re-apply, preserving the audit
trail without cluttering the database with redundant records.
**→ Requirement:** FR-APPLY-005 (duplicate guard), FR-APPLY-006 (re-activation),
UC-APPLY-003.

### PS-3 — Atomic Provisioning Failure Modes

The approval pipeline creates several records (Application status, User, Profile, Registration)
in a single transaction. Any failure midway — User creation fails (duplicate email), Profile
FK violation (invalid department), Registration FK violation (invalid internship) — must
roll back cleanly with no orphaned records. The error must surface as a user-friendly message
to the admin, not a stack trace.
**→ Requirement:** FR-APPLY-009 (single transaction), FR-APPLY-011 (missing-internship
refusal), NFR-APPLY-004 (no orphans).

---

## 2. Goals & Non-Goals

### Goals

- **Atomically provision User + Profile + Registration on approval** — one transaction, fully functional account or nothing. *Why:* removes the half-provisioned states manual work produces.
- **Prevent duplicate pending applications per email** — a second submit while one is live is refused. *Why:* stops queue clutter and double-review work.
- **Guest-accessible application page without authentication** — the form lives behind `guest` middleware. *Why:* applicants are not users yet; login cannot be a prerequisite.
- **Re-activate rejected applications on re-apply** — same email, same record, back to pending. *Why:* preserves history instead of accumulating shadow rows.
- **Record admin attribution on every decision** — who decided, when, and why. *Why:* review decisions need an accountable trail.
- **Support placement-based and proposed-company modes** — pick a slot or propose a company. *Why:* some applicants arrive with a slot, others with only an idea.

### Non-Goals

- **Registration workflow**. *Why:* owned by [registration](MBB5R-registration.md); this spec only feeds it.
- **Placement management**. *Why:* owned by [placement](J9GBH-placement.md).
- **Bulk import from government systems**. *Why:* bulk onboarding belongs to [bulk import](O2KCR-csv-import-export.md).
- **Student self-service placement swap**. *Why:* peer-to-peer swaps without admin review are out of MVP scope.

---

## 3. User Stories / Use Cases

Guest submission, admin review, and re-application. Each story below is verified by a Feature
test against a real database.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-APPLY-001 | Guest submits an account application through the public form and it lands as pending | P0 | F | Full |
| UC-APPLY-002 | Admin approves or rejects a pending application with attribution recorded | P0 | F | Full |
| UC-APPLY-003 | Guest whose application was rejected re-applies and the same record returns to pending | P1 | F | Full |

### 3.1 Guest Journey

#### UC-APPLY-001 — Guest applies for a student account

Picture the week registration opens: a Grade 11 student borrows her brother's phone, opens
`/apply` on a shaky connection, and picks her department's internship from a list that shows
only published programs with open slots. She fills name, email, phone, address, ID numbers,
class, entry year, and academic year, then chooses between tapping an existing placement and
typing a proposed company name by hand. One submit creates exactly one pending record, guards
against a duplicate live application on her email, and shows her a neutral confirmation that
reveals nothing about prior attempts.

#### UC-APPLY-003 — Rejected guest tries again

A boy rejected last month because his proposed company had no available mentor returns with a
different placement choice. He types the same email, half expecting an error about duplicates,
and instead his original record quietly wakes back to pending with the new form data attached.
No second row appears in the admin queue, and the earlier rejection reason survives in the
activity log where only staff can see it.

### 3.2 Admin Review

#### UC-APPLY-002 — Admin clears the morning queue

Monday morning, the admin opens the pending queue: twelve applications, each showing student
ID, department, and chosen placement or proposed company. She approves nine in sequence —
each approval lands a User with a random password and `setup_required` flag, a Profile, and
an active Registration inside one transaction, then fires the activation notification. Three
she rejects with a written reason, each stamped with her id and the current timestamp. A
colleague approving the same row concurrently cannot double-provision it: the second attempt
meets a still-pending check that no longer holds and is refused.

---

## 4. Functional Requirements

Single-table contract for the whole pipeline, from enum to form. `Layer` marks where each
row is tested; every row is implemented and verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-APPLY-001 | `AccountApplicationStatus` enum implements `LabelEnum` and `StatusEnum` contracts | P0 | U | Full |
| FR-APPLY-002 | Valid transitions are `PENDING` → `APPROVED` or `REJECTED`; `APPROVED` and `REJECTED` are terminal | P0 | U | Full |
| FR-APPLY-003 | `AccountApplication` stores flexible fields in a `form_data` JSON column | P1 | F | Full |
| FR-APPLY-004 | `AccountApplication` belongs to a Department and belongs to a User as `processed_by` | P1 | F | Full |
| FR-APPLY-005 | `ApplyAccountAction` refuses a new application when a `PENDING` or `APPROVED` row already exists for the email | P0 | F | Full |
| FR-APPLY-006 | `ApplyAccountAction` re-activates a `REJECTED` row to `PENDING` with fresh form data instead of inserting a duplicate | P0 | F | Full |
| FR-APPLY-007 | `ApplyAccountAction` runs both the create path and the re-activation path inside a transaction | P0 | F | Full |
| FR-APPLY-008 | Application email is checked for uniqueness against both `account_applications` and `users` | P0 | F | Full |
| FR-APPLY-009 | `ApproveAccountApplicationAction` runs mark-approved → create User (random 32-char password, `setup_required`, student role) → create Profile → create active Registration inside a single database transaction | P0 | F | Full |
| FR-APPLY-010 | `ApproveAccountApplicationAction` refuses unless the application is still `PENDING` at execution time | P0 | F | Full |
| FR-APPLY-011 | `ApproveAccountApplicationAction` throws `RejectedException` when `form_data` carries no `internship_id` | P0 | F | Full |
| FR-APPLY-012 | `RejectAccountApplicationAction` records `rejection_reason` and moves the row to `REJECTED` | P0 | F | Full |
| FR-APPLY-013 | `RejectAccountApplicationAction` refuses unless the application is still `PENDING` at execution time | P0 | F | Full |
| FR-APPLY-014 | Approval and rejection stamp `processed_by` and `processed_at` on the application | P0 | F | Full |
| FR-APPLY-015 | Approval dispatches the activation notification for the provisioned account | P0 | F | Full |
| FR-APPLY-016 | `AccountApplicationPolicy` opens create to guests and reserves viewAny, view, update, and delete to admins | P0 | A | Full |
| FR-APPLY-017 | Every approval and rejection writes a PII-masked SmartLogger activity entry with actor identity | P0 | F | Full |
| FR-APPLY-018 | Approval and rejection enforce authorization in both the Policy layer and the Action layer via `RejectedException` | P0 | A | Full |
| FR-APPLY-019 | `ApplyPage` lives at `/apply` behind `guest` middleware | P0 | F | Full |
| FR-APPLY-020 | `ApplyPage` extends `BaseFormView` and binds the `AccountApplicationForm` Form Object | P0 | F | Full |
| FR-APPLY-021 | `ApplyPage` lists only published, active internships | P0 | F | Full |
| FR-APPLY-022 | `ApplyPage` scopes placements to the chosen internship and hides full slots via `PlacementCapacity` | P0 | F | Full |
| FR-APPLY-023 | `ApplyPage` offers a mode toggle between placement-based and proposed-company application | P1 | F | Full |
| FR-APPLY-024 | Toggling the mode clears `placement_id` and the proposed-company fields so stale values never submit | P1 | F | Full |
| FR-APPLY-025 | The form validates name, dual-table-unique email, an `OpenForRegistration` internship, and academic year | P0 | F | Full |
| FR-APPLY-026 | The form requires `placement_id` in placement mode or proposed-company name plus address otherwise | P0 | F | Full |
| FR-APPLY-027 | `AccountApplicationForm::toArray()` returns all fields as a flat array for Action consumption | P1 | U | Full |

### 4.1 Lifecycle Shape

#### FR-APPLY-001 — Status enum honours both contracts

The enum is not decoration: `LabelEnum` gives every status a translated label for badges and
slips, while `StatusEnum` gives it `isTerminal()` and `canTransitionTo()` so no caller
hand-rolls transition logic. A reviewer checking a new badge component finds one source of
truth instead of three switch statements disagreeing about what "rejected" means.

#### FR-APPLY-002 — Three states, one way forward

Pending is the only live state; approved and rejected are graves with no exits. This narrowness
is deliberate — the night a coordinator tried to "un-approve" a mistaken approval by flipping
status back, the guard forced the honest path instead: keep the provisioned account and handle
it through user management, where the audit trail stays intact.

- Terminal states answer `canTransitionTo()` with false for every target, no exceptions.
- The map lives on the enum, so unit tests pin it without touching the database.

#### FR-APPLY-003 — Flexible fields ride in JSON

School forms change faster than schemas: one year asks for entry year, the next for class
name. `form_data` absorbs that churn so a new field is a form edit, not a migration. The
trade is discipline — only the fixed columns (name, email, IDs, status, attribution) are
queryable, and anything the approval pipeline depends on must be promoted out of the JSON.

#### FR-APPLY-004 — Application points at department and decider

Tracing a single request shows why both relations matter: the department link scopes the
application to the right academic home, and `processed_by` names the admin who decided it.
Months later, when a parent asks who approved her child's placement, the answer is one query
away instead of a grep through logs.

### 4.2 Submission Guards

#### FR-APPLY-005 — One live application per email

Double-clicking submit on a slow phone used to create twin rows that two admins then reviewed
in parallel. Now the guard checks for any pending or approved row on the email before
inserting, and the second attempt is refused with a translatable message. The check runs
inside the Action, not just the form, so a crafted request cannot bypass it.

#### FR-APPLY-006 — Rejection is a pause, not a ban

Early builds inserted a fresh row on every re-apply until one persistent applicant owned six
records and the queue looked busier than it was. Re-activation keeps exactly one row per
email: status returns to pending, form data refreshes, and the old rejection stays readable
in the activity log. Admins see a returning applicant, not a new stranger.

#### FR-APPLY-007 — Both submission paths transact

Whether inserting or re-activating, the write happens atomically. If the process dies between
the status flip and the form-data update, the database shows the pre-attempt state rather
than a pending row carrying last month's company choice. Callers never branch on which path
ran; the transaction boundary covers both.

#### FR-APPLY-008 — Email checked in two tables

A student who already holds an account — created by an admin import, say — must not be able
to file a guest application on the same email and end up with two identities. The form and
the Action both check `account_applications` and `users`, so the refusal holds even when the
Livewire validation is skipped by a direct call.

### 4.3 Review Pipeline

#### FR-APPLY-009 — Approval provisions everything at once

Follow one approval through the code: the Action opens a transaction, stamps the application
approved, creates the User with a random 32-character password and `setup_required`, builds
the Profile from the form's contact fields, creates the active Registration against the
chosen internship and placement, then commits and dispatches the approved event. Any step
throwing rolls everything back — there is no universe where the login works but the
registration is missing.

#### FR-APPLY-010 — The pending check stops double approval

Two admins opening the same row is not hypothetical during enrollment week. The Action
re-reads status inside the transaction and proceeds only from pending; the loser of the race
gets a clean rejection instead of a duplicate User insert blowing up on a unique key. The
guard turns a concurrency bug into a polite toast.

#### FR-APPLY-011 — No internship, no provisioning

A form that somehow arrives without an internship reference — a stale page from before quotas
were set, a hand-built POST — must never mint an account floating outside every program. The
Action treats the missing id as a business refusal with a message the admin can act on, not
an infrastructure error. Failures here read like guidance, not stack traces.

#### FR-APPLY-012 — Rejection keeps its reason

A bare "rejected" stamp helps nobody; the applicant re-applies blind and the cycle repeats.
Recording the reason at decision time gives the next attempt something to fix and gives
supervisors something to audit. The field is required, not optional, precisely because busy
reviewers skip optional fields.

- Reason, decider, and timestamp land in the same write as the status move.
- The reason is user-safe text: it may be quoted back to the applicant.

#### FR-APPLY-013 — Rejection also checks pending first

The same race that threatens approvals threatens rejections: approve and reject landing
together must not both succeed. The pending check makes the two Actions mutually exclusive
without either knowing about the other — whichever commits first wins, the other is refused.

#### FR-APPLY-014 — Every decision names its decider

An approval without attribution is a rumor. Stamping `processed_by` and `processed_at` on
both outcomes means the queue view can show "approved by Bu Ratna, Tuesday 09:14" and the
audit export can prove it. System-initiated transitions never occur here, so a null decider
on a decided row is itself a defect worth alerting on.

#### FR-APPLY-015 — Approval ends with a notification

Provisioning without telling anyone strands the account: a login that exists but no student
knows about. Dispatching the activation notification as the final pipeline step closes the
loop — credentials or activation link travel to the applicant while the admin moves to the
next row. Delivery failures are logged, never allowed to roll back the already-committed
account.

### 4.4 Policy, Audit & Authorization

#### FR-APPLY-016 — Guests may ask, only admins may touch

The policy draws the building's floor plan: anyone, including strangers, may create; only
admin roles may list, view, update, or delete. Opening create this wide is safe because
creation only files a request — it grants no access, creates no session, and touches no
other record. Everything past the inbox door requires the admin key.

#### FR-APPLY-017 — Decisions are logged with masks on

Each approval and rejection writes one SmartLogger activity entry carrying the actor, the
application id, and the outcome — with emails, phones, and ID numbers masked before they
reach either channel. During a dispute over a rejected application, this entry is the record
the school shows; the masking is what lets them show it without leaking the applicant's
personal data into a file an intern might read.

#### FR-APPLY-018 — Two layers say no, not one

A policy gate on the route stops unauthorized clicks; a `RejectedException` inside the
Action stops unauthorized calls. The second layer is the one that matters when someone
invokes the Action directly in tinker or a test — authorization travels with the business
operation, not with the HTTP wrapper around it.

### 4.5 Form & Page

#### FR-APPLY-019 — Public door at a fixed address

`/apply` behind `guest` middleware is a promise printed on brochures: no account needed,
signed-in users bounced away to their dashboard instead of staring at a form they cannot
use. Moving this route would break printed materials, so its path is treated as stable
surface, not implementation detail.

#### FR-APPLY-020 — Page follows the form-view shape

Extending `BaseFormView` buys the page validation handling, error rendering, and toast
behavior every other form in the system shares, instead of a bespoke guest page that drifts.
The Form Object owns the fields and rules; the page owns layout and flow. A new maintainer
reading this page meets a familiar skeleton, not a snowflake.

#### FR-APPLY-021 — Only open programs are choosable

Listing drafts, archived years, or closed internships would invite applications nobody can
honor. Filtering to published and active programs keeps the applicant's choice real and
keeps admins from rejecting rows the system should never have accepted. The filter mirrors
the same `OpenForRegistration` rule the form validates against, so display and enforcement
cannot disagree.

#### FR-APPLY-022 — Slots shown are slots that exist

Nothing sours an applicant faster than choosing a placement that filled last week. Scoping by
internship and excluding full slots through `PlacementCapacity` means the dropdown tells the
truth at render time. Residual races — two guests picking the last seat — resolve at approval,
where the pending check and capacity validation have the final word.

#### FR-APPLY-023 — Two doors in one form

Some applicants arrive with a slot in mind; others arrive with only a company's name scribbled
on paper. The mode toggle serves both without forking the flow into two pages, two routes,
and two review queues. One queue, one review skill, half the training for the admin team.

#### FR-APPLY-024 — Toggling wipes the other mode's data

The classic corruption here is invisible: pick a placement, toggle to proposed-company, submit
— and the stale `placement_id` rides along, provisioning the student into a slot they never
chose. Clearing both sides on toggle makes the submitted payload match what the applicant
sees. What you see is literally what gets stored.

#### FR-APPLY-025 — Base fields carry hard rules

Name required and bounded, email valid and unique in both tables, internship real and open,
academic year present and bounded. Each rule exists because its absence once produced a real
failure: nameless rows in exports, collisions at approval, applications against closed
programs. Validation runs server-side in the Form Object, so it holds regardless of client
behavior.

#### FR-APPLY-026 — Each mode demands its own evidence

Placement mode without a placement is a shrug; proposal mode without a name and address is a
rumor. The conditional requirement forces the applicant to finish the thought their mode
started, and gives the reviewer the minimum material needed to decide. Partial rows never
reach the queue.

#### FR-APPLY-027 — The form flattens itself for the Action

`toArray()` is the handshake between the Livewire world and the Action world: every field,
flat, no form-object types leaking into business logic. Because the gradual-migration path
allows `execute(array)` today and a DTO tomorrow, this flat array is also the shape the
future DTO's `fromArray()` will consume — the migration will not need to touch the form.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-APPLY-001 | Guest submissions are rate-limited per IP so bots cannot flood the review queue | Throttle enforced; excess returns 429 | P0 | F | Full |
| NFR-APPLY-002 | `form_data` content is sanitized before storage so stored JSON cannot carry executable markup into admin views | Zero unescaped markup rendered | P0 | A | Full |
| NFR-APPLY-003 | Provisioned accounts always receive a random 32-character password; applicants never choose credentials | No applicant-chosen password path | P0 | A | Full |
| NFR-APPLY-004 | Approval provisioning is atomic: any step failing leaves zero orphaned Users, Profiles, or Registrations | Zero orphans on failure | P0 | F | Full |
| NFR-APPLY-005 | Rejection reasons survive re-activation in the activity log | Reason retrievable after re-apply | P1 | F | Full |
| NFR-APPLY-006 | Post-submit messaging never discloses whether an application was new or re-activated | Identical message both paths | P1 | F | Full |
| NFR-APPLY-007 | The apply form is keyboard-navigable with associated labels and AA-contrast text | Labels on all inputs; contrast ≥ 4.5:1 | P1 | B | Full |
| NFR-APPLY-008 | Every user-facing string on the apply flow passes through `__()` with mirrored `en`/`id` keys | Zero hardcoded strings; keys present both locales | P0 | A | Full |

### 5.1 Security & Integrity

#### NFR-APPLY-001 — The public door has a bouncer

An open form is a spam magnet the week it goes on a brochure. Per-IP throttling turns a
bot flood into a trickle of 429s while genuine applicants — one submit per person — never
notice it. The limit lives in middleware configuration, adjustable without touching the
form, because the right threshold on launch week differs from the right one in quiet months.

#### NFR-APPLY-002 — Stored JSON must not bite the reviewer

`form_data` renders inside the admin review screen, which makes it stored-XSS territory: an
applicant (or a bot) can submit markup today that executes in a staff browser tomorrow.
Sanitizing before storage plus escaping at render closes both ends. The review queue is a
privileged surface; it must be safe to simply look at.

#### NFR-APPLY-003 — Credentials are minted, never chosen

Letting applicants pick passwords invites `smk12345` across five hundred accounts and hands
the admin a support burden of weak-credential lockouts. Random 32-character secrets with a
forced setup step remove the choice entirely — the student sets something personal later,
through the setup flow, where strength rules apply.

### 5.2 Reliability

#### NFR-APPLY-004 — Partial provisioning must be impossible

Half an account is worse than none: a User without a Registration logs into an empty
dashboard and files a support ticket; a Registration without a User corrupts every roster
join. The single-transaction pipeline plus rollback makes "half" unreachable, and the
absence of orphans after induced failures is asserted by test, not assumed.

#### NFR-APPLY-005 — History outlives the second chance

Re-activation rewrites status and form data, which would destroy the rejection story if the
log did not keep it. Because the reason persists in the activity channel, a supervisor can
still ask "why was this rejected in March?" after an April re-apply — and get an answer
with a name and timestamp attached.

#### NFR-APPLY-006 — One message for both outcomes

Telling the applicant "welcome back, your old application was revived" leaks the existence
of a prior record to anyone typing random emails into the form. A single neutral
confirmation — received, under review — treats new and returning applicants identically and
closes an enumeration side-channel that costs nothing to close.

### 5.3 Experience & Localization

#### NFR-APPLY-007 — The form works for every applicant

Many applicants apply from low-end phones with keyboard navigation and bright sunlight on
the screen: associated labels keep screen readers honest, visible focus keeps keyboard users
oriented, and 4.5:1 contrast keeps text readable outdoors. These three were merged into one
row because they ship together in the same template pass and regress together.

#### NFR-APPLY-008 — Two languages from day one

Every string on the guest flow — the widest-read surface in the system — resolves through
`__()` with keys mirrored in Indonesian and English. A missing key is a defect, not a
follow-up, because the applicant staring at a raw `registration.apply.title` key has no way
to report it politely; they just leave.

---

## 6. API / Data Contracts

### 6.1 AccountApplication Model

```php
// app/Modules/Enrollment/AccountApplication/Models/AccountApplication.php
// Table: account_applications
// Fillable: name, email, student_id_number, department_id, form_data (json),
//           status, processed_by, processed_at, rejection_reason
// Casts: form_data → array, processed_at → datetime, status → AccountApplicationStatus
// Relations: department() → BelongsTo Department, processor() → BelongsTo User (processed_by)
// PK: UUID v7 via BaseModel; FKs via foreignUuid()->constrained()
```

### 6.2 AccountApplicationStatus Enum

```php
// app/Modules/Enrollment/AccountApplication/Enums/AccountApplicationStatus.php
enum AccountApplicationStatus: string implements LabelEnum, StatusEnum
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string;            // __('registration.status.{value}')
    public function isTerminal(): bool;         // true for APPROVED, REJECTED
    public function validTransitions(): array;  // PENDING → [APPROVED, REJECTED]
    public function canTransitionTo(StatusEnum $target): bool;
}
```

### 6.3 Action Signatures

```php
// app/Modules/Enrollment/AccountApplication/Actions/ApplyAccountAction.php
final class ApplyAccountAction extends BaseCommandAction
{
    public function execute(array $data): AccountApplication;
    // Guards: no duplicate PENDING/APPROVED by email (RejectedException otherwise)
    // Re-activates REJECTED on re-apply (status + form_data refresh, same row)
    // Transactional on both paths; dispatches AccountApplicationSubmitted event
}

// app/Modules/Enrollment/AccountApplication/Actions/ApproveAccountApplicationAction.php
final class ApproveAccountApplicationAction extends BaseCommandAction
{
    public function execute(string $applicationId, User $admin): Registration;
    // Requires PENDING (concurrent guard); requires form_data.internship_id
    // Single transaction: mark APPROVED (+processed_by/at) → create User
    // (random 32-char password, setup_required, student role) → create Profile
    // → create Registration (active) → dispatch AccountApplicationApproved
}

// app/Modules/Enrollment/AccountApplication/Actions/RejectAccountApplicationAction.php
final class RejectAccountApplicationAction extends BaseCommandAction
{
    public function execute(string $applicationId, User $admin, string $reason): void;
    // Requires PENDING; stamps REJECTED + processed_by/at + rejection_reason
    // Dispatches AccountApplicationRejected event
}
```

### 6.4 Policy

```php
// app/Modules/Enrollment/AccountApplication/Policies/AccountApplicationPolicy.php
class AccountApplicationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool;                              // admin only
    public function view(User $user, AccountApplication $application): bool; // admin only
    public function create(?User $user): bool;                               // always true (guest)
    public function update(User $user, AccountApplication $application): bool; // admin only
    public function delete(User $user, AccountApplication $application): bool; // admin only
}
```

### 6.5 Form Object

```php
// app/Modules/Enrollment/AccountApplication/Livewire/Forms/AccountApplicationForm.php
class AccountApplicationForm extends Form
{
    // Fields: name, email, phone, address, national_id_number, student_id_number,
    //   department_id, class_name, entry_year, internship_id, placement_id,
    //   academic_year, proposed_company_name, proposed_company_address, use_placement
    public function rules(): array;   // base + conditional (mode-dependent) rules
    public function toArray(): array; // flat array for Action consumption
}
// Base: name required|string|max:255; email required|email|unique both tables;
//   internship_id required|exists|OpenForRegistration; academic_year required|string|max:20
// Conditional: placement_id required_if use_placement; proposed_company_name +
//   proposed_company_address required_if not use_placement
```

### 6.6 Events

```php
// AccountApplicationSubmitted  — dispatched by ApplyAccountAction (payload: AccountApplication)
// AccountApplicationApproved   — dispatched by ApproveAccountApplicationAction (payload: AccountApplication)
// AccountApplicationRejected   — dispatched by RejectAccountApplicationAction (payload: AccountApplication)
```

### 6.7 Routes

```php
// routes/web/enrollment.php (account application portion)
Route::middleware('guest')->group(function () {
    Route::livewire('/apply', ApplyPage::class)->name('apply');
});
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/admin/account-applications', AccountApplicationManager::class)
        ->name('account-applications.index');
});
```

### 6.8 Database Migration

| Migration | Table | Key Columns |
| --------- | ----- | ----------- |
| `2026_01_04_000004_create_account_applications_table.php` | `account_applications` | id (uuid v7), name, email (index), student_id_number, department_id (FK→departments), form_data (json), status, processed_by (FK→users), processed_at, rejection_reason, timestamps |

---

## 7. Design Decisions

Recorded choices with their context; each is observable in the code it shaped.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-APPLY-001 | Approval provisions User + Profile + Registration inside one transaction | P0 | — | — |
| DD-APPLY-002 | Re-apply re-activates the rejected row instead of inserting a new one | P0 | — | — |
| DD-APPLY-003 | Provisioned accounts get random 32-character passwords with `setup_required` | P0 | — | — |
| DD-APPLY-004 | One form serves placement-based and proposed-company modes via a toggle | P1 | — | — |
| DD-APPLY-005 | Email uniqueness spans both `account_applications` and `users` | P0 | — | — |

### 7.1 Provisioning Shape

#### DD-APPLY-001 — One transaction or nothing

Before this decision, provisioning was three sequential writes and enrollment week produced
its share of ghosts: logins with no registration, registrations pointing at deleted users.
Wrapping the pipeline in a single transaction traded a slightly longer lock hold — password
hashing is CPU work — for a guarantee reviewers now take for granted. Admin-triggered and
low-frequency, the hold time never showed up in practice.

#### DD-APPLY-002 — Wake the old row, don't birth a new one

The alternative was an ever-growing pile of attempts per email with "latest wins" logic
sprinkled across queries. Re-activation keeps the queue count honest and the history linear:
one row, many chapters, the rejection chapter still legible in the log. The cost — losing
the inline rejection reason on the row itself — is paid by keeping it in the activity trail.

#### DD-APPLY-003 — Nobody chooses the first password

Admins choosing passwords meant admins knowing passwords; applicants choosing meant
`nama123` everywhere. Random secrets plus a forced setup step remove both problems at the
price of one extra student action on first login — an action the setup wizard walks them
through in under a minute.

### 7.2 Form Shape

#### DD-APPLY-004 — Two modes, one queue

Splitting modes into separate pages would have doubled the review surfaces and the training.
Conditional validation inside one form concentrates the complexity where Livewire handles it
best — dynamic rules on toggle — and leaves the admin with a single queue to learn. Form
logic bends so operations don't have to.

#### DD-APPLY-005 — Uniqueness without blind spots

Checking only the applications table once let a guest apply for an email that already owned
an account, forking one person into two identities that later collided at approval. The
dual-table check closes the fork at the cheapest point — the form — with the Action
re-checking for callers that skip the form. Stricter, yes; the escape hatch is an admin
creating the registration directly for the existing user.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Provisioning atomicity | Zero orphaned Users/Registrations after induced failures | Failure-injection Feature test on approval |
| Duplicate prevention | Zero duplicate pending/approved rows per email | Guard Feature test with double submit |
| Application-to-provisioning time | Under 5 minutes excluding admin review wait | Transaction timing in approval test |
| Re-activation audit | Zero lost rejection records across re-apply | Activity log assertion after re-activation |
| Dual-table email check | 100% of existing-user emails refused at submit | Form + Action validation tests |
| Closed-program protection | Zero applications accepted for closed internships | `OpenForRegistration` rule tests |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [registration](MBB5R-registration.md) | Registration workflow — application is the self-service entry point into it |

### Build Guide

Guest application is the first touchpoint for students entering PKL: public form, admin
review, atomic provisioning. Once accounts exist, administration moves to user management.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [user-crud-and-status](95EVB-user-crud-and-status.md) | Admin manages provisioned accounts; status transitions continue the lifecycle |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

- [Spec registry](index.md) — Enrollment phase; this spec is `920SO` with status Full
- [Registration](MBB5R-registration.md) — downstream workflow this pipeline feeds
- [Placement](J9GBH-placement.md) — slot capacity behind the placement filter
- [User CRUD & status](95EVB-user-crud-and-status.md) — administration of provisioned accounts
- [Architecture](D2FT3-architecture.md) — Action Triad, DTO boundary, Entity rules
- [Project initialization](QLHDO-project-initialization.md) — global FR-GLB/NFR every row inherits
