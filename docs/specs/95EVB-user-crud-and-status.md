# 95EVB — User CRUD & Status

> **Spec ID:** 95EVB
> **Status:** Full
> **Owner:** User
> **Depends on:** YB7RG

## Description

User administration for Internara: role-specific Livewire managers, the `AccountStatus`
state machine guarding every account transition, the create/update/delete lifecycle with
profile handling, batch lock/unlock/delete with protected-account skipping, and the
multi-layer super-admin protection that makes system lockout structurally unreachable.
Authentication state and role checks come from [authentication](YB7RG-authentication.md);
bulk tabular onboarding lives in [bulk import](O2KCR-csv-import-export.md) and credential
slips in [account slips](EWCZ0-account-slips.md).

---

## 1. Problem Statements

### PS-1 — Multi-Role User Administration

Schools manage students, teachers, supervisors, and administrators — each with different data
requirements and operational needs. A single generic user management interface cannot efficiently
handle the distinct fields (student: department, NIS; teacher: NIP; supervisor:
company) without becoming unwieldy. Role-specific management interfaces with tailored columns,
filters, and form fields are needed.
**→ Requirement:** FR-USER-033/040/041/042 (role managers), UC-USER-001.

### PS-2 — Account Lifecycle Complexity

User accounts progress through 8 states (provisioned → activated → verified → … → archived)
with strict transition rules. Without a state machine, administrators could put accounts in
inconsistent states (e.g., archiving a provisioned account directly). The state machine must
enforce valid transitions, define terminal states, and prevent illegal operations at every layer.
**→ Requirement:** FR-USER-013–026 (state machine + Entity guards), UC-USER-003.

### PS-3 — Bulk Operations at Scale

Schools may have 500+ students to onboard or manage. Manual one-by-one creation and status
changes are impractical. Batch lock/unlock, batch delete, and mass archive must skip protected
accounts (self, super admin), and report clear counts without exhausting memory.
**→ Requirement:** FR-USER-010 (batch delete), FR-USER-055/056 (mass archive),
UC-USER-003/006.

### PS-4 — Super Admin Protection

The super admin account is the system's break-glass access. If deleted or locked, the school
could be permanently locked out. A single protection layer is insufficient — observers can be
bypassed via tinker, actions via direct calls, UIs via crafted requests. Multiple independent
protection layers are needed: model observer, action guard, Livewire UI guard, and model-level
integrity rules.
**→ Requirement:** FR-USER-046–054 (protection layers), UC-USER-004, NFR-USER-004.

---

## 2. Goals & Non-Goals

### Goals

- **Role-specific management interfaces** — User, Student, Teacher, Supervisor, Admin managers with tailored columns and filters. *Why:* one generic table cannot serve five roles without drowning in conditionals.
- **Enforced `AccountStatus` state machine** — 8 states, Entity-owned transition guards, terminal states. *Why:* illegal account states must be unrepresentable, not merely discouraged.
- **Batch delete, lock, and unlock with skip-on-protected logic** — partial success with counts. *Why:* operating on hundreds of rows must not halt on the two that are sacred.
- **Profile editing with personal, emergency, and role-specific fields** — upsert semantics, guarded access. *Why:* a user record without its profile is an incomplete identity.
- **Super admin immune to deletion, lockout, and identity change** — at observer, model, action, and UI layers. *Why:* losing break-glass access is a school-wide outage with no remote fix.
- **Mass archival of finished cohorts via chunked queries** — bounded memory at any cohort size. *Why:* graduation must not OOM the admin's request.

### Non-Goals

- **User self-registration**. *Why:* intake flows through [account application](920SO-account-application.md), not open signup.
- **Role permission editing**. *Why:* permission grants belong to the RBAC system, not user CRUD.
- **CSV import/export mechanics**. *Why:* owned by [bulk import](O2KCR-csv-import-export.md); this spec only consumes its results.
- **Account slip PDF generation**. *Why:* owned by [account slips](EWCZ0-account-slips.md).
- **User activity monitoring beyond the audit log**. *Why:* surveillance dashboards are post-MVP depth.
- **Multi-tenant user isolation**. *Why:* single-tenant by product decision; no `tenant_id` anywhere.
- **User impersonation / login-as**. *Why:* impersonation is a post-MVP support tool with its own audit burden.

---

## 3. User Stories / Use Cases

Single-account work, bulk work, and the protection story. Each row below is verified by a
Feature test against a real database.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-USER-001 | Admin creates a single user with generated credentials and activation notices | P0 | F | Full |
| UC-USER-002 | Admin edits a user's details and profile atomically with an event dispatched | P0 | F | Full |
| UC-USER-003 | Admin locks multiple accounts at once with self and super-admin attempts refused | P1 | F | Full |
| UC-USER-004 | Any attempt to delete or mutate the super admin identity is refused at every layer | P0 | F | Full |
| UC-USER-005 | Admin resets a user's password by revoking activation tokens so re-activation is required | P1 | F | Full |
| UC-USER-006 | Admin mass-archives a finished student cohort in bounded memory with super admin skipped | P1 | F | Full |

### 3.1 Single-Account Operations

#### UC-USER-001 — First day of term, two hundred accounts to make

The admin opens User Management the morning after enrollment closes, clicks Create, and types
a name and email — leaving username and password blank because the system mints both, the
username derived from the email with a suffix on collision, the password a random 12-character
secret. One transaction lands the User, the Profile, and the role grants; an activation
notification and, when the password was minted, a welcome note with the plaintext secret go
out; the page lands on the account slip ready to print. Creation is a straight line from form
to printable credential with no manual follow-ups hiding in it.

#### UC-USER-002 — A typo in the roster gets fixed

A teacher reports that a student's name is misspelled and the phone number is last year's.
The admin opens the row, edits name, email, profile fields, and roles in one modal, and saves
— uniqueness re-checked against everyone except this user, user plus profile plus roles
persisted atomically, `UserUpdated` dispatched for listeners downstream. Either everything
lands or nothing does; there is no saved-name-but-stale-phone middle state.

#### UC-USER-005 — A compromised password gets neutralized

When a student shares a password with a friend, the admin does not set a new one — that
would mean knowing it. One click revokes every activation token on the account, locking the
holder out until they walk the activation flow again and set a fresh secret. The flash
confirmation is the whole ceremony: revoke, confirm, done, with the user re-proving
ownership on the way back in.

### 3.2 Bulk Work & Protection

#### UC-USER-003 — Locking a misbehaving lab group

After an incident in the computer lab, the admin checks twelve rows and hits Lock. Each id
flows through the status Action: transition validated against the map, self-selection
refused (the admin cannot lock their own session out from under themselves), the super admin
refused by integrity rule, each success emitting `UserStatusChanged` and a notice to the
affected student. The batch reports what moved and what was skipped instead of failing whole
on the first refusal.

#### UC-USER-004 — The delete that must never succeed

Sooner or later someone — a curious new admin, a script, a tinker session — aims a delete at
the super admin. The observer throws first at the model layer; the Action's explicit role
guard throws second; the edit modal never even opens at the UI layer; name, username, and
status mutations each meet their own integrity refusal. Four independent walls, no shared
key: bypassing one still leaves three standing between the click and a school-wide lockout.

#### UC-USER-006 — Graduating five hundred students at once

July arrives and last year's cohort must retire without dragging five hundred rows into
memory. The archive Action takes a query, walks it in chunks of one hundred, skips the super
admin wherever it hides, and sets each student to archived — returning a count the admin can
read back. Peak memory stays flat whether the cohort is fifty or five thousand, because the
chunk boundary, not the cohort size, decides the footprint.

---

## 4. Functional Requirements

The full contract: creation, deletion, state machine, profile, managers, protection,
lifecycle. `Layer` marks the verifying test layer; every row is implemented and verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-USER-001 | `CreateUserAction` validates name (required, max 255, not reserved), username (unique, system format, not reserved), and email (unique, valid) | P0 | F | Full |
| FR-USER-002 | `CreateUserAction` derives a username from the email when none is given, resolving collisions with an alphanumeric suffix via `UserIdentifierGenerator` | P0 | F | Full |
| FR-USER-003 | `CreateUserAction` mints a random 12-character password when none is given | P0 | F | Full |
| FR-USER-004 | `CreateUserAction` persists User plus Profile plus role grants inside one database transaction | P0 | F | Full |
| FR-USER-005 | `CreateUserAction` sends `ActivationCodeNotification` with a token from `AccessToken::generateFor()` | P0 | F | Full |
| FR-USER-006 | `CreateUserAction` sends `WelcomeNotification` carrying the plaintext password exactly when the password was auto-generated | P0 | F | Full |
| FR-USER-007 | `UpdateUserAction` atomically updates user data, upserts the profile, and syncs roles | P0 | F | Full |
| FR-USER-008 | `UpdateUserAction` refuses name and username changes on the super admin via `SuperAdminIntegrityRules` | P0 | F | Full |
| FR-USER-009 | `DeleteUserAction` refuses deletion of the super admin and of the acting admin's own account | P0 | F | Full |
| FR-USER-010 | `BatchDeleteUserAction` skips self, super admin, and missing rows, returning `deleted` and `skipped` counts | P0 | F | Full |
| FR-USER-011 | All CRUD actions extend `BaseCommandAction` and wrap writes in `$this->transaction()` | P0 | A | Full |
| FR-USER-012 | All CRUD actions dispatch their domain events (`UserCreated`, `UserUpdated`, `UserDeleted`) after success | P0 | F | Full |
| FR-USER-013 | `AccountStatus` enum implements the `StatusEnum` and `ColorableEnum` contracts | P0 | U | Full |
| FR-USER-014 | The enum defines exactly 8 states: `PROVISIONED`, `ACTIVATED`, `VERIFIED`, `PROTECTED`, `RESTRICTED`, `SUSPENDED`, `INACTIVE`, `ARCHIVED` | P0 | U | Full |
| FR-USER-015 | `allowsLogin()` is true for activated, verified, protected, restricted, and inactive, false for provisioned, suspended, and archived | P0 | U | Full |
| FR-USER-016 | `isTerminal()` is true for `PROTECTED` and `ARCHIVED` and nothing else | P0 | U | Full |
| FR-USER-017 | `validTransitions()` pins the full map: provisioned→activated/suspended; activated→verified/suspended/archived; verified→restricted/suspended/archived/inactive; protected→none; restricted→verified/suspended/archived; suspended→activated/verified/archived; inactive→verified/archived/suspended; archived→none | P0 | U | Full |
| FR-USER-018 | `canTransitionTo()` returns false for every transition out of a terminal state | P0 | U | Full |
| FR-USER-019 | Transition truth lives in the Entity: the account Entity owns `validTransitions()`, `canTransitionTo()`, and `isTerminal()`, and `SetUserStatusAction` delegates to it instead of re-implementing the map | P0 | U | Full |
| FR-USER-020 | `SetUserStatusAction` validates the move through `canTransitionTo()` before touching the row | P0 | F | Full |
| FR-USER-021 | `SetUserStatusAction` refuses self-status-change and any status change on the super admin | P0 | F | Full |
| FR-USER-022 | `ToggleUserStatusAction` moves only between `VERIFIED` and `SUSPENDED` | P1 | F | Full |
| FR-USER-023 | Every applied status change dispatches `UserStatusChanged` | P0 | F | Full |
| FR-USER-024 | Every applied status change sends `AccountStatusNotification` to the affected user | P1 | F | Full |
| FR-USER-025 | `color()` returns warning/info/success/primary/warning/error/warning/error across the eight states in order | P1 | U | Full |
| FR-USER-026 | `label()` returns the translated string via `__('account_status.status.'.$this->value)` | P0 | U | Full |
| FR-USER-027 | `Profile` belongs to User, Department, and Company | P1 | F | Full |
| FR-USER-028 | `Profile` casts gender, blood type, birth date, and emergency contact to their rich types | P1 | U | Full |
| FR-USER-029 | `Profile` whitelists its fillable set through the `#[Fillable]` attribute | P0 | A | Full |
| FR-USER-030 | `UpdateProfileAction` validates and persists profile changes with upsert semantics | P0 | F | Full |
| FR-USER-031 | Profile changes dispatch `ProfileUpdated` | P1 | F | Full |
| FR-USER-032 | `ProfilePolicy` grants access to admins and to the owning user only | P0 | U | Full |
| FR-USER-033 | `UserManager` extends `BaseRecordManager` with full CRUD, search, filter, and pagination | P0 | F | Full |
| FR-USER-034 | `UserManager` searches across name, email, username, and profile phone | P1 | F | Full |
| FR-USER-035 | `UserManager` filters by role, status, and creation date range | P1 | F | Full |
| FR-USER-036 | `UserManager` renders name, email, profile phone, role list, status, and actions columns | P1 | F | Full |
| FR-USER-037 | `UserManager` eager-loads `roles` and `profile` on every listing query | P0 | F | Full |
| FR-USER-038 | `UserManager::roles()` offers every role except super admin and admin, which belong to `AdminManager` | P1 | F | Full |
| FR-USER-039 | `UserManager` listing and CSV export exclude admin-role users; admin accounts live exclusively in `AdminManager` | P0 | F | Full |
| FR-USER-040 | `UserManager::statusOptions()` offers every status except `PROTECTED` and `ARCHIVED` | P1 | F | Full |
| FR-USER-041 | `StudentManager` adds the department filter and student fields | P1 | F | Full |
| FR-USER-042 | `TeacherManager` shows the id-number (NIP) column | P1 | F | Full |
| FR-USER-043 | `SupervisorManager` shows the company column with its filter | P1 | F | Full |
| FR-USER-044 | `AdminManager` is reachable by admins only | P0 | F | Full |
| FR-USER-045 | Every manager authorizes `viewAny` on User in `boot()` | P0 | F | Full |
| FR-USER-046 | `UserObserver::deleting()` throws `RejectedException` for a user holding the super-admin role | P0 | F | Full |
| FR-USER-047 | `User::delete()` consults `asSuperAdminIntegrityRules()->canBeDeleted()` and throws `RejectedException` on refusal | P0 | F | Full |
| FR-USER-048 | `DeleteUserAction` checks the super-admin role before deleting | P0 | F | Full |
| FR-USER-049 | `UserManager::editUser()` answers the super admin with a flash error instead of opening the modal | P0 | F | Full |
| FR-USER-050 | `UserManager::confirmAction()` verifies the super-admin role before confirming deletion | P0 | F | Full |
| FR-USER-051 | `UpdateUserAction` routes name changes through `canChangeName()` and username changes through `canChangeUsername()` | P0 | F | Full |
| FR-USER-052 | `SetUserStatusAction` routes super-admin moves through `canBeLocked()` and refuses on denial | P0 | F | Full |
| FR-USER-053 | `ToggleUserStatusAction` routes super-admin toggles through `canBeLocked()` and refuses on denial | P0 | F | Full |
| FR-USER-054 | `BatchDeleteUserAction` skips the super admin inside the iteration | P0 | F | Full |
| FR-USER-055 | `ArchiveStudentAccountsAction` archives through a chunked query at 100 rows per chunk | P0 | F | Full |
| FR-USER-056 | `ArchiveStudentAccountsAction` skips super-admin accounts | P0 | F | Full |
| FR-USER-057 | `RevokeUserActivationTokensAction` revokes all of a user's activation tokens via `AccessToken::revokeFor()` | P0 | F | Full |

### 4.1 Creation & Update

#### FR-USER-001 — Creation validates like a bouncer with a list

Name present and bounded, username unique and machine-shaped, email unique and well-formed,
reserved words turned away at the door. Each rule maps to a real embarrassment avoided: the
blank-name row in the printed roster, the `admin` username squatting on a student account,
the typo'd email that swallowed an activation link. Validation runs in the Action so it
holds for CSV imports and tinker calls alike, not just the modal.

#### FR-USER-002 — Usernames mint themselves

Asking admins to invent two hundred unique usernames is how you get `siswa001` through
`siswa200` and a collision on the transfer student. Deriving from the email local-part keeps
usernames recognizable, and the suffix walk on collision keeps them unique without human
creativity. The generator owns the algorithm so every entry point mints the same way.

#### FR-USER-003 — Passwords mint themselves too

A blank password field is not an omission, it is the recommended path: twelve random
characters, no human pattern to guess. Admins who insist on typing one may, but the default
steers them away from `kelas12a` securing a quarter of the roster. Random-by-default is a
security posture disguised as convenience.

- Length and charset are fixed in the Action, not configurable per call.
- The plaintext exists only long enough to notify; then only the hash remains.

#### FR-USER-004 — Three writes, one fate

User, profile, roles — created together or not at all. The transaction draws a circle around
all three so a role-sync failure cannot leave a login with no identity, and a profile
failure cannot leave an identity with no login. Callers get a User back or an exception;
the middle states are simply unreachable.

#### FR-USER-005 — Every birth announces itself

The activation notification is part of creation, not an afterthought somebody remembers to
send. Token minted through `AccessToken::generateFor()`, notification carrying it, delivery
attempted before the Action returns. An account nobody told its owner about is a support
ticket with a head start.

#### FR-USER-006 — Minted secrets travel once, in the open

When the system chose the password, the owner must learn it exactly once — hence the welcome
notice carrying plaintext. When a human chose it, no notice goes out, because echoing a
known secret doubles its exposure for zero benefit. The condition is precise: auto-generated
means notified, hand-picked means silent.

#### FR-USER-007 — Updates land as a unit

Name, email, profile fields, roles — edited together in the modal, persisted together in the
Action. The profile upsert means no "create profile first" ceremony for users predating some
field; the role sync means deselected roles actually leave. Atomicity here protects the
admin from their own interrupted request as much as from bugs.

#### FR-USER-008 — The super admin's name is carved in stone

`Super Admin` and `superadmin` are load-bearing strings: setup scripts, recovery flows, and
human habit all assume them. The integrity rules refuse renames at the Action layer, so even
a direct call with a plausible new name bounces off with a translatable refusal. Cosmetic
edits elsewhere on the account remain allowed; identity itself does not move.

### 4.2 Deletion & Batch

#### FR-USER-009 — Two deletions that never happen

Self-deletion would let an admin click themselves out of existence mid-session; super-admin
deletion would lock the school out of its own system. Both refusals live in the Action,
checked before any write, raising `RejectedException` with messages suitable for a toast.
Protection that depends on the UI hiding a button is decoration; this is enforcement.

#### FR-USER-010 — Batches bend, they don't snap

A hundred-id batch containing the admin themselves, the super admin, and three already-gone
rows must still delete the other ninety-five. Skipping with counts turns the operation from
fragile to administrative: the result tells you what moved and what didn't, and the skipped
are inspectable rather than fatal. Partial success is the specified outcome, not a
degraded mode.

#### FR-USER-011 — Every mutation wears the uniform

Extending `BaseCommandAction` and transacting is not boilerplate, it is how audit logging,
error mapping, and rollback become structural instead of remembered. A reviewer never asks
whether some CRUD Action logs — the base answers. The scan enforces the inheritance, so
drift is caught by tooling before it reaches review.

#### FR-USER-012 — State changes announce themselves downstream

Slips, caches, notifications, and reports all listen for `UserCreated`, `UserUpdated`, and
`UserDeleted`. Dispatching after success — never before, never on failure — keeps every
listener's world consistent with the database. An event for a write that rolled back is a
lie the system tells itself; this ordering makes it impossible.

### 4.3 AccountStatus State Machine

#### FR-USER-013 — The enum signs two contracts

`StatusEnum` plugs the status into the transition machinery every state machine in the
system shares; `ColorableEnum` plugs it into badges, tables, and exports. Signing both
means account status renders and constrains through the same object, so the color on the
badge can never disagree with the rule in the guard — they read the same case.

#### FR-USER-014 — Eight states, no ninth

Provisioned, activated, verified, protected, restricted, suspended, inactive, archived: the
closed set every transition, test, and badge enumerates. Adding a state is a spec amendment,
not a drive-by enum edit, because each new case multiplies the transition matrix the tests
must pin. Finiteness here is a feature the whole section depends on.

#### FR-USER-015 — Login permission is a lookup, not an opinion

Which states may start a session is fixed data on the enum, one boolean per case. The night
someone argued suspended users should "just check announcements," the answer was a row in
this table, not a debate in a controller. Authentication consults the enum; the enum does
not consult the request.

#### FR-USER-016 — Two states are final

Protected and archived answer no further transitions — the first because it shelters
system-critical accounts from lifecycle machinery, the second because graduation must mean
something. Terminality is declared, not inferred from an empty map, so a future edit that
accidentally adds an outgoing edge fails loudly against this row.

- No caller may special-case its way out of a terminal state.
- Recovery from archived is a new provisioning flow, never a transition.

#### FR-USER-017 — The whole map in one place

Every legal move, spelled out: provisioned wakes to activated or falls to suspended;
activated verifies, suspends, or archives; verified restricts, suspends, archives, or idles;
restricted returns, suspends, or archives; suspended reactivates, verifies, or archives;
inactive re-verifies, archives, or suspends; protected and archived go nowhere. This row is
the matrix the unit tests iterate exhaustively — all sixty-four pairs, each asserting its
fate.

#### FR-USER-018 — Terminal means terminal, tested as such

`canTransitionTo()` from protected or archived returns false for all seven targets, and the
test loops all fourteen combinations to prove it. The method is the single chokepoint every
guard calls, so this row plus the Entity-ownership row below close every path to resurrecting
a closed account through normal operations.

#### FR-USER-019 — The Entity owns the truth about movement

This is the ADR-demanded row: transition knowledge lives on the account Entity — the map,
the terminal predicate, the check — and `SetUserStatusAction` calls into it rather than
carrying a second copy. Two copies of a state map always diverge; one copy with many readers
stays honest. Unit tests construct the Entity from arrays in milliseconds, no database, and
pin the matrix where it actually lives.

#### FR-USER-020 — No guard, no transition

The Action asks `canTransitionTo()` before writing, every time, without exception flags or
fast lanes. A direct call with a plausible-but-illegal move — verified straight to archived
is legal, provisioned straight to archived is not — meets the same refusal the UI would
produce. Business rules ride with the operation, not the form.

#### FR-USER-021 — You cannot suspend yourself, nor the untouchable

Self-status-change is refused because an admin locking their own session mid-batch is a
footgun with no upside; super-admin change is refused because break-glass access outranks
every workflow. Both checks precede the transition check — identity protection first,
workflow validity second — so the error message names the real problem.

#### FR-USER-022 — The toggle knows exactly one trick

Quick lock/unlock flips verified to suspended and back, nothing else. Narrowing the toggle
to one pair keeps the one-click control safe to expose on every row: it cannot archive,
cannot verify the unverified, cannot wake the inactive. Anything fancier goes through the
full status dialog with its reason field.

#### FR-USER-023 — Movement emits an event

`UserStatusChanged` carries who, from what, to what, and why — the raw material for audit
exports, notification listeners, and the occasional forensic "who suspended this student in
October?" Dispatching on every applied change, including system-initiated ones, keeps the
log complete rather than merely UI-complete.

#### FR-USER-024 — The affected user hears about it

A suspension discovered at the login screen feels like a malfunction; the same suspension
announced by notification feels like administration. Sending `AccountStatusNotification` on
every change respects the person inside the row. Delivery failures log and continue — the
status already committed must not roll back because a mailbox bounced.

#### FR-USER-025 — Eight states, eight colors, zero ambiguity

Warning for provisioned, info for activated, success for verified, primary for protected,
warning for restricted and inactive, error for suspended and archived. The mapping is fixed
so exports, PDFs, and third-party readers render identically — and color never travels
alone, since labels ride alongside per the experience rows below.

#### FR-USER-026 — Labels speak the user's language

`__('account_status.status.'.$this->value)` resolves through both locale files, so the badge
reads "Terverifikasi" or "Verified" depending on session, never a raw `verified` slug. The
translation call lives inside `label()`, which means every renderer gets localization for
free and no Blade template hand-builds a key and gets it subtly wrong.

### 4.4 Profile

#### FR-USER-027 — One profile, three homes

User for identity, department for academic home, company for industry placement: the three
`BelongsTo` links let a roster query walk from student to department to company without
detours. Nullable department and company keep the profile valid before placement assigns
either — the links describe reality, they don't force it early.

#### FR-USER-028 — Rich types at the boundary

Gender and blood type arrive as enums, birth date as a date, emergency contact as structured
JSON — cast on read so consumers never parse strings by hand. A report counting blood types
for a field-trip medical sheet reads typed values, not substrings. Casting in one place
kills a hundred ad-hoc parsers.

#### FR-USER-029 — Fillable is an allowlist, audited

The `#[Fillable]` attribute names exactly the mass-assignable columns; everything else —
notably `user_id` handling and any future sensitive field — stays off it. Mass assignment
without an allowlist once let a crafted request set fields the form never showed; the
attribute plus the scan make that shape unrepresentable now.

#### FR-USER-030 — Profile writes upsert

`updateOrCreate` absorbs users whose profiles predate fields, imports, or refactors: no
"profile missing" errors, no manual backfill step, no branching on existence. Validation
still runs first — upsert is about persistence shape, never about skipping rules.

#### FR-USER-031 — Profile edits are events too

Downstream listeners — completeness scores, change digests, sync jobs — subscribe to
`ProfileUpdated` rather than polling rows. Emitting on every validated change keeps derived
views fresh without coupling the Action to each consumer. Consumers come and go; the event
stays.

#### FR-USER-032 — Profiles open to two parties

Admins manage; owners view and edit their own; nobody else enters. The policy encodes
exactly that, tested per role with allow/deny pairs and no database beyond the model. A
student reading another student's emergency contact is not a missing feature, it is this
row holding.

### 4.5 Livewire Managers

#### FR-USER-033 — Every manager inherits the record machinery

Search, filter, sort, pagination, selection, bulk confirmations — `BaseRecordManager` brings
the whole kit, so each role manager adds only its columns and filters. The fortieth admin
table behaves like the first because both inherit the behavior instead of retyping it. New
managers start working, not start from scratch.

#### FR-USER-034 — Search reaches across the join

Name, email, username, plus the profile's phone: the four things an admin actually has when
someone stands at their desk. Searching the relation costs one eager load the listing
already pays, so the phone lookup is effectively free. Finding people fast is the manager's
whole job; this row is that job quantified.

#### FR-USER-035 — Filters slice by role, status, and time

"Show me suspended students created this month" is a support conversation that happens
weekly. Role, status, and a creation window compose to answer it without exporting to a
spreadsheet. Each filter narrows server-side on indexed columns, so the answer stays fast
at full roster size.

#### FR-USER-036 — Columns show what decisions need

Name and email identify, phone reaches, roles and status explain, actions invite the next
step. Nothing decorative, nothing missing: every column either answers "who is this" or
"what can I do." Column choice is UX specification, and this row pins it against drift.

#### FR-USER-037 — Listings never N+1

Roles and profile eager-loaded on every listing query — the morning dashboard renders one
thousand rows with a flat query count instead of two thousand extra. N+1 at roster scale is
not a slowdown, it is a timeout; this row makes the fast shape the only shape.

- The eager loads live in `query()`, not in a caller that might forget.
- Export paths reuse the same loaded relations.

#### FR-USER-038 — Admin roles hide from the wrong desk

`roles()` omits super admin and admin because those accounts are governed in `AdminManager`
with its stricter access. Showing them in the general manager would invite edits from hands
without the clearance to make them. Separation here is authorization expressed as UI scope.

#### FR-USER-039 — Admin accounts live in exactly one place

Listing and export both exclude admin-role users from `UserManager`, full stop. Dual
enforcement matters: the list hides them from casual browsing, the export exclusion keeps
them out of spreadsheets that travel by email. One home for privileged accounts means one
place to audit who touched them.

#### FR-USER-040 — Dangerous states hide from the dropdown

Protected and archived never appear as choices in the quick status control — not because the
transitions are illegal from every state, but because the dropdown is a fast path and fast
paths must not offer irreversible-adjacent moves. Reaching those states requires the
deliberate flows that own them.

#### FR-USER-041 — Students get their department lens

The student manager adds the department filter and student fields because "which department"
is the first question about any student list. A school with eight departments and nine
hundred students navigates by department the way a library navigates by shelf. Role-specific
managers earn their existence one filter like this at a time.

#### FR-USER-042 — Teachers show their registration number

The NIP column turns the teacher table from a name list into an employment record — the
identifier payroll, certification, and government reporting all key on. Its presence here
reflects that teachers are staff with staff identifiers, not students with different
permissions.

#### FR-USER-043 — Supervisors show their company

A supervisor without a company is a name without context; the company column plus its filter
answers "who supervises at PT Maju Jaya?" directly. Industry-side staff are reached through
their workplace, so the manager organizes them that way instead of alphabetically.

#### FR-USER-044 — The admin desk is behind a second door

`AdminManager` requires admin clearance because it governs the accounts that govern
everything else. Edits here can create school-wide capability, so the audience narrows to
the roles already holding it. Privilege administers privilege, and nothing lesser reaches
the controls.

#### FR-USER-045 — Every manager proves its right to load

`boot()` authorizing `viewAny` means no manager renders for unauthorized eyes, regardless of
route configuration or remembered URLs. Defense runs at the component, not just the route —
a student guessing the admin URL meets the same refusal as an unauthenticated visitor.

### 4.6 Super Admin Protection

#### FR-USER-046 — The observer is the last wall

`deleting()` throwing `RejectedException` catches the paths nothing else sees: tinker
sessions, stray scripts, cascade deletes nobody traced. The observer does not ask who
called — role present means refusal, always. It is deliberately dumb, because the final
safety net must not have conditions to misconfigure.

#### FR-USER-047 — The model guards its own destruction

`User::delete()` consulting integrity rules before proceeding means the protection travels
with the object, not with the caller. Any code path reaching for delete — Action, observer
bypass, future bulk tool — meets the same check. Objects that protect themselves survive
callers that forget.

#### FR-USER-048 — The Action states the refusal plainly

The explicit role check in `DeleteUserAction` produces the user-facing refusal: a toast
explaining the account cannot be deleted, not a database error, not silence. UI authors
calling this Action inherit correct behavior without knowing the protection exists. Clarity
at this layer is what the admin actually sees.

#### FR-USER-049 — The edit modal never opens

Answering the super-admin row with a flash error instead of a form removes temptation and
confusion alike — no fields to change, no save button to wonder about. The refusal happens
before data loads, so not even current values leak into a context where they could be
edited by mistake.

#### FR-USER-050 — Confirmation dialogs check too

Bulk flows confirm before acting, and the confirmation re-verifies the role — closing the
gap where selection state changes between listing and confirm. A super-admin id smuggled
into `selectedIds` dies here, before the batch runs. Every step that can say no, does.

#### FR-USER-051 — Identity fields route through integrity rules

Name and username changes consult `canChangeName()` and `canChangeUsername()`, keeping the
gate logic in the Entity where tests pin it without a database. The Action asks; the
Entity decides. When the rule next tightens — say, freezing more fields — exactly one
object changes.

#### FR-USER-052 — Status moves check the lock rule

`SetUserStatusAction` asks `canBeLocked()` for the super admin and refuses on denial,
because suspending break-glass access is deletion with extra steps. The check precedes
transition validation: identity protection outranks workflow validity, and the message says
so.

#### FR-USER-053 — The toggle obeys the same lock rule

One-click controls are the most likely to be clicked thoughtlessly, so the toggle carries
the identical `canBeLocked()` refusal. Convenience must never be the hole in the wall —
especially not the control designed for speed.

#### FR-USER-054 — Batches skip the sacred row silently-ish

Inside the iteration, the super admin counts as skipped, not failed: the batch completes,
the counts report honestly, and no exception aborts ninety-nine legitimate deletions.
Protection composes with bulk work instead of opposing it.

### 4.7 Lifecycle

#### FR-USER-055 — Archival walks, it doesn't swallow

Chunked at one hundred, the archive query processes cohorts of any size with flat memory —
each chunk loads, transitions, and releases before the next begins. Graduation-season loads
that once threatened the request cycle now pass through unnoticed. The chunk size is fixed
in the Action so no caller can "optimize" it into an OOM.

#### FR-USER-056 — Archival never retires the break-glass

The super admin is skipped wherever it appears in the archive query — even inside a
student-scoped cohort it should never inhabit, because defensive code assumes the query is
wrong, not the data. Skipping unconditionally costs one condition and removes an entire
class of catastrophe.

#### FR-USER-057 — Reset means revoke, not replace

`RevokeUserActivationTokensAction` invalidates every activation token through
`AccessToken::revokeFor()`, forcing the user back through activation to set a fresh secret.
The admin never learns, sets, or sees a password — revocation is the whole intervention,
and re-proof of ownership happens on the way back in.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-USER-001 | Listing queries eager-load roles and profile; no N+1 at any roster size | Flat query count on 1000-row listing | P0 | F | Full |
| NFR-USER-002 | Mass operations walk chunked queries so memory stays bounded | Peak flat from 50 to 5000 rows | P0 | F | Full |
| NFR-USER-003 | Batch operations skip protected and missing targets without aborting the batch | Partial counts always returned | P0 | F | Full |
| NFR-USER-004 | Super admin is defended at observer, model, action, and UI layers independently | All four layers refuse in test | P0 | F | Full |
| NFR-USER-005 | Passwords are hashed on every write path; plaintext never persists | Zero plaintext secrets at rest | P0 | A | Full |
| NFR-USER-006 | Status moves are validated through `canTransitionTo()` before application | Zero unvalidated writes | P0 | F | Full |
| NFR-USER-007 | Self-status-change is refused in both status Actions | 100% of self-moves refused | P0 | F | Full |
| NFR-USER-008 | System-initiated transitions may bypass the acting-user checks via an explicit flag | Flag present and audited per use | P1 | F | Full |
| NFR-USER-009 | Batch delete reports `deleted` and `skipped` counts on every run | Counts present even when all skip | P1 | F | Full |
| NFR-USER-010 | Notification failures during creation are caught, logged, and never propagated | Account survives mail outage | P1 | F | Full |
| NFR-USER-011 | Every CRUD operation answers with a TallstackUI toast, success or failure | Zero silent mutations | P1 | F | Full |
| NFR-USER-012 | Status badges pair every color with its translated text label via `label()` | Zero color-only badges | P1 | F | Full |
| NFR-USER-013 | Destructive actions confirm through keyboard-trappable modals | Focus trapped; Esc cancels | P1 | B | Full |
| NFR-USER-014 | Managers delegate mutations to Actions; no inline Livewire persistence | Zero `create/update/delete` in components | P0 | A | Full |
| NFR-USER-015 | All manager input validates through Form Objects, never inline rules | Zero inline rule arrays | P0 | A | Full |
| NFR-USER-016 | Write actions extend `BaseCommandAction` (reads `BaseReadAction`) and audit-log via `$this->log()` | Every mutation logged | P0 | A | Full |
| NFR-USER-017 | Every user-facing string passes through `__()` with mirrored `en`/`id` keys | Zero hardcoded strings | P0 | A | Full |

### 5.1 Performance & Reliability

#### NFR-USER-001 — A thousand rows, a flat query count

The listing test seeds a full roster, renders the manager, and counts queries — then
asserts the number does not grow with rows. Eager loading is what makes that true, and
pinning it as a requirement means the next well-meaning `withCount` addition gets measured
before it merges. Performance here is a number in a test, not a feeling in review.

#### NFR-USER-002 — Memory that ignores cohort size

Archiving fifty or five thousand must peak identically, because chunks bound the working
set. The test runs both sizes and compares peaks; a future refactor that "simplifies" the
chunk into a `get()->each()` fails before it ships. Scale safety as a regression test.

#### NFR-USER-003 — Batches report, never collapse

Skipping the unskippable — self, super admin, ghosts — keeps the batch honest: counts out,
exceptions none. Operators learn to read `deleted`/`skipped` the way pilots read gauges,
and a batch that aborted halfway with no accounting becomes a story nobody here can tell.

#### NFR-USER-009 — Counts on every run, even all-skipped

A batch that skips everything still returns its zeros, because "nothing happened" is
information the admin needs confirmed, not inferred from silence. The counts shape is part
of the contract — importers, scripts, and tests all read the same two keys.

#### NFR-USER-010 — Mail outages must not eat accounts

When the mail driver is down on creation morning, the account still lands and the failure
lands in the log. Notification sending is wrapped, caught, and recorded — a degraded
ceremony, not a failed birth. The test kills the mailer and asserts the User exists anyway.

### 5.2 Security

#### NFR-USER-004 — Four walls, tested separately

Observer, model, action, UI: each layer gets its own refusal test, each bypassing the
layers above it. Tinker reaches past the UI and meets the Action; direct calls reach past
the Action and meet the observer. Independence is the point — one test per wall, no wall
trusting another.

#### NFR-USER-005 — Hashes only, everywhere

`Hash::make()` on every path that writes a password; no plaintext column, no plaintext log,
no "temporary" exception during imports. The scan asserts the absence structurally, because
a secret at rest is a breach waiting for a backup to leak.

#### NFR-USER-006 — Validation before mutation, always

The transition check precedes the write with no flag to skip it for convenience. "Just this
once" moves are how state machines die — one unvalidated write, then a report that assumes
invariants the data no longer honors. The order is fixed: validate, then touch.

#### NFR-USER-007 — Hands off your own switch

Self-lockout is the support ticket nobody can resolve remotely, since the resolver just
locked themselves out too. Both status Actions refuse the acting user's own id first, with
a message explaining why. Administrators administer others; the mirror is off limits.

#### NFR-USER-008 — The system gets a labeled key

Automated transitions carry an explicit skip flag, audited at each use site, so machine
moves are distinguishable from human ones in the log. The flag exists because lifecycle
jobs act with no session — not because humans need shortcuts. Every use names its reason.

### 5.3 Experience, Structure & Localization

#### NFR-USER-011 — Every mutation gets its toast

Success or failure, the admin hears back within the same interaction — a toast, not a
silent row change they must scroll to verify. Silent mutations train operators to distrust
the UI and double-click everything; feedback trains the opposite. Zero silent writes is a
UX invariant with test coverage.

#### NFR-USER-012 — Color never speaks alone

Red-green distinctions vanish for color-blind operators, so each badge pairs its color with
the translated label from `label()`. The pairing is structural — badge components call the
same method — which means new states arrive accessible by default instead of by reminder.

#### NFR-USER-013 — Destructive intent is confirmed deliberately

Delete, lock, and unlock confirm through modals that trap focus and yield to Escape,
keeping keyboard operators inside the decision until they make it. A confirmation that can
be tabbed out of accidentally is a confirmation in name only. The modal is the last chance;
it behaves like one.

#### NFR-USER-014 — Components conduct, Actions play

No `User::create()` in Livewire, ever — components validate input, call Actions, and render
responses. The scan enforces the absence structurally. Business logic with two homes always
diverges; this row guarantees one.

#### NFR-USER-015 — Forms own their rules

Validation lives in Form Objects shared by every caller of the flow, not in component
methods that each remember differently. One rule change updates every surface at once. The
scan flags inline rule arrays so convenience never quietly forks the contract.

#### NFR-USER-016 — Mutations log through the base

`$this->log()` on every write Action routes through SmartLogger with PII masking into both
channels — the activity entry the auditors query and the system line the operators grep.
Logging inherited from the base is logging that survives deadline pressure.

#### NFR-USER-017 — Two languages, zero exceptions

Indonesian primary, English secondary, `__()` on every string, keys mirrored in both
locales. The D3 scan fails the build on a hardcoded string, which is exactly as strict as
it should be: the first impression hundreds of students get of the system must not be a
translation key.

---

## 6. API / Data Contracts

### 6.1 AccountStatus Enum

```php
// app/Modules/User/Enums/AccountStatus.php
enum AccountStatus: string implements ColorableEnum, StatusEnum
{
    case PROVISIONED = 'provisioned'; // warning, login: no
    case ACTIVATED = 'activated';     // info,    login: yes
    case VERIFIED = 'verified';       // success, login: yes
    case PROTECTED = 'protected';     // primary, login: yes, terminal
    case RESTRICTED = 'restricted';   // warning, login: yes
    case SUSPENDED = 'suspended';     // error,   login: no
    case INACTIVE = 'inactive';       // warning, login: yes
    case ARCHIVED = 'archived';       // error,   login: no, terminal

    public function color(): string;
    public function label(): string;  // __('account_status.status.'.$this->value)
    public function allowsLogin(): bool;
    public function isTerminal(): bool;
    public function validTransitions(): array;
    public function canTransitionTo(StatusEnum $target): bool;
}
// Map: PROVISIONED→ACTIVATED,SUSPENDED; ACTIVATED→VERIFIED,SUSPENDED,ARCHIVED;
// VERIFIED→RESTRICTED,SUSPENDED,ARCHIVED,INACTIVE; PROTECTED→(none);
// RESTRICTED→VERIFIED,SUSPENDED,ARCHIVED; SUSPENDED→ACTIVATED,VERIFIED,ARCHIVED;
// INACTIVE→VERIFIED,ARCHIVED,SUSPENDED; ARCHIVED→(none)
```

### 6.2 Account Entity (Transition Owner)

```php
// app/Modules/User/Entities/UserAccountState.php (final readonly)
final readonly class UserAccountState extends BaseEntity
{
    public static function fromModel(Model $model): static;
    public function validTransitions(): array;               // FR-USER-017 map
    public function canTransitionTo(StatusEnum $target): bool; // false from terminal
    public function isTerminal(): bool;
    public static function rules(): array;                    // shared validation
}
// SetUserStatusAction and ToggleUserStatusAction delegate to this Entity;
// they never carry their own copy of the map (FR-USER-019).
```

### 6.3 Action Signatures

```php
final class CreateUserAction extends BaseCommandAction
{
    public function execute(array $userData, array $profileData = [], array $roles = [], bool $sendNotification = true): User;
}
final class UpdateUserAction extends BaseCommandAction
{
    public function execute(User $user, array $userData, ?array $profileData = null, ?array $roles = null): User;
}
final class DeleteUserAction extends BaseCommandAction
{
    public function execute(User $user): void; // refuses super admin + self
}
final class BatchDeleteUserAction extends BaseCommandAction
{
    public function __construct(protected readonly DeleteUserAction $deleteAction) {}
    /** @return array{deleted: int, skipped: int} */
    public function execute(array $ids): array;
}
final class SetUserStatusAction extends BaseCommandAction
{
    public function execute(User $user, AccountStatus $newStatus, ?string $reason = null, bool $skipAuthCheck = false): User;
}
final class ToggleUserStatusAction extends BaseCommandAction
{
    public function execute(User $user, ?string $reason = null): User; // VERIFIED ↔ SUSPENDED
}
final class UpdateProfileAction extends BaseCommandAction
{
    public function execute(User $user, array $profileData): Profile; // updateOrCreate
}
final class ArchiveStudentAccountsAction extends BaseCommandAction
{
    public function execute(Builder $query): int; // chunk(100), skips super admin
}
final class RevokeUserActivationTokensAction extends BaseCommandAction
{
    public function execute(User $user): void; // AccessToken::revokeFor($user, 'activation')
}
```

### 6.4 User & Profile Models

```php
// app/Modules/User/Models/User.php — extends Authenticatable (auth requirement),
// manually applies HasUuids + non-incrementing string keys (UUID v7, FR-GLB-007).
// #[Fillable]: name, email, username, password, setup_required, first_login_at,
//   locked_at, locked_reason, status, is_active
// Casts: password → hashed, setup_required → boolean, status → AccountStatus,
//   locked_at/first_login_at/email_verified_at → datetime
// Relations: profile() HasOne, registrations() HasMany; avatar via medialibrary
// Entity bridges: asStudent(), asTeacher(), asSupervisor(), asAdmin(),
//   asAccountActivation(), asSuperAdminIntegrityRules(), asAccountState()

// app/Modules/User/Profile/Models/Profile.php — extends BaseModel (UUID v7).
// #[Fillable]: user_id, phone, address, bio, gender, blood_type, pob, dob,
//   emergency_contact, id_number, national_id_number, competence_field,
//   employment_status, job_title, internal_notes, department_id, company_id
// Casts: gender → Gender, blood_type → BloodType, dob → date, emergency_contact → array
```

### 6.5 Tables

```sql
-- users: id UUID PK, name, email, username UNIQUE, email_verified_at NULL,
--   password, remember_token NULL, setup_required BOOL, first_login_at NULL,
--   locked_at NULL, locked_reason NULL, status DEFAULT 'provisioned',
--   is_active BOOL, last_activity_at NULL, timestamps;
--   INDEX(status), INDEX(locked_at), INDEX(setup_required), INDEX(is_active)
-- profiles: id UUID PK, user_id UUID UNIQUE FK→users CASCADE, phone, address, bio,
--   gender, blood_type, pob, dob, emergency_contact JSON, id_number,
--   national_id_number, competence_field, employment_status, job_title,
--   internal_notes, department_id NULL FK→departments SET NULL,
--   company_id NULL FK→companies SET NULL, timestamps;
--   INDEX(department_id), INDEX(company_id)
```

---

## 7. Design Decisions

Choices that shaped the code above; each names the structure it produced.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-USER-001 | Role-specific Livewire managers over one generic table | P0 | — | — |
| DD-USER-002 | Account status as an enum state machine with Entity-owned guards | P0 | — | — |
| DD-USER-003 | Super admin defended at four independent layers | P0 | — | — |
| DD-USER-004 | Mass operations walk chunked queries at 100 rows per chunk | P1 | — | — |

### 7.1 Structure

#### DD-USER-001 — Five desks instead of one counter

One generic manager with role tabs concentrates every role's columns, filters, and fields
into conditional branches that rot within a term. Five thin managers over a shared record
base keep each desk simple: ten to thirty lines of role-specific code each, the rest
inherited. More files, radically less branching — and each desk can evolve without
renegotiating the others. (CSV skip-on-duplicate policy referenced here lives in
[bulk import](O2KCR-csv-import-export.md) DD-CSV-001.)

#### DD-USER-002 — The enum is the machine

An enum carrying its own transition map is self-documenting and exhaustively testable: all
sixty-four pairs asserted in milliseconds with no database. The alternative — transition
rules scattered across Actions — had already produced two call sites disagreeing about
whether suspended could return to verified. One map on the Entity ended the disagreement
permanently; adding a state now means updating exactly one object and its tests.

### 7.2 Safety at Scale

#### DD-USER-003 — Redundant protection on purpose

Observer, model, action, UI: four checks for the same invariant looks wasteful until the
day tinker bypasses three of them. Each layer assumes the others are absent, so each
refuses independently with its own test proving it. The cost is a few duplicated lines;
the payoff is that no single bypass — crafted request, console session, future bulk tool —
can retire the account the whole school depends on. Recovery stays a CLI operation, never
an emergency code change.

#### DD-USER-004 — Cohorts processed in hundred-row strides

Loading a graduating class into memory to loop it is the kind of code that passes every
test on staging and OOMs in production. Chunking at one hundred bounds the working set
regardless of cohort size, at the price of slightly more elaborate code than
`get()->each()`. The same stride governs CSV import, so both bulk paths share one proven
shape instead of two clever ones.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Creation time | Under 1s including notifications | `CreateUserAction` timing test |
| Update time | Under 500ms including profile upsert | `UpdateUserAction` timing test |
| Batch delete (100 users) | Under 10s with correct skip counts | `BatchDeleteUserAction` test |
| Illegal transitions rejected | 100% of the 64-pair matrix correct | Entity unit tests |
| Self and super-admin refusals | 100% refused at every layer | Layer-by-layer Feature tests |
| Status notifications | Every applied change notifies | `AccountStatusNotification` assertions |
| Listing queries | Flat count at 1000 rows | Query-count Feature test |
| Archive memory | Flat peak from 50 to 5000 rows | Chunked archive test |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [authentication](YB7RG-authentication.md) | Auth state and role-based access user CRUD stands on |

### Build Guide

User CRUD with status management is the identity foundation every people-referencing module
builds on. Administration flows onward into bulk onboarding and credential distribution.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [bulk import](O2KCR-csv-import-export.md) | Bulk user onboarding via CSV through `CreateUserAction` |
| 2 | [account slips](EWCZ0-account-slips.md) | Printable credential documents drawn from user data |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

- [Spec registry](index.md) — Enrollment phase; this spec is `95EVB` with status Full
- [Authentication](YB7RG-authentication.md) — auth state and role checks this spec assumes
- [Bulk import](O2KCR-csv-import-export.md) — tabular onboarding into these managers
- [Account slips](EWCZ0-account-slips.md) — credential documents for these accounts
- [Architecture](D2FT3-architecture.md) — Action Triad, Entity/Model split, DTO boundary
- [Project initialization](QLHDO-project-initialization.md) — global FR-GLB/NFR every row inherits
