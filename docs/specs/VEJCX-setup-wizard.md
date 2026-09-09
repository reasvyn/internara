# Setup Wizard — Feature Specification

> **Spec ID:** VEJCX
> **Status:** Full
> **Owner:** Setup
> **Depends on:** 8NZAU

## Description

Defines the browser-based setup wizard that turns a provisioned instance into a working school: six guided steps from environment welcome through super admin creation, school profile, first department, and atomic finalization. CLI provisioning that precedes it belongs to [8NZAU-installation.md](8NZAU-installation.md); the recovery key born here is operated in [C9ZB6-recovery-ecosystem.md](C9ZB6-recovery-ecosystem.md).

---

## 1. Problem Statements

### PS-1 — Access Control During Setup

The wizard creates the super admin account and writes sensitive configuration. Untrusted parties must not reach it, guess its URL, or replay expired sessions.
**→ Requirement:** FR-WIZ-016 (token gate), FR-WIZ-017 (versioned session), FR-WIZ-018 (post-finalization lockout).

### PS-2 — Recovery Key Handoff

Finalization mints the recovery key exactly once. The installer must see it, copy it, and store it elsewhere — a key displayed but not saved is a lockout scheduled for next semester.
**→ Requirement:** FR-WIZ-007 (key display with copy), FR-WIZ-013 (dual persistence).

### PS-3 — Guided Configuration

School IT staff may not be technical. The wizard must walk them through super admin creation, school profile, and first department with plain instructions and validation at every step.
**→ Requirement:** FR-WIZ-001/002 (step structure and audit gate), FR-WIZ-003/004/005 (validated forms).

### PS-4 — Auto-Redirect for Uninstalled Systems

Any visitor to an uninstalled instance must land on the wizard, not on a broken or empty application.
**→ Requirement:** FR-WIZ-016 (global redirect to `/setup`).

---

## 2. Goals & Non-Goals

### Goals

- **Six steps, five minutes** — welcome to complete without a manual. *Why:* setup day competes with every other first-week task; a wizard that needs its own training has failed.
- **Token-gated access end to end** — no anonymous path to account creation. *Why:* the wizard manufactures the most powerful account in the system.
- **Uninstalled means redirected** — every route leads to `/setup` until finalization. *Why:* a half-born system must never look finished.
- **Bilingual throughout** — every string in Indonesian and English. *Why:* the person provisioning and the person filling forms often read different languages.
- **Idempotent finalization** — finishing twice changes nothing. *Why:* double-clicks and impatient refreshes are part of every real setup day.
- **Recovery key copied, not glanced at** — one-click copy at finalization. *Why:* transcription errors in a 64-character secret are a support ticket with no self-service fix.

### Non-Goals

- **CLI wizard**. *Why:* the terminal path is owned by [8NZAU-installation.md](8NZAU-installation.md).
- **Progress persistence across browsers**. *Why:* session-scoped state is enough; an expired session restarts cheaply from step one with no orphaned rows.
- **Import or export of setup configuration**. *Why:* one school, one setup, no fleet to clone at MVP.
- **Custom theme or branding during setup**. *Why:* branding is post-setup configuration; see [52O1I-branding-theme-locale.md](52O1I-branding-theme-locale.md).
- **One-time-password verification during recovery**. *Why:* shared hosting frequently lacks SMTP; deferred post-MVP (see §10 R-1).

---

## 3. User Stories / Use Cases

One table holds every use case; the groups below (§3.1–§3.2) carry the free-form detail for each row.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-WIZ-001 | Installer completes the six-step browser wizard from welcome to recovery key | P0 | B | Full |
| UC-WIZ-002 | Any visitor to an uninstalled instance is redirected to the setup entry | P0 | F | Full |
| UC-WIZ-003 | Installer copies the recovery key inside the post-finalization window, then setup locks | P0 | F | Full |
| UC-WIZ-004 | Installer navigates back to completed steps with all entered data preserved | P1 | B | Full |

### 3.1 The Wizard Journey

#### UC-WIZ-001 — Walk the Six Steps

The vice principal opens the signed URL from WhatsApp and meets a welcome screen showing the environment audit — green across the board, because the technician already ran it. Super admin step: name and username locked to their immutable values, email and a strong password filled in. School step: name, NPSN code, contact email, and the optional address and principal fields. Department step: the first department, named in full. Finalize step: a summary of everything entered and two checkboxes — data verified, security implications understood — without which the finish button stays asleep. Then the completion screen with the recovery key, a copy button, and a slow twenty-second countdown to the login page. Five minutes, no manual, one working school.

#### UC-WIZ-004 — Go Back Without Losing Work

Halfway through the school step, the installer realizes the admin email has a typo — it was entered two screens ago. The step indicator lets them walk back to the account step, where every field is exactly as left, fix the address, and walk forward again without retyping the school data. Form state lives in the session across navigation, so backward movement is review, not rework. Only completed steps allow return; the wizard never lets anyone skip ahead into a step whose prerequisites are unmet.

### 3.2 Entry and Exit

#### UC-WIZ-002 — Every Road Leads to Setup

A curious teacher types the dashboard URL into a browser before setup day. Instead of an error page or, worse, a functioning-looking shell with no data, the middleware sees the uninstalled flag and delivers them to the setup entry — the token prompt, not the wizard itself, because curiosity is not authorization. This holds for every route on an uninstalled system, which is what makes it a guarantee rather than a suggestion: there is no URL that shows a half-born school.

#### UC-WIZ-003 — Copy the Key, Then the Door Closes

Finalization lands the installer on the completion screen with the recovery key displayed and thirty seconds of guaranteed access to copy it — long enough to click copy, paste into the school's password vault, and breathe. When the window closes, the session's setup state purges and the route answers 404 permanently. An installer who lingered too long finds no key on screen but loses nothing: the key already rests hashed in settings and in plaintext in its private file, retrievable through the recovery commands.

---

## 4. Functional Requirements

One table holds every functional requirement; each row's detail lives under its group (§4.1–§4.3).

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-WIZ-001 | Wizard presents exactly six ordered steps: welcome, account, school, department, finalize, complete | P0 | B | Full |
| FR-WIZ-002 | Welcome step shows the environment audit and holds Start until every gate passes | P0 | B | Full |
| FR-WIZ-003 | Account step locks super admin name and username and validates email plus a strong password | P0 | F | Full |
| FR-WIZ-004 | School step requires name, NPSN code, and contact email with URL validation on the website | P0 | F | Full |
| FR-WIZ-005 | Department step requires the first department name with optional description | P0 | F | Full |
| FR-WIZ-006 | Finalize step requires both confirmation checkboxes and shows an entered-data summary | P0 | B | Full |
| FR-WIZ-007 | Complete step shows the recovery key with one-click copy and a timed redirect to login | P0 | B | Full |
| FR-WIZ-008 | Form data persists in session and backward navigation reaches completed steps intact | P1 | B | Full |
| FR-WIZ-009 | Finalization is atomic across school, department, admin, settings, and key material | P0 | F | Full |
| FR-WIZ-010 | Finalization writes the school profile and derives brand and site title from the school name | P0 | F | Full |
| FR-WIZ-011 | Finalization creates the first department row | P0 | F | Full |
| FR-WIZ-012 | Finalization creates the PROTECTED, email-verified super admin and clears its setup flag | P0 | F | Full |
| FR-WIZ-013 | Finalization mints the 64-character recovery key, hashed in settings and plaintext in its file | P0 | F | Full |
| FR-WIZ-014 | Finalization sets the installed flag, fires the event, notifies, clears caches and session state | P0 | F | Full |
| FR-WIZ-015 | Re-running finalization on an installed system throws `RejectedException` with no side effects | P0 | F | Full |
| FR-WIZ-016 | Global middleware redirects uninstalled traffic to `/setup` and token-gates setup routes | P0 | F | Full |
| FR-WIZ-017 | Validated tokens establish a versioned session authorization with a regenerated session ID | P0 | F | Full |
| FR-WIZ-018 | Setup stays reachable for 30 seconds after finalization, then purges state and answers 404 | P0 | F | Full |
| FR-WIZ-019 | Installed systems answer 404 on setup routes while assets and Livewire requests pass through | P0 | F | Full |

### 4.1 Steps and Validation

#### FR-WIZ-001 — Six Steps, Fixed Order

The step keys — welcome, account, school, department, finalize, complete — are configuration, but their order is contract: identity before institution, institution before department, everything before the summary that commits it. Fixed order is what makes the finalize summary trustworthy; if steps could shuffle, the summary would be reviewing a sequence nobody followed. Six is also a completeness claim — anything the school needs on day one fits in these screens, and anything else waits for post-setup settings.

#### FR-WIZ-002 — The Audit Gate

A school technician once clicked through a warning-colored audit, created the admin, and discovered mid-semester that the missing extension broke certificate PDFs. The welcome step exists so that story cannot repeat: full audit results render before anything else, the Start control stays disabled while any gate fails, and a Recheck button re-runs the audit after fixes. Entering the wizard with a red audit is simply not a path the interface offers.

#### FR-WIZ-003 — The Immutable Account

Name and username arrive locked from configuration — `Super Admin`, `superadmin` — because every policy, recovery command, and audit query in the system addresses that fixed point. What the installer does provide is the email, validated as an address, and a password held to the full Laravel strength rules with confirmation. Locking identity while validating contact cleanly separates what the system owns from what the human supplies.

#### FR-WIZ-004 — School Profile With a Required Email

Name, NPSN institutional code, and contact email are required; address, phone, website, and principal name are optional, with the website checked as a URL when given. The email deserves its deliberate strictness: the wizard is a one-time provisioning act, and requiring a contact address guarantees every installed instance can receive its welcome notification and future mail. Post-setup editing may later relax that field (see DD-WIZ-005) — provisioning strictness and operational flexibility are different contexts, not a contradiction.

#### FR-WIZ-005 — The First Department

Every school needs at least one department before anything academic can happen — placements hang from departments, teachers belong to them — so the wizard refuses to finalize an institution with nowhere to put students. One name, one optional description, real validation. Later departments are ordinary admin work; this first one is structural, which is why it holds a step of its own instead of hiding in an advanced section nobody opens.

#### FR-WIZ-006 — The Deliberate Finish

The finalize screen shows everything entered, then demands two explicit acknowledgments: the data was verified, and the installer understands the security implications of what they are creating. The finish control sleeps until both boxes are checked, because a summary nobody confirmed is decoration. This is the last moment where going back is cheap — after this click, creation is atomic and the system is a school.

#### FR-WIZ-007 — Key Display and Countdown

Completion shows the fresh recovery key beside a one-click copy control and the account summary, then counts down twenty seconds toward the login page. The countdown is theater with a purpose: it paces the installer to copy now rather than screenshot-later, while guaranteeing the screen does not linger as a standing secret. Everything on this screen is designed around a single human behavior — save the key somewhere that is not this browser.

#### FR-WIZ-008 — Session Memory

Step forms persist in the session, so navigation never destroys work and a dropped connection costs minutes, not the whole setup. Backward travel reaches completed steps with values intact; forward travel follows only the validated path. Notably, there is no forward jump into uncompleted steps — the wizard is a sequence, not a menu, and the session is what enforces that shape without writing a single partial row to the database.

### 4.2 Finalization

#### FR-WIZ-009 — All or Nothing

Finalization writes the school, the department, the admin, the settings, and the key material inside one transaction, because every partial outcome is a distinct disaster: an admin with no school, a school with no admin, an installed flag with nothing behind it. The atomicity requirement is the reason setup-day failures are boring — the system either fully works or remains honestly uninstalled, and the installer simply retries. Partial setup is not a state this system has.

#### FR-WIZ-010 — Profile and Brand From One Name

The school name the installer typed becomes three things: the `school.*` settings rows, and the derived `brand_name` and `site_title` that the layout reads from the first render. Deriving brand from the authoritative name avoids the classic fresh-install look where the header says a template string while the profile says the real school. One source, three consumers, zero drift from the first page load.

#### FR-WIZ-011 — Department Row

The first department materializes as a real database row in the same transaction — not a setting, not a promise, a row other modules can immediately reference. Its simplicity is the point: name in, record out, ready for teachers and placements within the same request cycle that created the school around it.

#### FR-WIZ-012 — Birth of the Super Admin

The super admin arrives with role, PROTECTED status, verified email, and its setup-required flag already cleared — fully formed, never passing through a half-provisioned state where it exists but cannot act. PROTECTED from birth means no later misclick can delete or lock the account the whole system leans on. Clearing the setup flag in the same write is what stops the account from being treated as provisional a moment longer than necessary.

#### FR-WIZ-013 — Minting the Last Resort

Finalization generates the 64-character recovery key, stores its bcrypt hash in settings, and writes the plaintext to the private file at owner-only permissions. The hash enables verification without ever persisting the secret where dumps can find it; the file enables retrieval without a working application. If the file write fails, finalization still completes and warns — a missing file is recoverable, a missing admin is not, and the ordering of those priorities is deliberate.

#### FR-WIZ-014 — Closing Ceremony

Installed flag set, `SetupFinalized` event dispatched, welcome notification queued to the new admin, caches cleared so the first real request sees the finished system, setup session state purged. Each item has bitten someone when forgotten: the flag left false re-triggers the redirect loop, the uncleared cache serves setup pages to a finished school, the missing event starves listeners that warm post-install state. The ceremony is a checklist because checklists are what prevent exactly these omissions.

#### FR-WIZ-015 — Finishing Twice Changes Nothing

Double-clicks, refresh storms, and retried requests all converge on finalization, so re-running it against an installed system throws `RejectedException` before touching anything — no second admin, no duplicate department, no rotated key. Idempotency here is not politeness; it is the property that lets the completion screen be safely reloaded while the installer hunts for the copy button. The guard reads the installed flag first and refuses before the transaction even opens.

### 4.3 Access Control

#### FR-WIZ-016 — Redirect Everything, Gate the Gate

One middleware watches all traffic on uninstalled systems and delivers it to the setup entry; a second validates the token on the setup routes themselves. The pairing matters: the first guarantees no visitor ever sees a half-born system, the second guarantees no visitor reaches account creation without the secret. Together they make setup both unavoidable and inaccessible — unavoidable to stumble past, inaccessible to enter uninvited.

#### FR-WIZ-017 — From Token to Session

A validated token is exchanged for session authorization carrying the token version, and the session identifier regenerates at that instant against fixation. After the exchange the wizard reads the session, never the token — which is precisely why the token can be single-use without breaking a six-step flow. The version binding travels silently: rotate the token from the CLI and every session minted from its predecessor stops authorizing.

#### FR-WIZ-018 — Thirty Seconds of Grace

After finalization the setup route survives exactly one short window — thirty seconds, configurable — so the completion screen stays readable while the key is copied. Then the session's setup keys purge and the route answers 404. Thirty seconds is a human-factoring choice: enough for copy-paste into a vault, short enough that a forgotten tab is not a standing credential. The configurability exists for schools whose vault ritual runs slower.

#### FR-WIZ-019 — Locked Doors, Open Windows

On installed systems every setup access without a live authorized session is a 404, full stop — there is no setup page to discover on a working school. Meanwhile real files under `public/` stream through untouched and Livewire update requests pass the middleware without redirect, because a gate that breaks styling or interactivity trains users to disable gates. The strictness is aimed at people; the leniency is aimed at the framework's own traffic.

---

## 5. Non-Functional Requirements

One table holds every non-functional constraint; `Target` carries the concrete number or SLO, or `N/A` where enforcement is architectural.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-WIZ-001 | Token secrecy, single-use, throttling, and session discipline hold end to end per installation | per 8NZAU §5.1 | P0 | F | Full |
| NFR-WIZ-002 | Super admin password meets strength rules and the account is PROTECTED from birth | 8+ chars, mixed case, numbers | P0 | F | Full |
| NFR-WIZ-003 | Finalization logs through SmartLogger with PII masked before any sink | 100% of finalizations | P0 | F | Full |
| NFR-WIZ-004 | Finalization rolls back wholly on failure; key-file failure warns without blocking | 0 partial installs | P0 | F | Full |
| NFR-WIZ-005 | Wizard guides with progress, navigation, persistence, copy control, and countdown | N/A | P1 | B | Full |
| NFR-WIZ-006 | Every wizard string is translatable with mirrored locale keys | en + id, 100% via `__()` | P0 | F | Full |
| NFR-WIZ-007 | Accessible baseline: labels, keyboard paths, non-color status, announced copy control | N/A | P1 | B | Full |
| NFR-WIZ-008 | Wizard state lives in session and settings; no wizard-specific migration exists | 0 wizard migrations | P1 | A | Full |
| NFR-WIZ-009 | Wizard code follows the Action Triad with pure entities, covered by Pest | 0 triad violations | P0 | A | Full |
| NFR-WIZ-010 | A prepared installer walks all six steps quickly | < 5 min | P2 | B | Full |

### 5.1 Security and Audit

#### NFR-WIZ-001 — Token Guarantees Inherited

The wizard mints no tokens and weakens none: randomness, encryption at rest, single-use clearing, versioned sessions, throttled validation, and regenerated session identifiers all hold exactly as specified in [8NZAU-installation.md](8NZAU-installation.md) §5.1. Stating it here as inheritance rather than restating the numbers is deliberate — two copies of a secret's parameters will eventually disagree, and the installation spec is the authority both files point to.

#### NFR-WIZ-002 — First Account Strength

The password rules apply at the form and at the action, because client-side checks are courtesy and server-side checks are law. PROTECTED status from the first write means the account the school depends on cannot be deleted or locked by any later workflow, including its own profile page. Strength plus immutability is the whole hardening story for an account created before any security policy exists to govern it.

#### NFR-WIZ-003 — Masked Finalization Trail

Finalization handles the densest PII of the system's life — admin email, school contact details, the recovery secret — so its SmartLogger entries mask before either channel sees them, per the [SmartLogger ADR](../adr/adr-smartlogger-dual-channel.md). An auditor reviewing the install trail sees that an admin was created and a key was minted, never the values themselves. The activity entry is the proof; the masking is what makes the proof safe to keep for a year.

#### NFR-WIZ-004 — Failure Without Debris

A failed finalization leaves the system uninstalled and unmarked — zero partial rows, zero half-written settings — because the transaction boundary from FR-WIZ-009 is also this row's enforcement. The single exception is the recovery-key file: if its write fails after the database commits, the wizard warns and continues, showing the key on screen for manual safekeeping. Ordering mercy above symmetry here reflects a simple ranking — a found key beats a perfect transaction.

### 5.2 Experience

#### NFR-WIZ-005 — Guidance You Can Feel

Progress indicators, backward and forward movement, form memory, audit icons, a copy button that works, a countdown that paces — none of these is individually remarkable, and together they are the difference between a five-minute setup and a support call. The requirement is deliberately holistic: judge the wizard by watching a non-technical installer use it, not by ticking widgets. Anything that confuses that observer is a defect against this row.

#### NFR-WIZ-006 — Two Languages, No Exceptions

Indonesian primary, English secondary, every string through the helper, every key mirrored in both locale files — enforced by the D3 scan, not by reviewer memory. The wizard is many users' first contact with the system, and a hardcoded English sentence on the account step tells an Indonesian technician exactly how much the system was built for them. Setup day carries enough anxiety without a language barrier.

#### NFR-WIZ-007 — Accessible Baseline Without Ceremony

Every input carries a real label, every step is reachable and operable by keyboard, audit status never depends on color alone, and the copy control announces itself to assistive technology. This is the MVP baseline — contrast minimums and keyboard paths — explicitly short of a formal external audit, which waits post-MVP per the spec-zero non-goals. Baseline accessibility is a build habit; the audit is an event. Only the habit ships now.

#### NFR-WIZ-010 — Five Minutes, End to End

A prepared installer — token in hand, school facts ready — reaches completion in under five minutes. The budget disciplines the design: no step may demand research, no validation may require a phone call, no screen may present a decision the installer cannot make on the spot. When a step threatens the budget, the step is wrong, not the installer.

### 5.3 Structure and Verification

#### NFR-WIZ-008 — No Wizard Tables

Transient wizard state lives in the session; durable outcomes live in settings and domain tables. Zero wizard-specific migrations exist because a table for a six-screen flow used once per school would be schema clutter with permanent maintenance. The day the wizard needs persistence it does not have, that day justifies a migration — until then, absence is the design.

#### NFR-WIZ-009 — Triad Shape, Tested

Finalization and its supporting writes travel through command actions with validated inputs, entities stay pure and readonly, and the Pest suite covers the journey including the double-submit case. The wizard's Livewire layer validates for humans and delegates for truth — no model writes in components, no business rules in Blade. Reviewers check this structurally; the scans prove it continuously.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 Settings Keys

School profile stored with `group = 'school'`:

> **Email rule divergence (resolved in DD-WIZ-005):** the setup wizard requires `school.email`
> (FR-WIZ-004, SchoolForm `required|email`) so an installed system always has a working contact
> address. The post-setup editor ([81SMS-school-profile.md](81SMS-school-profile.md) §6.3) allows `nullable`
> so an admin may clear the email later. DD-WIZ-005 records this as intentional, not a contradiction.

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `school.name` | string | yes | Institution name |
| `school.institutional_code` | string | yes | NPSN code |
| `school.email` | string | yes | Contact email |
| `school.address` | string | no | Physical address |
| `school.phone` | string | no | Contact phone |
| `school.website` | string | no | Institution website |
| `school.principal_name` | string | no | Principal name |

### 6.2 Setup Wizard Form Contracts

```php
// SuperAdminForm — name/username locked from config
class SuperAdminForm extends LivewireForm
{
    public string $name = '';           // Locked, from config
    public string $username = '';       // Locked, from config
    public string $email = '';          // Required, email validation
    public string $password = '';       // Required, Password::min(8)->mixedCase()->numbers()
    public string $password_confirmation = '';
}

// SchoolForm
class SchoolForm extends LivewireForm
{
    public string $name = '';           // Required, max:255
    public string $institutional_code = ''; // Required, max:50
    public string $address = '';        // Nullable
    public string $email = '';          // Required, email
    public string $phone = '';          // Nullable, max:20
    public ?string $website = null;     // Nullable, URL validation
    public ?string $principal_name = null; // Nullable, max:255
}

// DepartmentForm
class DepartmentForm extends LivewireForm
{
    public string $name = '';           // Required, max:255
    public string $description = '';    // Nullable
}
```

### 6.3 Action Contracts

```php
// FinalizeSetupAction
class FinalizeSetupAction extends BaseCommandAction
{
    public function execute(
        array $schoolData,
        array $departmentData,
        array $adminData,
        array $stepsToComplete = ['account', 'school', 'department'],
    ): string; // Returns plaintext recovery key
    // @throws RejectedException when already installed
}

// SetupSchoolAction
class SetupSchoolAction extends BaseCommandAction
{
    public function execute(array $data): void;
}

// SetupDepartmentAction
class SetupDepartmentAction extends BaseCommandAction
{
    public function execute(array $data): Department;
}
```

### 6.4 Routes

| Method | URI | Handler | Name | Middleware |
|--------|-----|---------|------|------------|
| GET | `/setup` | `SetupWizard` (Livewire) | `setup` | `setup.protected` |
| POST | `/setup` | `SetupController@redirect` | — | `setup.protected` |
| POST | `/setup/cleanup` | `SetupController@cleanup` | `setup.cleanup` | `setup.protected` |

### 6.5 Config

```php
// config/setup.php (wizard-specific)
[
    'wizard' => [
        'step_keys' => ['welcome', 'account', 'school', 'department', 'finalize', 'complete'],
        'finalize_steps' => ['account', 'school', 'department'],
    ],
    'security' => [
        'finalization_window_seconds' => 30,
    ],
]
```

---

## 7. Design Decisions

One table holds every design decision; detail prose below states each decision with its history and accepted cost.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-WIZ-001 | Wizard progress lives in session state, never in partial database rows | P0 | — | — |
| DD-WIZ-002 | Setup redirect is enforced by global middleware, not per-route guards | P0 | — | — |
| DD-WIZ-003 | Finalization commits school, department, admin, and settings atomically | P0 | — | — |
| DD-WIZ-004 | Setup route survives 30 seconds past finalization, then locks permanently | P0 | — | — |
| DD-WIZ-005 | School email is required at setup and nullable in post-setup editing | P1 | — | — |

### 7.1 Flow and State

#### DD-WIZ-001 — Session State, No Partial Rows

Persisting wizard progress to the database would mean inventing a lifecycle for half-schools: draft flags, cleanup jobs, ownership of abandoned rows. Session storage sidesteps all of it — an expired session simply restarts from welcome with nothing to clean, because nothing was written. The thirty-second post-finalization grace is the one deliberate exception, and it exists so the key can be copied, not so progress can be saved.

#### DD-WIZ-002 — Global Redirect

Per-route guards fail open: every new route is unprotected until someone remembers, and the one forgotten route on an uninstalled system is the one indexed or probed. Global middleware inverts the default — everything redirects until proven setup-related — with narrow passthroughs for real files and Livewire traffic. The cost is eternal vigilance over the passthrough list, which is cheap compared to auditing every future route for setup-safety.

#### DD-WIZ-003 — Atomic Commit

The transaction around finalization is the load-bearing wall of setup day. Without it, each failure mode in §4.2 becomes a distinct rescue operation requiring database surgery by someone who may not know SQL. With it, failures are boring: retry from the finalize screen against untouched state. The accepted cost is a slightly larger transaction holding locks a moment longer — negligible for an operation that runs once per school, ever.

### 7.2 Locking and Context

#### DD-WIZ-004 — Grace Before the Lock

Thirty seconds of post-finalization access balances two fears: the installer who needs one more look at the key, and the operator who fears a permanently reachable setup page. The window is configurable because vault rituals differ, but its existence is not negotiable — shipping a key display with zero grace time would train installers to photograph screens, which is worse than any window. After it closes, the purge is total and the 404 is forever.

#### DD-WIZ-005 — Strict at Birth, Lenient for Life

Requiring the school email during setup while allowing it to be cleared later looks contradictory until you see the two contexts: provisioning must guarantee a contactable instance with a deliverable welcome message, while post-setup editing must tolerate a school whose address genuinely changed or lapsed. The same key, two validation contexts, each correct for its moment. Both specs document the split so no future reader "fixes" one side into matching the other.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Uninstalled traffic reaches setup | 100% of probed routes redirect | HTTP sweep of representative routes pre-install |
| Anonymous wizard entry | 0 successes without a valid token | Attempted entry without and with expired tokens |
| Double finalization harmless | 0 duplicate rows, `RejectedException` raised | Submit finalization twice in tests |
| Setup route locked after window | 404 past 30 s, session purged | HTTP assertions post-window |
| Prepared installer to completion | < 5 min | Timed walkthrough of the six steps |

---

## 9. Roadmap

### Prerequisites

This spec builds after its dependencies are complete:

| Spec | What It Provides |
|------|-----------------|
| [8NZAU-installation.md](8NZAU-installation.md) | Setup token, provisioned database, seeded roles, `setup.is_installed` flag |

### Build Guide

After this spec, the system owns a six-step browser wizard that creates the super admin, the school profile, and the first department, then finalizes atomically and mints the recovery key. The wizard is the entry point for all configuration; nothing academic exists before it completes.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [C9ZB6-recovery-ecosystem.md](C9ZB6-recovery-ecosystem.md) | Operates the recovery key minted here; verifies against `setup.install_recovery_key` |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|----------------------------------|--------|-------|----------|
| R-1 | No second factor on recovery while SMTP is optional; revisit OTP if managed mail becomes universal on target hosting | Open | Maintainer | — |
| R-2 | Session-scoped wizard state means a mid-wizard session loss restarts the flow; accepted because nothing partial was ever written | Accepted | Maintainer | — |
| A-1 | We assume the token holder reaching the browser is authorized to create the super admin; physical and chat-channel security of the URL is the school's responsibility | Accepted | Maintainer | — |

---

## Quick References

- [Installation](8NZAU-installation.md) — CLI provisioning and token minting that precede the wizard
- [Recovery ecosystem](C9ZB6-recovery-ecosystem.md) — operates the recovery key minted at finalization
- [School profile](81SMS-school-profile.md) — post-setup editor with the relaxed email context
- [Branding, theme & locale](52O1I-branding-theme-locale.md) — owns brand keys derived here
- [Authentication](YB7RG-authentication.md) — owns login that the wizard hands off to
- [ADR: Gradual migration](../adr/adr-gradual-migration.md) — array-first inputs stabilizing toward DTOs
- [ADR: SmartLogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — masked audit trail for finalization
- [ADR: Exception hierarchy](../adr/adr-exception-hierarchy.md) — `RejectedException` on re-finalization
- [ADR: Base class mandate](../adr/adr-base-class-mandate.md) — action and entity shape requirements
- [Spec registry](index.md) — all specs grouped in 12 phases
