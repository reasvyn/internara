# Account Recovery Slips — User-Facing Backup Access

> **Spec ID:** SHQ1J
> **Status:** Full
> **Owner:** Auth
> **Depends on:** SE5Q9, YB7RG, WQGTP

## Description

Self-serve backup access for locked-out users: authenticated users generate ten single-use
recovery codes stored as hashed `AccessToken` rows, print them once as a slip, and later redeem
exactly one code with their username to set a new password. Admins can generate a set on behalf
of users who never made their own.

---

## 1. Problem Statements

### PS-1 — Users Without Working Email Recovery

School mail servers go down during enrollment peaks, and some students never configured a
reachable address in the first place. Email password reset then fails closed with no fallback,
leaving the account unreachable until an administrator intervenes by hand.
**→ Requirement:** FR-SLIP-001/002/003 (generatable code set), UC-SLIP-002 (guest redemption).

### PS-2 — Account Lockout Without Admin Help

Every manual password reset costs a coordinator a phone call, a verification, and a database
edit — multiplied across hundreds of students each term. Users who prepared a slip can recover
alone at midnight before a deadline instead of waiting for office hours.
**→ Requirement:** FR-SLIP-006/007/008/009 (throttled self-serve redemption), UC-SLIP-002.

### PS-3 — Admin-Assisted Recovery Without Password Exposure

New hires who forgot credentials during onboarding never generated their own slips, and handing
admins raw password-reset power would expose every account to insider reset. Generating a
single-use set for the user preserves the boundary: the admin mints codes, the user redeems
one, and nobody ever sees or sets another person's password.
**→ Requirement:** FR-SLIP-013 (admin manager), DD-SLIP-001 (single active set).

---

## 2. Goals & Non-Goals

### Goals

- **One-command code generation** — ten uppercase codes, hashed at rest, revocable as a set. *Why:* preparation takes seconds while lockout happens at the worst moment.
- **Single-use guest redemption** — username plus code plus new password, throttled by IP. *Why:* self-serve recovery without weakening the login gate.
- **Once-only display with PDF slip** — session-held plaintext shown a single time with a printable slip. *Why:* the slip works like a printed backup key, not a retrievable password.
- **Admin generation without password touch** — search, select, generate, hand over securely. *Why:* onboarding failures resolve without granting password-write power.
- **Logged, masked, rejection-safe flows** — SmartLogger with PII masking on every generation and redemption, `RejectedException` for every business refusal. *Why:* recovery is the most abuse-sensitive flow in auth and must also be the best-observed.

### Non-Goals

- **Super-admin CLI recovery.** *Why:* server-level recovery belongs to the recovery ecosystem spec, not to user-facing slips.
- **Email password reset.** *Why:* the dedicated reset spec owns that path; slips are the non-email fallback, not its replacement.
- **Multi-factor authentication.** *Why:* TOTP and second factors are post-MVP depth on top of core auth.
- **Code rotation on redemption or reprint-on-demand.** *Why:* consumed codes stay consumed and slips print once; regeneration mints a fresh set instead.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled here
because each story below has a code-testable consequence verified by the listed layer.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SLIP-001 | Authenticated user generates ten codes, views them once, and downloads the printable slip | P0 | F | Full |
| UC-SLIP-002 | Locked-out guest redeems one code with username and new password under IP throttling | P0 | F | Full |
| UC-SLIP-003 | Admin searches for a user and generates a fresh set on their behalf | P0 | F | Full |

### 3.1 Self-Serve Generation and Redemption

#### UC-SLIP-001 — Student prints the slip before internship starts

A Grade 12 student about to leave for a remote workshop with spotty connectivity opens the
recovery page while still on school Wi-Fi and generates her set. The screen shows ten numbered
codes exactly once beside a warning to store them like a certificate, and the download button
produces the same ten as a dated PDF. Closing the page or clicking done clears the session, so
reopening shows no codes — only the option to generate a fresh set that would revoke these. She
folds the printout into her placement folder, which is precisely where it needs to be when the
mail server is unreachable two months later.

#### UC-SLIP-002 — Midnight lockout the night before logbook deadline

Locked out at 23:00 with the daily logbook window closing, the student opens the guest recovery
page and enters username, one unused code from the folded slip, and a new password twice. The
first two attempts with a mistyped code fail gently with the same generic message, and the
fourth rapid attempt meets the throttle instead of a password guess oracle. The correct third
attempt resets the password inside one transaction, marks that single code consumed, and
redirects to login with a translated confirmation. The remaining nine codes stay valid, and the
used one never works again.

### 3.2 Assisted Recovery

#### UC-SLIP-003 — Admin rescues a new hire who never prepared

A newly hired supervisor forgot credentials during onboarding week and never generated slips,
so the admin opens the slip manager, searches by name fragment, and selects the right account
from the shortlist. One generate action revokes any stale set and shows the fresh ten exactly
once for secure handover — read aloud over a verified call or handed over in person, never
mailed. The admin never sees a password field and never sets one; the supervisor redeems a code
through the same guest path as everyone else, which keeps the insider boundary intact.

---

## 4. Functional Requirements

Global defaults from QLHDO and D2FT3 apply (localization, dual-layer authorization, Action Triad,
`RejectedException`, SmartLogger with PII masking). `Status` is `Full` for every row because this
spec is implemented and verified.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-SLIP-001 | GenerateRecoverySlipAction revokes all existing account_recovery tokens for the user before minting a new set | P0 | F | Full |
| FR-SLIP-002 | Generation mints exactly ten random twelve-character uppercase alphanumeric codes | P0 | F | Full |
| FR-SLIP-003 | Each code persists as a hashed AccessToken with token_type account_recovery and a long break-glass expiry | P0 | F | Full |
| FR-SLIP-004 | Generation dispatches RecoverySlipGenerated carrying the user and the code count | P1 | F | Full |
| FR-SLIP-005 | Generation returns the plaintext set with the first-code RecoveryCodeData DTO | P1 | F | Full |
| FR-SLIP-006 | Guest redemption throttles to three attempts per three hundred seconds keyed by IP | P0 | F | Full |
| FR-SLIP-007 | Redemption resolves the user by username and refuses with a generic RejectedException when unresolvable | P0 | F | Full |
| FR-SLIP-008 | Redemption iterates only valid tokens and compares with Hash::check() | P0 | F | Full |
| FR-SLIP-009 | On a match redemption sets the new hashed password and stamps last_used_at on the consumed token inside one transaction | P0 | F | Full |
| FR-SLIP-010 | On no match redemption throws a generic RejectedException indistinguishable from the unknown-user case | P0 | F | Full |
| FR-SLIP-011 | RecoveryCode component holds plaintext only in session, renders a numbered list with warnings, offers one PDF download, and clears on leave or done | P0 | F | Full |
| FR-SLIP-012 | AccountRecovery guest component collects username, code, and password pair, enforces the IP throttle, and on success flashes confirmation and redirects to login | P0 | F | Full |
| FR-SLIP-013 | RecoverySlipManager gates on User viewAny, finds users by identity search, delegates generation to GenerateRecoverySlipAction, and shows the set once | P0 | F | Full |
| FR-SLIP-014 | Every generation and redemption writes a SmartLogger entry with PII masked before either sink | P0 | F | Full |
| FR-SLIP-015 | Slip follow-ups leave through queued after-commit listeners and never through model observers | P1 | A | Full |

### 4.1 Code Generation

#### FR-SLIP-001 — Revocation before minting, every time

When early testers generated a second set and both sets stayed valid, a leaked first printout
remained a live key forever. Revoking the whole existing set inside the same unit as minting
makes only the newest printout live, so losing an old slip stops mattering the moment a new one
is printed. The rule holds for admin-generated sets too, which is why handover always starts
from a clean slate rather than accumulating live codes across semesters.

#### FR-SLIP-002 — Ten codes in a fixed, typable alphabet

Five codes felt fragile once students started losing slips to rain and motorbike gloveboxes,
while twenty made the printout look like a wall nobody would transcribe. Ten twelve-character
uppercase alphanumerics split the difference: enough redundancy to survive losing half the
slip, constrained enough to read aloud over a phone without ambiguity between similar glyphs.
The fixed count also makes the iteration bound in redemption predictable, keeping hash-check
time flat.

#### FR-SLIP-003 — Hash at rest with a break-glass horizon

Storing printable codes would have turned any database backup leak into a master key ring for
every account. Each code therefore persists only as a one-way hash on the shared token model
under its own type discriminator, with an expiry so distant it never interrupts a school
career. The discriminator matters as much as the hash: account-recovery rows stay queryable
apart from API tokens and session tokens, so revocation and validity checks never touch a
foreign token family.

#### FR-SLIP-004 — Generation announces itself to the system

A silent generation would leave the audit trail unable to answer when a live set started or how
many codes it holds. The generated event carries the user plus the count — never the codes
themselves — so activity loggers and future listeners can record lineage without ever seeing
printable secrets. The payload shape is deliberately minimal because every extra field would be
another candidate for accidental PII capture.

#### FR-SLIP-005 — Plaintext leaves through exactly one door

The Action returns the printable set alongside the first code's DTO because the session display
and the PDF renderer both need the same source of truth exactly once. Nothing else in the
system retains the plaintext afterward: the database holds hashes, the event holds a count, and
the log holds metadata. The narrowness of this return is the whole security model for display,
and widening it to any second accessor would break the once-only promise.

### 4.2 Redemption Discipline

#### FR-SLIP-006 — Throttle that treats guessing as the threat

An unthrottled twelve-character code field invites scripted guessing, especially since usernames
at a school follow predictable patterns. Three attempts per five minutes per IP slows a guessing
campaign to irrelevance while letting a legitimate user mistype twice and succeed on the third
without noticing the limit. The throttle engages before any user lookup, so even the timing of
the refusal reveals nothing about whether the username exists.

#### FR-SLIP-007 — Username resolution that never enumerates

If unknown usernames failed differently from wrong codes, anyone could harvest the student
roster one request at a time. The redemption path resolves by username and refuses with the
same generic, translated message whether the name is absent or the code mismatches, and the
response timing stays comparable between the two cases. Support staff debugging a genuine typo
rely on the masked server log, never on a distinct client message.

#### FR-SLIP-008 — Comparison confined to live tokens only

Checking revoked, consumed, or expired rows would have resurrected dead codes after every
regeneration, defeating the single-active-set promise. The iteration first narrows to rows that
are unrevoked, unused, and unexpired for this user, then compares each stored hash in turn. At
most ten hashes are ever checked, so the worst-case cost is bounded and constant, and a timing
channel across set sizes never develops.

#### FR-SLIP-009 — Password and consumption flip together

Setting the password while leaving the code live would let a shoulder-surfer reuse the same
slip line within minutes. The matching path therefore writes the newly hashed password and
stamps the consumed token's usage marker in one transaction: either the account moves to the
new credential with that line burned, or nothing changes. The single-use property is a database
fact rather than a UI suggestion, which is what makes the remaining nine codes trustworthy.

#### FR-SLIP-010 — One generic refusal for every failure

Distinct error texts for bad code versus unknown user versus expired set once let testers map
account existence from a cafe across the street. Collapsing all redemption failures into a
single generic message removes the oracle while staying honest — something did not match, and
the throttle notice appears only when the rate limit itself is the cause. The uniformity is
asserted by tests that probe each failure mode and compare the surfaced text.

### 4.3 Interface and Stewardship

#### FR-SLIP-011 — Display that forgets by design

A recovery code readable a week later is a password by another name, so the authenticated
component keeps plaintext only in the session, renders it as a numbered slip with storage
warnings, and wipes it the moment the user leaves or confirms done. The PDF download renders
the same ten with the warning banner and timestamp baked in, then streams without persisting a
copy on disk. Reopening the page afterward offers generation, never recall, which teaches the
print-and-keep habit the whole flow depends on.

#### FR-SLIP-012 — Guest form with no more than it needs

The locked-out user gets four fields and nothing else: username, code, new password, and its
confirmation. The component validates shape before invoking the Action, enforces the same IP
throttle as the Action for defense in depth, and on success flashes a translated confirmation
before sending the user to login. No account details, no code hints, and no enumeration leak
appear in any branch, because the guest surface is the most probed page in auth.

#### FR-SLIP-013 — Admin handover without password power

Letting admins type a new password for someone else would make every help-desk session a
potential account takeover. The manager instead searches by identity fragment, selects the
account, and delegates to the same generation Action the user would invoke, displaying the
fresh set once for verified handover. The viewAny gate on the user policy keeps the search
itself admin-only, and the generated set obeys the same revocation and once-only rules as a
self-made one.

#### FR-SLIP-014 — Every mint and burn leaves a masked trace

Recovery is where abuse looks most like legitimate use, so generation and redemption both write
structured log entries through the dual-channel logger with masking applied before either sink.
Codes, passwords, and candidate guesses never reach the payload; the entries record who, when,
which token row, and what outcome. The masked trace is what lets a coordinator distinguish a
student's three mistyped attempts from a guessing campaign without ever seeing secrets.

#### FR-SLIP-015 — Slow follow-ups ride queued events, never observers

Token revocation and password writes complete synchronously inside the Action's transaction
because the next login attempt must see them immediately. Everything after that — activity
fan-out, notification copies, secondary cache work — leaves through after-commit listeners that
carry the queue marker, so a rollback discards them and a slow mail driver never holds the
redemption request open. No model observer touches token state, since observers cannot span the
needed modules, cannot queue, and cannot promise discard-on-rollback semantics.

---

## 5. Non-Functional Requirements

`Target` is the concrete SLO. `Status` is `Full` for every row because this spec is implemented
and verified.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SLIP-001 | Codes persist only as one-way hashes with no plaintext code or password in storage or logs | 0 plaintext secrets | P0 | F | Full |
| NFR-SLIP-002 | Plaintext codes render exactly once from session and vanish on leave or done | 0 re-renders | P0 | F | Full |
| NFR-SLIP-003 | Printable slip always carries storage warnings and a generation timestamp | 100% slips | P1 | F | Full |
| NFR-SLIP-004 | Guest redemption refuses the fourth rapid attempt from one IP inside the window | 3 per 300s | P0 | F | Full |
| NFR-SLIP-005 | Every user-facing string passes through __() with mirrored en and id keys | 0 hardcoded strings | P0 | A | Full |
| NFR-SLIP-006 | All classes declare strict types | 100% files | P0 | A | Full |
| NFR-SLIP-007 | Unknown-user and wrong-code failures surface identically with no enumeration signal | 1 shared message | P0 | F | Full |

### 5.1 Secrecy and Display

#### NFR-SLIP-001 — Hashes everywhere secrets rest

A backup table copied to a developer laptop once settled the plaintext debate permanently: the
copy contained only hashes, so the incident was a cleanup rather than a breach notification to
hundreds of families. One-way hashing on every stored code, masked logging that drops
candidate guesses before either sink, and no plaintext column anywhere in the schema together
make the database a dead end for anyone seeking a usable slip. The guarantee is verified by
inspecting stored rows and log output for secret-shaped material.

#### NFR-SLIP-002 — Once-only as a tested property

Students treat anything re-viewable as retrievable, so the session-held plaintext must provably
disappear. Navigating away, confirming done, or simply reloading leaves no codes behind — only
the offer to mint a fresh set. The test generates, displays, leaves, and returns asserting
emptiness, which turns a design intention about user habits into an enforced behavior.

#### NFR-SLIP-003 — Warnings printed on the artifact itself

A bare list of codes migrates into a phone photo gallery with no context, where it looks like
any other screenshot worth sharing. Baking the never-share, store-offline warning plus the
generation date into both the screen and the PDF keeps the handling instructions attached to
the secret through every copy. The timestamp additionally settles which printout is current
when two slips surface in the same drawer.

### 5.2 Abuse Resistance and Hygiene

#### NFR-SLIP-004 — Throttle with a visible edge

Legitimate users mistype, so the limit sits at three per five minutes rather than one-strike
lockout, and the fourth rapid attempt meets an explicit too-many-requests answer instead of a
mysterious failure. Keying by IP rather than username keeps an attacker from rotating names to
multiply guesses, while per-school traffic stays far below the threshold in normal use. The
window length matches the global sensitive-endpoint posture so operators reason about one rate
story.

#### NFR-SLIP-005 — Translation without exceptions

A lockout screen is the worst place for a foreign-language surprise, since the reader is
already stressed and possibly offline from help. Every label, warning, flash, and refusal in
the three components passes through the translation helper with both language files present.
Dynamic fragments travel as placeholders inside complete sentences, preserving grammar in both
languages.

#### NFR-SLIP-006 — Strict typing across the flow

Recovery mixes usernames, codes, hashes, and timestamps in ways that loose comparison once
confused — an empty code matched nothing yet passed a truthiness check far enough to log a
spurious attempt. Declaring strict types in every class of this flow turns that category into
immediate test failures instead of production mysteries. The convention scan holds the line for
future files.

#### NFR-SLIP-007 — Indistinguishable failures as an anti-oracle

When wrong-code and unknown-user messages differed by a single word, a script distinguished
them within an hour and began mapping valid usernames. Unifying the surfaced text, status, and
approximate timing across all redemption failures removes the signal entirely. Tests assert the
shared message for each underlying cause, so a future edit that helpfully clarifies one branch
fails loudly before it can reopen the oracle.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 Actions

```php
final class GenerateRecoverySlipAction extends BaseCommandAction
{
    public function execute(User $user): ActionResponse;
}

final class RedeemRecoverySlipAction extends BaseCommandAction
{
    public function execute(RedeemRecoverySlipData $data): ActionResponse;
}
```

### 6.2 Entities and DTOs

```php
final readonly class RecoveryCodeState extends BaseEntity
{
    public function __construct(
        public ?string $lastUsedAt,
        public string $expiresAt,
    );

    public function isValid(): bool;
}

final readonly class RecoveryCodeData extends BaseData
{
    public function __construct(
        public string $plainText,
        public string $hashedToken,
        public ?string $expiresAt,
    );
}

final readonly class RedeemRecoverySlipData extends BaseData
{
    public function __construct(
        public string $username,
        public string $code,
        public string $newPassword,
    );
}
```

### 6.3 Livewire Components

```php
class AccountRecovery extends BaseFormView
{
    public string $username = '';
    public string $recoveryCode = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    public function redeem(RedeemRecoverySlipAction $action): void;
}

class RecoveryCode extends BaseFormView
{
    public function generate(GenerateRecoverySlipAction $action): void;
    public function downloadPdf(): StreamedResponse;
    public function resetCode(): void;
}

class RecoverySlipManager extends BaseFormView
{
    public ?User $selectedUser = null;
    public string $search = '';

    public function searchUsers(): Collection;
    public function generate(GenerateRecoverySlipAction $action): void;
}
```

### 6.4 Token Model and Routes

```php
class AccessToken extends BaseModel
{
    // token_type = 'account_recovery' for recovery codes
    public function asRecoveryCodeState(): RecoveryCodeState;
}
```

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET /recover-account` | `AccountRecovery` | `guest`, throttle 3 per 300s |
| `GET /profile/recovery` | `RecoveryCode` | `auth` |
| `GET /admin/recovery-slips` | `RecoverySlipManager` | `auth`, `role:super_admin\|admin` |

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§7). `Layer` / `Status` stay `—` unless
a decision has a code-testable consequence; per QLHDO these are recorded decisions, not test rows.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-SLIP-001 | Keep exactly one live set per user by revoking before every generation | P0 | — | — |
| DD-SLIP-002 | Expire codes on a break-glass horizon instead of a short rotation | P1 | — | — |
| DD-SLIP-003 | Redeem as a guest behind IP throttling instead of requiring login | P0 | — | — |
| DD-SLIP-004 | Hash codes, show them once, and keep token state in Actions with queued after-commit follow-ups and no observers | P0 | — | — |

### 7.1 Set Lifetime

#### DD-SLIP-001 — One live printout at a time

Accumulating live sets would have made every forgotten printout a permanent spare key, and the
support question which of three slips is current has no good answer at a busy school office.
Revoking before minting keeps the mental model trivially simple: the newest slip is the only
slip. Losing the newest before printing is recoverable by minting again, while losing an older
one after replacement is automatically harmless.

#### DD-SLIP-002 — Expiry measured in decades

Short expiries assume users maintain their recovery material like certificates, yet field
visits show slips filed and forgotten until the lockout day years later. A horizon longer than
any school career removes rotation busywork that nobody would perform while adding no
practical risk, since single-use consumption and set revocation already bound the live key
count to ten. The rows themselves stay lightweight enough that accumulation never pressures
the table.

### 7.2 Access Shape and Storage Posture

#### DD-SLIP-003 — Guest redemption because lockout means logged out

Requiring authentication to recover authentication is a circle with no entry, so the redemption
page deliberately stands outside the auth gate. The openness is paid for with the IP throttle
plus the knowledge requirement of username and a twelve-character code, a combination that
resists guessing while staying usable at midnight without staff help. All other admin and
profile surfaces in this flow remain authenticated, keeping the guest exception as narrow as
the purpose demands.

#### DD-SLIP-004 — Hashes, once-only display, and queued follow-ups without observers

Password-style hashing plus session-once display borrows the hardest-won lessons of credential
storage rather than reinventing them, which is why a database copy contains no usable secret.
The observer question was settled on scope and timing: token revocation must commit in the same
transaction as minting, cross-module notifications must fan out after commit and discard on
rollback, and slow follow-ups must queue — three properties observers structurally lack. The
Action owns the synchronous state, queued listeners own everything after, and the token model
stays a persistence adapter throughout.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Redemption with valid code and username | 100% success | Feature test matrix on redemption paths |
| Guessing resistance | 4th rapid attempt refused per IP | Throttle test + manual burst |
| User enumeration via failure text | 0 distinguishable signals | Failure-mode message comparison test |
| Live codes beyond the newest set | 0 rows | Token table audit after regeneration |
| Plaintext secrets in storage or logs | 0 findings | Row inspection + masked-log review |
| Consumed code reuse | 0 second successes | Double-redeem test on one code |

---

## 9. Roadmap

### Prerequisites

| Spec | What it provides |
|------|-----------------|
| [Base Classes](SE5Q9-base-classes.md) | `BaseCommandAction`, `ActionResponse`, `RejectedException`, `BaseEntity`, `BaseData` |
| [Authentication](YB7RG-authentication.md) | `User` model, `AccessToken` model, auth infrastructure |
| [File Uploads & Media](WQGTP-file-uploads-media.md) | DomPDF rendering for the printable slip |

### Build Guide

Build the two Actions with their transaction and throttle behavior first against the existing
token table, then the guest redemption component under throttle tests, then the authenticated
display with its once-only session behavior and PDF rendering, and finally the admin manager on
top of the same generation Action. No schema work is needed beyond the shared token table.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | Recovery slips stand alone as the non-email fallback beside password reset |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume ten codes per set with a break-glass horizon fit school handling habits without rotation reminders at MVP | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Authentication](YB7RG-authentication.md) — `User` and `AccessToken` infrastructure this spec builds on
- [Password Reset](D9TKW-password-reset.md) — the email recovery path this spec backs up
- [Recovery Ecosystem](C9ZB6-recovery-ecosystem.md) — super-admin CLI recovery, explicitly out of scope here
- [ADR: Exception hierarchy](../adr/adr-exception-hierarchy.md) — `RejectedException` for business refusals
- [ADR: Eloquent observers](../adr/adr-eloquent-observers.md) — observer-vs-event criteria
- [ADR: MVP spec trim](../adr/adr-mvp-spec-trim.md) — reprint depth consolidated out of this spec
