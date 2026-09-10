# Profile Management — User Profile & Settings

> **Spec ID:** OCEMS
> **Status:** Full
> **Owner:** User
> **Depends on:** SE5Q9, T4B26, YB7RG, WQGTP

## Description

User profile management where authenticated users view and edit personal information,
upload or remove an avatar, change their password, and reach recovery codes. Role-aware form
fields expose staff-only data solely to staff roles, super admin identity stays immutable at
both UI and business layers, and every change is validated, authorized, logged, and announced
through domain events.

---

## 1. Problem Statements

### PS-1 — Users Need to Manage Their Profile

Users need one page to view and edit name, email, phone, address, bio, and avatar. Without
it, every correction becomes an admin support ticket, and the queue of trivial edits grows
exactly as fast as enrollment does.
**→ Requirement:** FR-PROF-001–007 (update Action), FR-PROF-011–017 (editor component).

### PS-2 — Role-Specific Profile Fields

Staff carry employment data — status, job title, ID number, competence field — that students
and supervisors do not. A one-size form either exposes irrelevant fields or forces separate
pages per role, doubling maintenance for a distinction the form shape can express.
**→ Requirement:** FR-PROF-008–010 (role-aware form shape), FR-PROF-017 (role-aware labels).

### PS-3 — Super Admin Identity Protection

The super admin's name and username are fixed by design, and the profile editor must enforce
that constraint in business logic — not merely by disabling inputs that a crafted request can
bypass.
**→ Requirement:** FR-PROF-005 (integrity rejection), FR-PROF-010 (immutable flags).

### PS-4 — Password Change From Profile

Users expect to change passwords where they manage their identity, with current-password
verification and throttling, rather than detouring through the reset flow while authenticated.
**→ Requirement:** FR-PROF-018–020 (password change path).

---

## 2. Goals & Non-Goals

### Goals

- **Validated profile updates with avatar support** — one Command Action persisting identity and profile data plus media handling. *Why:* a single transactional path keeps the two tables consistent.
- **Role-aware form shape** — a Read Action declaring common fields, staff fields, and immutability flags per caller. *Why:* the form adapts to roles without per-role pages.
- **Single editor component** — profile form, password change, and avatar management on one page. *Why:* users expect identity management at one destination.
- **Super admin integrity at the business layer** — name and username changes rejected regardless of UI state. *Why:* client-side disabling alone cannot survive a crafted request.
- **Avatar lifecycle on the media library** — upload with validation, instant preview, and removal. *Why:* photos need lifecycle rules, not just an upload button.
- **Logged changes with credential-change events** — audit entries plus notifications when identity credentials move. *Why:* email and username changes are security-relevant and must leave traces.

### Non-Goals

- **School profile management**. *Why:* owned by [school-profile](81SMS-school-profile.md); this spec covers people, not institutions.
- **Admin user CRUD and status transitions**. *Why:* owned by [user-crud-and-status](95EVB-user-crud-and-status.md); self-service editing is a different trust context.
- **Two-factor authentication setup**. *Why:* security extension beyond MVP core per the [mvp-spec-trim ADR](../adr/adr-mvp-spec-trim.md).
- **Profile visibility and privacy settings**. *Why:* role gating already bounds visibility; granular privacy controls are post-MVP.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled here
because each journey below has a code-verifiable consequence at this spec's scope.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-PROF-001 | Authenticated user edits profile fields with role-appropriate visibility and credential notifications | P0 | F | Full |
| UC-PROF-002 | Authenticated user uploads or removes an avatar with validation and instant preview | P0 | F | Full |
| UC-PROF-003 | Authenticated user changes password with current-password verification and throttling | P0 | F | Full |
| UC-PROF-004 | Authenticated user reaches recovery codes from the profile area | P2 | F | Full |

### 3.1 Identity Journeys

#### UC-PROF-001 — User Edits Profile

A teacher opens her profile, updates her phone number and bio, and saves. The editor had
loaded her record with its relations, asked the form-shape Action which fields her role may
see, and rendered staff fields alongside common ones while keeping nothing editable that her
role forbids. The update ran transactionally across both tables, and because her email moved,
a credential-change notification followed — the kind of trace that turns "who changed this"
from a mystery into a log query.

#### UC-PROF-002 — User Uploads or Removes Avatar

Choosing a photo starts with validation — image type, size ceiling — then stores the file in
the avatar collection, replacing any previous portrait, with the preview updating
immediately so the user sees the result before saving anything else. Removal clears the
collection outright. The lifecycle matters because a photo feature without removal rules
slowly accumulates stale images nobody owns.

### 3.2 Security Journeys

#### UC-PROF-003 — User Changes Password

Entering the current password plus a new confirmed one, the user triggers verification
against the stored hash first — a wrong current password stops everything — then throttling
bounds repeated attempts before the new hash persists and a notification confirms the
change. Keeping this flow on the profile page removes the absurdity of logging out to prove
you know your password.

#### UC-PROF-004 — User Views Recovery Codes

A sidebar link carries the user from profile to the recovery-code surface owned by the
account-recovery spec, where generation, viewing, and download live. The profile page links
rather than reimplements, so recovery logic keeps exactly one home and the profile keeps a
stable doorway to it.

---

## 4. Functional Requirements

A Functional Requirement is a verifiable behavior the system must support. `Priority` ranks
criticality on a P0–P3 scale. `Layer` declares the test layer (`U` Unit · `F` Feature ·
`B` Browser · `A` Arch). `Status` tracks implementation of the requirement itself.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-PROF-001 | The update Action validates all profile fields with explicit rules before persisting | P0 | F | Full |
| FR-PROF-002 | The update Action writes identity fields to the users table inside a transaction | P0 | F | Full |
| FR-PROF-003 | The update Action creates or updates the profiles row for profile-specific data | P0 | F | Full |
| FR-PROF-004 | The update Action stores a provided avatar in the media library avatar collection | P0 | F | Full |
| FR-PROF-005 | The update Action rejects super admin name and username changes with a business-rule exception | P0 | F | Full |
| FR-PROF-006 | The update Action dispatches the profile-updated event carrying previous credentials | P0 | F | Full |
| FR-PROF-007 | The update Action logs the profile update with masked personal data | P0 | F | Full |
| FR-PROF-008 | The form-shape Action returns the always-visible common field set | P0 | F | Full |
| FR-PROF-009 | The form-shape Action returns staff fields only for staff roles | P0 | F | Full |
| FR-PROF-010 | The form-shape Action returns name and username mutability flags, both false for the super admin | P0 | F | Full |
| FR-PROF-011 | The editor loads the user with profile and role relations on mount | P0 | F | Full |
| FR-PROF-012 | The editor delegates form population to the form-shape Action | P1 | F | Full |
| FR-PROF-013 | The editor authorizes through the user policy, covering the nullable-profile creation path | P0 | F | Full |
| FR-PROF-014 | The editor validates avatar uploads on type and size with accessible trigger, save handling, feedback, and loading state | P0 | F | Full |
| FR-PROF-015 | The editor supports avatar removal by clearing the avatar collection | P1 | F | Full |
| FR-PROF-016 | The editor provides an instant preview URL for pending avatar uploads | P2 | F | Full |
| FR-PROF-017 | The editor labels the ID number field per role, student versus staff terminology | P1 | F | Full |
| FR-PROF-018 | Password change verifies the current password against the stored hash | P0 | F | Full |
| FR-PROF-019 | Password change throttles attempts per user and address | P0 | F | Full |
| FR-PROF-020 | Successful password change persists the new hash and dispatches the credential notification | P0 | F | Full |

### 4.1 Profile Update Action

#### FR-PROF-001 — Explicit validation before persistence

Every field passes declared rules before anything touches the database, so a malformed phone
number or oversized bio fails fast with messages rather than half-persisting. Centralizing
the rules in the Action's data contract gives tests one surface to attack with invalid
payloads.

#### FR-PROF-002 — Identity writes inside a transaction

Name, email, and username updates on the users table run transactionally, because identity
fields are read by authentication on every request and a partial write there is a lockout
waiting to happen. Either the whole identity change lands or none of it does.

#### FR-PROF-003 — Profile row created or updated

Profile-specific data lands through create-or-update keyed on the user, which gracefully
covers both the newcomer with no profile row and the veteran editing hers. The nullable
profile is a normal state in this design, not a missing migration.

#### FR-PROF-004 — Avatar stored in its collection

A provided avatar file goes to the dedicated media collection, replacing any predecessor so
each user owns at most one portrait. Collection-scoped storage keeps avatars queryable and
deletable independently of document attachments elsewhere in the product.

#### FR-PROF-005 — Super admin identity rejected as a business rule

When the target is the super admin singleton, any name or username change throws a
translatable business-rule exception no matter what the request claims. This is the load-
bearing wall: UI disabling is courtesy, but the Action's rejection is the guarantee, and it
holds against direct Livewire calls that never render the form.

#### FR-PROF-006 — Profile-updated event with previous credentials

After commit the Action dispatches the domain event carrying the profile plus the previous
email and username, letting listeners detect exactly which credential moved. Downstream
notifications and audit entries subscribe to this event rather than re-deriving diffs, so
the change story stays consistent everywhere.

#### FR-PROF-007 — Logged update with masked data

The update writes an activity entry with personal data masked, preserving "who changed
what, when" without turning the log into a phone book. Masking at write time — not at
display time — means log exports and backups inherit the protection automatically.

### 4.2 Profile Form Shape Action

#### FR-PROF-008 — Common fields for every role

Name, email, phone, address, and bio form the universal set every authenticated user may see
and edit. Fixing this list in a Read Action rather than in Blade keeps the form's contract
testable: a test can assert the shape for a student without rendering a pixel.

#### FR-PROF-009 — Staff fields gated by role

Employment status, job title, ID number, and competence field appear only for staff roles,
so students and supervisors never see — or submit — data outside their domain. Gating the
shape server-side matters because hidden-but-submittable fields are a classic
mass-assignment-adjacent trap.

#### FR-PROF-010 — Mutability flags with super admin false

The shape carries two booleans governing whether name and username may change, both false
for the super admin. Flags rather than role-sniffing in Blade keep the template dumb and
the rule in one testable place, and the super admin case stays visibly special instead of
implicitly handled.

### 4.3 Profile Editor Component

#### FR-PROF-011 — Relations loaded on mount

Mounting with profile and role relations eager-loaded prevents the slow drip of lazy queries
as the form renders field after field. It also guarantees the component works from a
complete snapshot rather than re-querying mid-render.

#### FR-PROF-012 — Population delegated to the shape Action

The component asks the form-shape Action what to show instead of deciding itself, which
keeps presentation and policy cleanly separated. When the staff-field rule changes, one
Action changes — not every template branch that consumed it.

#### FR-PROF-013 — User-policy authorization covering creation

Authorization runs through the user policy — self or admin — precisely because the profile
row may not exist yet and a policy bound to a nullable model cannot decide. Choosing the
user as the authorization target closes the creation-path hole where no profile instance
exists to authorize against.

#### FR-PROF-014 — Validated upload with accessible trigger and feedback

Avatar selection validates type and size immediately, announces through an accessible
trigger rather than a hidden click proxy, saves through the shared handler, confirms with
feedback, and shows loading state during transfer. Each clause exists because uploads fail
in public: oversized files, screen-reader-invisible buttons, and silent transfers that
leave users clicking twice.

#### FR-PROF-015 — Removal clears the collection

Removing the avatar empties the media collection rather than flagging a boolean, so no
orphaned file lingers in storage and the fallback rendering resumes deterministically. A
collection clear is also idempotent — removing twice is harmless, which removal buttons
need to be.

#### FR-PROF-016 — Instant preview for pending uploads

Before anything persists, the component exposes a temporary preview URL for the selected
file. The preview turns an anxious "did it take my photo" moment into immediate visual
confirmation, and it costs nothing because the upload plumbing already stages the file.

#### FR-PROF-017 — Role-aware ID terminology

Students see their national student number label while staff see the employment number
label, because the same field means different things across roles. Terminology that matches
the user's world reduces misfiled numbers more effectively than any placeholder text.

### 4.4 Password Change

#### FR-PROF-018 — Current password verified first

The stored hash check runs before anything else, and a mismatch stops the flow with a
validation message. This ordering turns a stolen session with an unlocked laptop into a
read-only problem rather than a full account takeover.

#### FR-PROF-019 — Throttled attempts per user and address

Repeated password-change attempts are bounded per user-and-address window, which blunts
both guessing and automated abuse without punishing legitimate typos. Throttling at this
layer — not just at login — matters because an authenticated session is exactly where
rate limits are most often forgotten.

#### FR-PROF-020 — New hash persisted with notification

On success the new hash replaces the old and a credential notification goes out, closing
the loop for the user who changed it and alerting the account holder if someone else did.
Persistence without notification would leave half the security story untold.

---

## 5. Non-Functional Requirements

Profile constraints with measurable targets. `Target` holds the concrete SLO; `N/A` means
architectural enforcement verified via scans or tests.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-PROF-001 | Profile changes are logged with personal-data masking | 0 unmasked PII | P0 | F | Full |
| NFR-PROF-002 | The profile-updated event carries previous credentials for change detection | 100% carry | P0 | F | Full |
| NFR-PROF-003 | Super admin identity changes are rejected at both UI and business layers | 0 successes | P0 | F | Full |
| NFR-PROF-004 | Profile classes declare strict typing | 100% files | P0 | A | Full |
| NFR-PROF-005 | All profile strings resolve through the translation helper in both locales | 0 missing keys | P0 | A | Full |

### 5.1 Trust and Integrity

#### NFR-PROF-001 — Masked audit trail

Every profile mutation leaves an activity entry with personal data masked at write time. A
test asserting masked output on a governed mutation guards this permanently, because
unmasked logs are the breach that happens without any attacker at all.

#### NFR-PROF-002 — Credential diff travels with the event

Carrying previous email and username on the event means every listener — mail, audit,
security review — detects credential movement identically. Recomputing diffs per listener
would eventually disagree, and disagreements in security signals are how real changes go
unnoticed.

#### NFR-PROF-003 — Dual-layer super admin protection

Disabled inputs in the UI plus rejection in the Action give two independent checkpoints,
verified by attempting the change through both paths. Either layer alone invites its own
bypass — cosmetic disabling falls to crafted requests, server rules fall to confused users
— so both hold the line together.

### 5.2 Code and Language Discipline

#### NFR-PROF-004 — Strict typing throughout

Profile classes declare strict types without exception, keeping coercion surprises out of
identity data where a silently cast value could corrupt a lookup. The convention scan
asserts this structurally across the feature.

#### NFR-PROF-005 — Dual-language profile strings

Labels, validation messages, toasts, and notifications on the profile surface all resolve
through the translation helper with keys present in English and Indonesian. Profile pages
are visited by every role in both languages, so a monolingual validation message here
fails the widest possible audience.

---

## 6. API / Data Contracts

### Actions

```php
final class UpdateProfileAction extends BaseCommandAction
{
    public function execute(UpdateProfileData $data): Profile;
    // Validates via UpdateProfileData + super-admin integrity rules.
    // Updates users fields transactionally; create-or-update on profiles.
    // Stores avatar in the media library avatar collection.
    // Dispatches ProfileUpdated after commit; logs with masked data.
}

final readonly class UpdateProfileData extends BaseData
{
    public function __construct(
        public string $userId,
        public array $profile,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $username = null,
        public ?UploadedFile $avatar = null,
    ) {}
}

final class ReadProfileFormAction extends BaseReadAction
{
    public function execute(User $user): array;
    // Returns: fields, staffFields, canChangeName, canChangeUsername, role
}
```

### Livewire Component

```php
class ProfileEditor extends BaseFormView
{
    public ProfileForm $profileForm;
    public PasswordForm $passwordForm;
    public $avatar = null; // untyped for Livewire hydration
    public User $user;

    public function mount(): void;
    public function save(UpdateProfileAction $action): void;
    public function updatedAvatar(): void;
    public function confirmRemoveAvatar(): void;
    public function updatePassword(UpdateUserPasswordAction $action): void;
    public function avatarPreviewUrl(): ?string;
    public function getIdNumberLabel(): string;
}
```

### Models

```php
class Profile extends BaseModel
{
    // UUID primary key; belongsTo User, Department, Company.
    // Fields: phone, address, bio, gender, blood_type, pob, dob,
    // emergency_contact (JSON), id_number, national_id_number,
    // competence_field, employment_status, job_title, internal_notes,
    // department_id, company_id
}
```

### Events & Listeners

```php
class ProfileUpdated extends BaseEvent
{
    public function __construct(
        public Profile $profile,
        public ?string $previousEmail,
        public ?string $previousUsername,
    );
    public function eventName(): string; // 'profile.updated'
}

class SendProfileChangedMail implements ShouldQueue
{
    // Sends CredentialChangedNotification when email or username changes.
}
```

### Policy

```php
class ProfilePolicy extends BasePolicy
{
    // viewAny: admins only; view/update: admin or owner.
}

class UserPolicy extends BasePolicy
{
    // update: self or admin; used by the editor for the nullable-profile path.
}
```

### Route

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET /profile` | `ProfileEditor` (Livewire) | `auth` |

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—` unless
a decision has a code-testable consequence.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-PROF-001 | Profile editing, password change, and avatar management share one page rather than separate routes | P1 | — | — |
| DD-PROF-002 | Super admin protection is enforced in business logic, with UI disabling as visual feedback only | P0 | — | — |
| DD-PROF-003 | Profile data lives in a dedicated table in one-to-one relation with users rather than as user columns | P0 | — | — |

### 7.1 Page and Protection Shape

#### DD-PROF-001 — One page for all profile actions

Users think of "my profile" as a place, not three workflows, so editing, password change,
and avatar management share one destination with separate component methods. The component
grows larger than a purist would like, but the business logic still lives in dedicated
Actions — the page is a shared hallway, not a god object.

#### DD-PROF-002 — Business layer as the real guard

Interface disabling tells honest users what to expect; only the Action's rejection stops a
determined request. Placing the authoritative rule beside the data it protects — rather than
in the template that most users see — is what makes the super admin singleton actually
immutable instead of merely appearing so.

### 7.2 Data Shape

#### DD-PROF-003 — Separate profile table over wide users

Auth-critical columns stay lean on the users table for fast authentication queries while
optional profile data lives beside them in a one-to-one row that may simply not exist yet.
The split keeps login queries narrow and makes "incomplete profile" a representable state
rather than a row full of nulls.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|----------------|
| Profile update submit to confirmation | < 2s | Timed feature journey |
| Avatar upload for a 2MB image | < 5s | Timed upload journey |
| Super admin name or username changes succeeding | 0 | Dual-layer rejection tests |
| Credential-change notification on email move | 100% sent | Event listener test |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [base-classes](SE5Q9-base-classes.md) | Command and read bases, action responses, business-rule exceptions |
| [authentication](YB7RG-authentication.md) | User model and auth infrastructure |
| [rbac-and-authorization](T4B26-rbac-and-authorization.md) | Profile policy and role-based field visibility |
| [file-uploads-media](WQGTP-file-uploads-media.md) | Media library backing avatar uploads |

### Build Guide

Implement the form-shape and update Actions first, then the editor component. The profile
model and table arrive with earlier migration work, and avatar handling uses the media
library's avatar collection. Password change delegates to the shared password action with
its throttling intact.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [user-crud-and-status](95EVB-user-crud-and-status.md) | Admin user management builds on profile infrastructure |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — Action Triad and boundary objects behind profile flows
- [Authentication](YB7RG-authentication.md) — user model and auth infrastructure
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — policy contracts and the proxy model
- [File uploads & media](WQGTP-file-uploads-media.md) — media library backing avatars
- [Account recovery slips](SHQ1J-account-recovery-slips.md) — recovery-code surface linked from profile
- [Base-class mandate ADR](../adr/adr-base-class-mandate.md) — why editor tables and Actions extend their bases
