# Password Confirmation — Re-Authentication Gate

> **Spec ID:** CQVSK
> **Status:** Full
> **Owner:** Auth
> **Depends on:** SE5Q9 (base classes), YB7RG (authentication)

## Description

A re-authentication checkpoint for sensitive actions: before changing an email, deleting an
account, or touching anything else the `password.confirm` middleware guards, the holder proves
present knowledge of the current password. Success stamps a timestamp into the session; the
middleware measures its freshness against a configurable window. The gate is throttled, fully
audited, and deliberately session-bound rather than token-based.

---

## 1. Problem Statements

### PS-1 — Sensitive Actions Require Re-Authentication

An authenticated session proves someone signed in — not that the person at the keyboard now is
the same someone. Walk-away browsers in staff rooms and labs turn every sensitive action into a
takeover primitive: change the email, and the account quietly belongs to whoever typed fastest.
Demanding the current password at the moment of the sensitive act re-binds the human to the
session exactly where the stakes justify the friction.

### PS-2 — Session Freshness Enforcement

A confirmation that never expires is a permission granted forever — the morning's careful proof
still authorizing midnight mischief on an unattended machine. The confirmation timestamp must
therefore decay: fresh enough and the action proceeds silently, stale and the gate reappears.
Freshness converts a one-time proof into a rolling one, matching assurance to recency without
nagging the user on every click.

---

## 2. Goals & Non-Goals

### Goals

- **Password verification against the live hash** — current password checked via the hasher, never compared plain. *Why:* re-authentication must prove knowledge, not echo storage.
- **Session timestamp on success** — `auth.password_confirmed_at` stamped at confirmation time. *Why:* downstream guards need a freshness signal, not a second login.
- **Attempt throttling** — 5 tries per 300 seconds per requesting address. *Why:* the gate itself must not become a password oracle.
- **Complete audit coverage** — success and failure both reach SmartLogger. *Why:* confirmation attempts are authentication events and deserve the same trail as logins.

### Non-Goals

- **Two-factor authentication**. *Why:* possession factors are post-MVP depth; knowledge re-proof is the MVP gate.
- **Biometric confirmation**. *Why:* school hardware cannot be assumed to offer it.
- **Gating every action**. *Why:* only routes behind the `password.confirm` middleware pay this friction; ubiquity would train users to resent the gate.

---

## 3. User Stories / Use Cases

One journey: interrupted by the gate, proving knowledge, returning to the intended act.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-PWCON-001 | Authenticated user confirms the current password and resumes the guarded action | P0 | F | Full |

### 3.1 The Checkpoint

#### UC-PWCON-001 — User Proves Presence at the Gate

A teacher about to change the account email is bounced to the confirmation screen instead —
the middleware found no fresh timestamp. One password entry later the Action verifies against
the stored hash, stamps the session, clears the throttle count, and returns the teacher to the
exact page the interruption came from. The email change proceeds within the freshness window;
had the teacher wandered off for hours first, the gate would simply have reappeared, patient
and unmoved.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch.
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-PWCON-001 | ConfirmPasswordAction verifies the password via Hash::check() against the authenticated user's hash | P0 | F | Full |
| FR-PWCON-002 | Successful confirmation stamps session auth.password_confirmed_at with the current time | P0 | F | Full |
| FR-PWCON-003 | Failed confirmation throws RejectedException with the localized failure message | P0 | F | Full |
| FR-PWCON-004 | Successful confirmation logs the password_confirmed event via SmartLogger | P0 | F | Full |
| FR-PWCON-005 | Confirmation attempts throttle at 5 per 300 seconds per requesting address | P0 | F | Full |
| FR-PWCON-006 | ConfirmPassword component presents a password field with validation | P1 | F | Full |
| FR-PWCON-007 | Successful confirmation redirects to the intended URL, falling back to the dashboard | P1 | F | Full |
| FR-PWCON-008 | password.confirm middleware enforces timestamp freshness against the AUTH_PASSWORD_TIMEOUT window (default 10800s) | P0 | F | Full |

### 4.1 Verification & Session Stamp

#### FR-PWCON-001 — Knowledge Proved Against the Hash

The Action compares the submitted password with the stored bcrypt hash and nothing else — no
plaintext retrieval, no fallback questions, no administrator override path. Hash comparison is
deliberately slow work, which here is a virtue twice over: it rate-limits guessing at the
mathematical level beneath the throttle's administrative level. Proving knowledge means surviving
the hash, exactly as login demands.

#### FR-PWCON-002 — Timestamp as Freshness Proof

Success writes the current Unix time into the session under the framework's conventional key,
and that integer becomes the credential every guarded route inspects. Storing a moment rather
than a boolean is what makes decay possible — the middleware subtracts and compares instead of
merely checking presence. A boolean could only ever answer "once confirmed"; a timestamp answers
"confirmed how long ago", which is the only question that matters.

#### FR-PWCON-003 — Failure Wears the Rejected Voice

A wrong password throws RejectedException with the localized failure message — the same
business-rule voice as every other gate refusal, rendered as a form error rather than a crash.
Uniformity matters because confirmation failure is routine (typos happen under pressure), and
routine failures must be boring: a toast, a cleared field, another try within budget. Drama is
reserved for budget exhaustion, which arrives as the throttle message instead.

#### FR-PWCON-004 — Success Enters the Audit Trail

The confirmation writes its own activity entry with the user as subject through the Action's
logger, so the trail shows not just "email changed" but "presence re-proved, then email
changed" — cause and effect in order. Months later, disputing whether the holder authorized a
sensitive act reduces to finding this row. An unaudited gate is an unprovable gate, and
unprovable gates lose arguments.

### 4.2 Component, Throttle & Freshness

#### FR-PWCON-005 — Five Tries per Five Minutes

The component counts attempts per requesting address with a five-minute decay: five honest tries
for the flustered user, then a cooling wait announced with the remaining seconds. Failed
verifications increment, success clears — the same forgiveness pattern as the login gate, scaled
down to a checkpoint that should rarely need more than one attempt. Without the budget, the
confirmation screen would be a quiet brute-force surface sitting inside the authenticated area.

#### FR-PWCON-006 — Single Field, Validated

The component shows exactly one input — the current password — validated through its form object
before any Action runs. Minimalism is protective: there is nothing to misconfigure, no second
field to confuse with the new secret the guarded action is about to set. The most dangerous
screen in the settings area is deliberately the simplest one in it.

#### FR-PWCON-007 — Return to the Interrupted Act

After stamping the session, the component redirects to the URL the middleware remembered —
the email form, the deletion review, whatever was in progress — defaulting to the dashboard when
no intended destination survives. Losing the user's place would convert protection into
punishment: the teacher who confirmed correctly only to land somewhere unrelated learns to dread
the gate rather than trust it.

#### FR-PWCON-008 — Freshness Measured in Three Hours

The middleware compares the stamped moment against a 10800-second window, overridable per
deployment through `AUTH_PASSWORD_TIMEOUT`. Three hours spans a working morning without
re-prompting every sensitive click, yet bounds an abandoned session's authority to a single
school shift. The value is configuration rather than code because risk appetites differ — a lab
of shared PCs may tighten it, a staff laptop fleet may keep it — and neither should require a
deploy to decide.

---

## 5. Non-Functional Requirements

`Target` is concrete where observable at runtime and `N/A` where scans and review verify.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-PWCON-001 | Confirmation success and failure both reach SmartLogger | Both outcomes logged | P0 | F | Full |
| NFR-PWCON-002 | Passwords are never stored or logged in plain text at any point | 0 plain occurrences | P0 | A | Full |
| NFR-PWCON-003 | All confirmation PHP files declare strict_types=1 | 100% of files | P0 | A | Full |

### 5.1 Auditability & Secrecy

#### NFR-PWCON-001 — Both Outcomes Audited

Success writes the activity entry; failure writes the system-side error with the user id and the
refusal message under masking. The pair matters because one-sided audit tells comforting lies —
a gate that logs only its passes hides its sieges. Verification walks both paths and expects
both rows; the failure row's presence is what turns a guessing campaign against this endpoint
from invisible to obvious.

#### NFR-PWCON-002 — Plain Text Nowhere

The submitted password lives transiently in the request, dies in the hash comparison, and
reaches neither storage nor log — the failure log carries the user id and the error text, never
the guess. Holder-proofing the audit trail this way means even full log access yields no
credential material, which is the difference between a log leak and a credential leak sharing
one incident.

### 5.2 Code Discipline

#### NFR-PWCON-003 — Strict Types Throughout

Hash inputs, throttle keys, and timestamp arithmetic all run strictly typed, so the session
stamp is always an integer moment and never a coerced string surprise. The convention scan
covers the flow's small file set completely — a short file list is no excuse for a missing
declaration when the tooling checks it for free.

---

## 6. API / Data Contracts

### 6.1 Action

```php
// app/Modules/Auth/Domain/Password/Actions/ConfirmPasswordAction.php
final class ConfirmPasswordAction extends BaseCommandAction
{
    public function execute(User $user, string $password): ActionResponse;
    // Hash::check($password, $user->password) or RejectedException(auth.password_confirmation_failed)
    // Success: session(['auth.password_confirmed_at' => time()]) + log('password_confirmed')
}
```

### 6.2 Livewire Component

```php
// app/Modules/Auth/Domain/Password/Livewire/ConfirmPassword.php
class ConfirmPassword extends BaseFormView
{
    public ConfirmPasswordForm $form;   // single password field, validated
    public function confirm(ConfirmPasswordAction $action): void;
    // Throttle key 'confirm-password|{ip}', max 5, decay 300s; success clears
    // Failure: form error + system-side SmartLogger entry; success: intended URL
}
```

### 6.3 Route & Freshness Window

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET /user/confirm-password` | `ConfirmPassword` (Livewire) | `auth`, `auth.throttle` |

Freshness window: `config/auth.php` `password_timeout`, default `10800` seconds (3 hours),
overridable via `AUTH_PASSWORD_TIMEOUT`. Guarded routes declare the `password.confirm`
middleware, which redirects timestamp-less or stale sessions here while remembering the
intended URL.

---

## 7. Design Decisions

Decisions are recorded rationale, not test rows — `Layer` / `Status` stay `—` per the template.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-PWCON-001 | Session timestamp confirmation instead of one-time tokens | P0 | — | — |
| DD-PWCON-002 | Confirmation audit stays an inline SmartLogger call, not an event | P1 | — | — |

### 7.1 State & Audit Shape

#### DD-PWCON-001 — A Moment, Not a Token

One-time tokens would demand issuance, delivery, storage, expiry, and consumption machinery for
a proof that lives seconds and travels nowhere — all cost, no benefit, since the prover is
already authenticated in the same session. The timestamp reuses the framework's conventional
session key and its freshness middleware wholesale, covering every guarded route — present and
future — with zero per-route code. Persistence across the window is the accepted trade: a
hijacked session inherits the confirmation, but session hijacking is answered by transport and
cookie defenses, not by re-proving knowledge every ninety seconds.

#### DD-PWCON-002 — Logging Without Fan-Out

No listener waits on confirmation, no second consumer exists, and none is foreseeable — the
audit entry is the entire downstream. An event here would be ceremony without audience:
dispatch, registration, and a listener whose only act is the log call the Action already
performs inline. Should a consumer ever appear (anomaly scoring on confirmation failures, say),
graduating to an event is a small, spec-amended step; until then the inline call keeps the
checkpoint honest about its actual complexity.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Verification accuracy | Wrong password never confirms | Mismatch test asserting RejectedException |
| Throttle integrity | 6th attempt within 300s refused with wait message | Burst test on the component budget |
| Freshness enforcement | Stale timestamp redirects to the gate | Time-travel test past the window |
| Return fidelity | Post-confirmation landing matches the interrupted route | Intended-URL assertion in flow test |
| Audit duality | Success and failure rows both present | Log assertions on both paths |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | `BaseCommandAction`, `ActionResponse`, `RejectedException` |
| [authentication.md](YB7RG-authentication.md) (YB7RG) | `User` model, session lifecycle, throttle and audit voice |

### Build Guide

One Action plus one component: verification and timestamp first, throttle and audit second, the
intended-URL return last. Wire the `password.confirm` middleware onto whichever routes guard
sensitive acts — the gate activates per route, so each adoption is a one-line middleware
addition with no changes to this flow.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | Consumed by any current or future action requiring `password.confirm` middleware |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume the component throttle key (address-scoped, no user id) is sufficient because the route already requires authentication, narrowing guessers to session holders | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Identity & Auth phase, Full status
- [Authentication](YB7RG-authentication.md) — login gate, session lifecycle, throttle philosophy
- [Password reset](D9TKW-password-reset.md) — self-service recovery flow
- [Profile management](OCEMS-profile-management.md) — sensitive profile acts this gate protects
- [Base classes](SE5Q9-base-classes.md) — Action and response contracts
- [Logging & error handling](89SRA-logging-and-error-handling.md) — SmartLogger channels and PII masking
- [Middleware pipeline](2CF4Y-middleware-pipeline.md) — password.confirm freshness enforcement
- [ADR: exception hierarchy](../adr/adr-exception-hierarchy.md) — RejectedException for business-rule refusal
- [ADR: SmartLogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — masked, queryable audit trail
- [ADR: eloquent observers](../adr/adr-eloquent-observers.md) — why confirmation audit stays inline, not observed
