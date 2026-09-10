# Password Reset — Forgot/Reset Flow

> **Spec ID:** D9TKW
> **Status:** Full
> **Owner:** Auth
> **Depends on:** SE5Q9 (base classes), YB7RG (authentication)

## Description

Self-service recovery for forgotten passwords: a throttled reset-link request that never reveals
whether the address exists, followed by a throttled token redemption that sets a new password
through the framework's password broker. Both halves audit through SmartLogger with PII masking,
both refuse with the RejectedException voice, and the single-use hashed token lifecycle makes a
forwarded or replayed link worthless.

---

## 1. Problem Statements

### PS-1 — Users Lock Themselves Out

Forgotten passwords arrive in waves — Monday mornings, post-holiday returns, the first week of a
placement period. Each one without self-service becomes an administrator interruption: verify the
person, set a temporary credential, communicate it safely. An email-based reset flow returns that
time to both sides, letting the user recover alone while the administrator never handles a live
password at all.

### PS-2 — Reset Abuse Prevention

An unguarded reset endpoint is two weapons in one: an oracle for testing which addresses are
registered, and a mail cannon for flooding any inbox on demand. Request and redemption each need
their own throttle budget, and the request half must answer throttled and nonexistent addresses
with the same success it shows real ones — otherwise the protection announces exactly what it
was built to hide.

---

## 2. Goals & Non-Goals

### Goals

- **Throttled reset-link dispatch** — 3 requests per address-plus-IP per hour, enumeration-safe replies. *Why:* recovery must not become an oracle or a mail cannon.
- **Throttled token redemption** — 5 attempts per address-plus-IP per 5 minutes. *Why:* the token guard deserves its own budget against guessing.
- **Request form with honest feedback** — link-sent confirmation that means nothing about existence. *Why:* the user needs closure; the attacker must get none.
- **Redemption form with token binding** — email pre-filled from the link, password plus confirmation. *Why:* bind the new secret to the address the token was issued for.
- **Full audit coverage** — requested, throttled, mismatched, succeeded, and failed events via SmartLogger. *Why:* recovery is a high-value flow; its trail must be complete.

### Non-Goals

- **Password reset via SMS or phone**. *Why:* email is the verified channel every account already holds.
- **Security question-based reset**. *Why:* answers are low-entropy and socially discoverable.
- **Administrator-initiated resets for other users**. *Why:* owned by [user-crud-and-status.md](95EVB-user-crud-and-status.md).
- **Recovery via printed slips**. *Why:* owned by [account-recovery-slips.md](SHQ1J-account-recovery-slips.md).

---

## 3. User Stories / Use Cases

Two journeys — asking for the link, then spending it — each under its own throttle.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-PWRST-001 | User requests a reset link and receives an existence-neutral confirmation | P0 | F | Full |
| UC-PWRST-002 | User redeems a valid token and sets a new password | P0 | B | Full |

### 3.1 Request & Redeem

#### UC-PWRST-001 — User Requests a Reset Link

A teacher locked out before first period types an address and submits. The Action checks the
hourly budget for that address-plus-IP pair; whether throttled or fresh, known or unknown, the
screen answers identically — link sent. Behind the mask, only the unthrottled known address
actually triggers token generation and mail. The teacher watches a confirmation state that
promises nothing verifiable, which is precisely what keeps the same screen safe in an attacker's
hands.

#### UC-PWRST-002 — User Redeems the Token

The link in the mailbox opens the redemption form with the address already filled and the token
carried from the URL. New password plus confirmation go through the Action's own throttle, the
confirmation match is checked before the broker is touched, and the broker consumes the token on
success. A replayed link — clicked twice, forwarded to a friend, harvested from browser history —
meets a consumed token and a localized refusal. One link, one password, one use.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch.
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-PWRST-001 | SendPasswordResetLinkAction throttles by email+IP: max 3 attempts per 3600 seconds | P0 | F | Full |
| FR-PWRST-002 | Throttled link requests still return RESET_LINK_SENT to prevent enumeration | P0 | F | Full |
| FR-PWRST-003 | Link action calls Password::sendResetLink() with the submitted email | P0 | F | Full |
| FR-PWRST-004 | Link action logs link-requested and link-throttled events via SmartLogger | P0 | F | Full |
| FR-PWRST-005 | ResetPasswordAction throttles by email+IP: max 5 attempts per 300 seconds | P0 | F | Full |
| FR-PWRST-006 | Throttled redemption throws RejectedException with the throttle message | P0 | F | Full |
| FR-PWRST-007 | Redemption validates password against confirmation before calling Password::reset() | P0 | F | Full |
| FR-PWRST-008 | Confirmation mismatch logs the mismatch event and throws RejectedException | P0 | F | Full |
| FR-PWRST-009 | Redemption calls Password::reset() with email, token, password, and confirmation | P0 | F | Full |
| FR-PWRST-010 | Successful reset logs password_reset_success and returns ActionResponse::ok() | P0 | F | Full |
| FR-PWRST-011 | Invalid token or unknown user throws RejectedException with a localized message | P0 | F | Full |
| FR-PWRST-012 | ForgotPassword component shows the email input and a link-sent confirmation state | P1 | F | Full |
| FR-PWRST-013 | ResetPassword component binds the URL token and shows pre-filled email plus both password fields | P1 | F | Full |
| FR-PWRST-014 | Reset tokens expire after 60 minutes | P0 | F | Full |
| FR-PWRST-015 | Successful reset consumes the token so it cannot be reused | P0 | F | Full |

### 4.1 Reset-Link Dispatch

#### FR-PWRST-001 — Hourly Budget per Address and IP

Three requests per hour per address-plus-IP pair is enough for the user who mistyped twice and
starvation rations for the mail cannon. Keying on the pair rather than either half alone matters:
address-only would let one attacker burn everyone's budget for a victim, IP-only would let a
botnet walk around it. The RateLimiter holds the count with an hour-long decay, after which the
slate wipes clean without administrator involvement.

#### FR-PWRST-002 — Throttled Replies Wear the Success Mask

The cruelest detail of the design is also its kindest: even a throttled request answers
`RESET_LINK_SENT`. Telling the requester "slow down" would confirm the address is real and
worth slowing down for — the throttle signal itself would become the oracle. So the budget
enforces silently while the response stays cheerful, and the only observable difference is the
absence of mail, visible solely to the mailbox owner.

#### FR-PWRST-003 — Delegation to the Password Broker

Token generation, hashing, storage, and mail composition all belong to the framework's broker,
not to hand-rolled code — password-reset cryptography is exactly the wheel nobody should
reinvent. The Action's job narrows to throttle, delegate with the submitted address, and report
the broker's status upward. Whatever the broker answers for an unknown address, the Action
masks behind the same success (FR-PWRST-002).

#### FR-PWRST-004 — Both Link Outcomes Audited

Requested and throttled are both first-class log events, because a recovery flow missing its
negative space is half-blind: the throttled-event trail is where mail-cannon campaigns show up
as pattern rather than anecdote. Both entries carry the address under PII masking, keeping the
audit queryable by investigators without turning the log store into an address book.

### 4.2 Token Redemption

#### FR-PWRST-005 — Five-Minute Budget on Redemption

Redemption gets a tighter window than dispatch — five attempts per five minutes — because this is
where token guessing happens and guesses are cheap to automate. The budget binds the same
address-plus-IP pair so a guess campaign must spread across both dimensions to scale, and
spreading across dimensions is exactly what makes campaigns visible. Legitimate redemption needs
one attempt; five is headroom, not constraint.

#### FR-PWRST-006 — Throttle Speaks as Rejection

Unlike dispatch, redemption answers throttling honestly with a RejectedException carrying the
wait message. The asymmetry is deliberate: at dispatch time honesty would confirm address
existence, but at redemption time the requester already holds a link — itself proof of existence
— so there is nothing left to protect and everything to gain by telling the user when to retry.

#### FR-PWRST-007 — Confirmation Checked Before the Broker

Password-versus-confirmation comparison happens inside the Action before the broker is ever
called, so a typo costs a log entry rather than a consumed token. Burning a single-use token on
a mismatched submission would convert every fat-fingered attempt into a full restart of the
flow — new link, new mail, new wait — punishing exactly the users recovery exists to help.

#### FR-PWRST-008 — Mismatches Logged, Then Refused

The mismatch path writes its own distinct event before throwing, separating "couldn't type it
twice" from "couldn't prove the token" in the audit trail. Support staff reading the logs can
therefore distinguish confusion from attack at a glance: scattered mismatches are humans,
concentrated token failures are campaigns. One log line buys the whole distinction.

#### FR-PWRST-009 — Broker Call Carries All Four Fields

Email, token, password, and confirmation travel together into the broker call, binding the new
secret to the address the token was minted for. No field may be defaulted or re-derived
mid-flight — the token that arrived with the link is the token that gets spent, and the address
pre-filled from the link is the address that gets the password. Binding is what stops token
transplanting between accounts.

#### FR-PWRST-010 — Success Logged and Answered

On the broker's success signal the Action hashes the new password into the user row inside a
transaction, writes the success event with the user as subject, and returns a clean ok. The log
entry closes the recovery story that the request event opened — requested, redeemed, done — so
any future question about when an account last changed hands by recovery has a two-row answer.

#### FR-PWRST-011 — Dead Tokens Refused Gently

Invalid, expired, consumed, or stranger tokens all collapse into one localized refusal, because
distinguishing "wrong token" from "no such user" would rebuild the enumeration oracle at the
second gate after closing it at the first. The broker's granular statuses map onto the gentle
surface inside the Action; callers and screens never see the raw codes.

### 4.3 Livewire & Token Lifecycle

#### FR-PWRST-012 — Request Form States

The request component is a two-state machine — an email field, then a confirmation — with no
third state for failure, throttling, or unknown addresses. That missing third state is the
feature: every submission lands on the same confirmation screen, and the component's simplicity
is what makes the guarantee reviewable. A future designer tempted to add "address not found"
hints would break the spec's core promise in the name of helpfulness.

#### FR-PWRST-013 — Redemption Form Binding

Token arrives via the URL, address pre-fills from the query string, and both password fields
start empty — the form binds the broker's context without asking the user to retype what the
link already knows. Pre-filling the address (rather than trusting an editable field alone)
keeps the redemption anchored to the token's intended recipient even if the visible field is
disturbed.

#### FR-PWRST-014 — Sixty-Minute Token Life

An hour is long enough for a teacher to finish class before opening the mail and short enough
that a leaked link decays before it travels far. The expiry rides the framework default rather
than custom configuration — one less knob to mis-set, one more behavior every Laravel operator
already understands. After the hour, the token is cryptographically dead regardless of use.

#### FR-PWRST-015 — Single-Use Consumption

Success destroys the token's future: the broker marks it spent so replays — double-clicks,
forwarded mail, history resurrection — all meet refusal. Single-use is what lets schools treat
reset links casually in forwarding-friendly inboxes; the damage radius of a leaked link is
exactly one password change, and only the first arrival gets it.

---

## 5. Non-Functional Requirements

`Target` is concrete where the property is observable at runtime and `N/A` where scans and
review carry the verification.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-PWRST-001 | All reset events are logged via SmartLogger under the Auth module | Every event, Auth module | P0 | F | Full |
| NFR-PWRST-002 | Email addresses are masked in logs via withPiiMasking() | 0 raw addresses in sinks | P0 | A | Full |
| NFR-PWRST-003 | Throttle and unknown-address replies reveal nothing about existence | Identical success surface | P0 | F | Full |
| NFR-PWRST-004 | Reset tokens persist hashed, never plain | 0 plain tokens stored | P0 | A | Full |
| NFR-PWRST-005 | All password-reset PHP files declare strict_types=1 | 100% of files | P0 | A | Full |

### 5.1 Audit & Masking

#### NFR-PWRST-001 — Complete Event Trail

Requested, throttled, mismatched, succeeded, failed — the flow's vocabulary is fixed and every
word of it reaches SmartLogger tagged to the Auth module. Completeness is verifiable by walking
each path in test and asserting its entry appears; a silent path is a finding, because in
incident review the missing event is always the one that mattered.

#### NFR-PWRST-002 — Addresses Masked Before Sinks

The address that recovery revolves around must never land raw in any log sink — a log viewer
with a stolen credential should not harvest the school's address book as a bonus. Masking
applies by key name at the logger boundary, so even a future event that logs its whole payload
inherits protection without its author remembering to ask.

### 5.2 Token & Response Safety

#### NFR-PWRST-003 — Existence-Neutral Surface

Dispatch-time responses are indistinguishable across known, unknown, and throttled addresses —
same status, same screen, same timing envelope as far as engineering can hold it. Adversarial
verification submits all three cases and diffs everything observable; the row passes when the
diff is empty, and any helpful elaboration added later fails it by construction.

#### NFR-PWRST-004 — Hashed Token Storage

The broker stores digests, never secrets, so a database read — backup, breach, or curious query —
yields nothing redeemable. This property is inherited from the framework rather than
re-implemented, which is itself the decision: token cryptography stays where cryptographers
maintain it, and the spec merely forbids any parallel store that might be lazier.

### 5.3 Code Discipline

#### NFR-PWRST-005 — Strict Types Throughout

Throttle arithmetic, decay windows, and broker status comparisons all run under strict typing so
no silent coercion turns a status string into a truthy accident. The convention scan holds this
line across the flow's files; reviewers spend their attention on throttle budgets, not type
jokes.

---

## 6. API / Data Contracts

### 6.1 Actions

```php
// app/Modules/Auth/Domain/Password/Actions/SendPasswordResetLinkAction.php
final class SendPasswordResetLinkAction extends BaseCommandAction
{
    public function execute(string $email): ActionResponse;
    // Throttle key: 'forgot-password:{lower(email)}|{ip}', max 3, decay 3600s
    // Throttled => ActionResponse::ok(Password::RESET_LINK_SENT) (FR-PWRST-002)
    // Else Password::sendResetLink(['email' => $email]) + log
}

// app/Modules/Auth/Domain/Password/Actions/ResetPasswordAction.php
final class ResetPasswordAction extends BaseCommandAction
{
    public function execute(ResetPasswordData $data): ActionResponse;
    // Throttle key: 'reset-password:{lower(email)}|{ip}', max 5, decay 300s
    // Mismatch => log + RejectedException; invalid token/user => RejectedException
}
```

### 6.2 Livewire Components

```php
// app/Modules/Auth/Domain/Password/Livewire/ForgotPassword.php
class ForgotPassword extends BaseFormView
{
    public string $email = '';
    public bool $emailSent = false;   // false => form state, true => confirmation state
    public function sendLink(SendPasswordResetLinkAction $action): void;
}

// app/Modules/Auth/Domain/Password/Livewire/ResetPassword.php
class ResetPassword extends BaseFormView
{
    public string $token;              // bound from URL segment
    public string $email;              // pre-filled from query string
    public string $password = '';
    public string $passwordConfirmation = '';
    public function resetPassword(ResetPasswordAction $action): void;
}
```

### 6.3 Routes

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET /forgot-password` | `ForgotPassword` (Livewire) | `guest`, `auth.throttle` |
| `GET /reset-password/{token}` | `ResetPassword` (Livewire) | `guest`, `auth.throttle` |

### 6.4 Token Lifecycle

Token creation, hashing, 60-minute expiry, and single-use consumption ride the framework
password broker (`Password::sendResetLink` / `Password::reset`); no bespoke token store exists
for this flow. The broker's `PASSWORD_RESET` status is the sole success signal; every other
status maps to the localized refusal in FR-PWRST-011.

---

## 7. Design Decisions

Decisions are recorded rationale, not test rows — `Layer` / `Status` stay `—` per the template.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-PWRST-001 | Throttled dispatch answers success to starve enumeration | P0 | — | — |
| DD-PWRST-002 | Token lifecycle rides the framework broker, not a bespoke store | P0 | — | — |

### 7.1 Silence & Delegation

#### DD-PWRST-001 — Success as Camouflage

Answering throttled requests with success feels dishonest until the alternative is spelled out:
any distinguishable throttle reply becomes an existence test, and existence tests compound into
harvested address lists. The design therefore spends a small lie — "link sent" when nothing was
sent — to protect a large truth, that the system's user directory is not public. Redemption can
afford honesty only because holding a link already proves what dispatch must never confirm.

#### DD-PWRST-002 — Borrowed Cryptography

A bespoke token table would need its own generation, hashing, expiry, consumption, and sweep —
five mechanisms the framework broker already ships, reviewed by far more eyes than any school
project commands. Riding the broker concentrates custom code where the product actually differs
(throttle budgets, enumeration masking, audit vocabulary) and leaves the cryptographic core to
its maintainers. The day the framework changes its broker contract, one adapter absorbs the
difference instead of five hand-rolled tables.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Reset mail dispatched | Mail handed to the mailer within the request | Feature test asserting notification dispatch |
| Throttle integrity | No link mail under throttling, no 6th redemption within window | Burst tests on both Actions |
| Enumeration neutrality | Identical dispatch surface for known, unknown, throttled | Response comparison across the three cases |
| Token single-use | Second redemption with the same token refused | Replay test after successful reset |
| Expired-token refusal | Token older than 60 minutes refused | Time-travel test past expiry |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | `BaseCommandAction`, `ActionResponse`, `RejectedException` |
| [authentication.md](YB7RG-authentication.md) (YB7RG) | `User` model, login gate, throttle philosophy, audit voice |

### Build Guide

Dispatch first — throttle plus broker delegation plus the masked audit pair — then redemption
with its own budget and mismatch-before-broker ordering, then the two Livewire states. Both
routes stay guest-only behind the HTTP throttle; nothing in this flow assumes an authenticated
session at any point.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | Standalone recovery flow; credential-change listeners in YB7RG announce resets that pass through password-change Actions |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume broker-driven resets that bypass password-change Actions do not emit PasswordUpdated, so credential-change mail for broker resets depends on future listener wiring | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Identity & Auth phase, Full status
- [Authentication](YB7RG-authentication.md) — login gate, lockout budgets, credential-change notifications
- [Password confirmation](CQVSK-password-confirmation.md) — re-authentication gate for sensitive actions
- [Account recovery slips](SHQ1J-account-recovery-slips.md) — slip-based recovery alternative
- [Base classes](SE5Q9-base-classes.md) — Action and response contracts
- [Logging & error handling](89SRA-logging-and-error-handling.md) — SmartLogger channels and PII masking
- [Middleware pipeline](2CF4Y-middleware-pipeline.md) — HTTP throttle budgets
- [ADR: exception hierarchy](../adr/adr-exception-hierarchy.md) — RejectedException for business-rule refusal
- [ADR: SmartLogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — masked, queryable audit trail
