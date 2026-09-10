# Authentication — Login, Throttling & Session Lifecycle

> **Spec ID:** YB7RG
> **Status:** Full
> **Owner:** Auth
> **Depends on:** SE5Q9 (base classes), T4B26 (RBAC & authorization)

## Description

Internara authenticates every user through one login path: identifier detection accepts email or
username, dual throttling (HTTP middleware plus cache-based lockout with exponential backoff)
absorbs brute force, the session is regenerated on every state change, and identical generic
errors close the enumeration hole. The same spec owns the surrounding lifecycle — account
activation for provisioned users, the access-token primitives activation rides on, and the
dual-channel credential-change notifications that make silent takeover impossible.

---

## 1. Problem Statements

### PS-1 — Credential Stuffing & Brute Force Attacks

Students and teachers reuse passwords across services, which makes a school login endpoint a
natural credential-stuffing target. One throttling layer is not enough: an IP-based HTTP limit
stops a single noisy attacker but does nothing against the same username tried from a hundred
rotating addresses. The gate therefore needs two independent budgets — per-IP at the middleware
and per-identifier in the Action — so volumetric and distributed attacks each meet resistance.

### PS-2 — Session Fixation on Shared Computers

Lab PCs, library kiosks, and shared teacher-office machines mean browsers are communal property.
When the session identifier survives login unchanged, anyone who planted an identifier in that
browser beforehand inherits the victim's authenticated session — the classic fixation shape. Every
authentication state change must therefore mint a fresh session, and logout must additionally
destroy session data and rotate the CSRF token so nothing usable lingers behind.

### PS-3 — Account Lockout Recovery

A lockout that never explains itself converts a forgotten password into a support ticket. Fixed
long lockouts punish legitimate mistyping; fixed short ones let an attacker simply wait out each
round and resume. Exponential backoff squares the circle — the first breach costs ten seconds, a
sustained campaign costs hours — provided the remaining wait is always shown so the honest user
knows exactly when to retry.

### PS-4 — User Enumeration Prevention

A login form that distinguishes "unknown identifier" from "wrong password" is an oracle for
harvesting registered addresses, and even timing differences between the two paths leak the same
signal. Every failure must therefore read identically, and the lockout-cache read must happen
before any user lookup so the code path — and its timing — stays uniform whether the identifier
exists or not.

### PS-5 — Login Identifier Ambiguity

Nobody at a vocational school wants to remember whether they registered with an email address or
a username. Forcing a mode choice at the login form manufactures failed attempts, which feed the
very lockout counters this spec maintains. Accepting both identifiers through automatic detection
removes a whole class of avoidable failures at the gate.

### PS-6 — Account Activation

Administrator-provisioned accounts start `PROVISIONED` and cannot sign in until the holder proves
control of the account and sets a password. Without a self-service activation flow — time-limited
token, hashed storage, attempt limiting, one-time password setup — every new cohort means manual
administrator intervention per student. Activation is the bridge between provisioning and the
first login, and it must be secure enough to survive token-guessing campaigns.

### PS-7 — Credential Change Notifications

A password changed in silence is a takeover nobody notices. If only email fires and the attacker
controls the mailbox, the legitimate holder learns nothing; if only an in-app notice fires and
the victim never opens the dashboard, same result. Dual-channel notification — mail plus in-app —
means compromising one channel is no longer enough to keep the change quiet.

### PS-8 — Login Must Not Become a Proxy Decision

Mentor flows let a teacher act in a supervisor's stead, but that delegation is evaluated later,
at the policy layer, through the registration bridge. If login itself started resolving proxy
capabilities, the session would carry an ambiguous identity — audit entries could no longer say
plainly who signed in. Login therefore establishes exactly one stored role; proxy stays a
runtime permission check downstream.

---

## 2. Goals & Non-Goals

### Goals

- **Dual throttling** — HTTP middleware (5 attempts/60s) plus cache lockout (10 failures, then exponential backoff). *Why:* volumetric and distributed attacks each need their own budget.
- **Email-or-username identifiers** — automatic detection, no mode selector. *Why:* removes avoidable failures at the gate.
- **Session regeneration on every state change** — login and logout alike. *Why:* fixation is the shared-computer threat.
- **Exponential backoff with transparent recovery** — `10 * 2^(attempts - 10)` seconds, remaining wait always shown. *Why:* deters campaigns without stranding honest users.
- **Enumeration-safe failures** — identical messages, lockout read before lookup. *Why:* the login form must not be an account oracle.
- **Login audit trail** — domain events plus SmartLogger with PII masking. *Why:* every gate decision stays queryable without leaking identifiers into sinks.
- **Status-gated entry** — `PROVISIONED`, `SUSPENDED`, `ARCHIVED` never sign in. *Why:* provisioning, discipline, and archival states must hold at the gate, not just in the UI.
- **Secure activation** — token verification, attempt limiting, one-time password setup. *Why:* provisioned cohorts must reach first login without administrator hand-holding.
- **Dual-channel credential-change notification** — mail plus in-app on every password change. *Why:* silent takeover must be structurally impossible.

### Non-Goals

- **Multi-factor authentication**. *Why:* password-plus-throttling is the MVP gate; second factors are a post-MVP security extension.
- **Social login (Google, GitHub, Microsoft)**. *Why:* local-database bcrypt auth is the single-tenant default; external providers contradict data sovereignty.
- **Passwordless login (magic links, WebAuthn, passkeys)**. *Why:* post-MVP authentication depth, not MVP scope.
- **OAuth2 / OpenID Connect provider functionality**. *Why:* Internara is not an identity provider for third parties.
- **Password reset or account recovery flows**. *Why:* owned by [D9TKW](D9TKW-password-reset.md) and [SHQ1J](SHQ1J-account-recovery-slips.md).
- **Remember-me beyond Laravel's built-in**. *Why:* the framework cookie covers the MVP need; custom persistence is post-MVP.

---

## 3. User Stories / Use Cases

Seven journeys cover the gate: signing in, being locked out and recovering, signing out, surviving
a volumetric flood, activating a fresh account, and learning about a credential change.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-AUTH-001 | Student signs in with an email identifier and reaches the role dashboard | P0 | B | Full |
| UC-AUTH-002 | Attacker triggers exponential lockout after 10 consecutive failures | P0 | F | Full |
| UC-AUTH-003 | Authenticated user signs out with session destruction and CSRF rotation | P0 | F | Full |
| UC-AUTH-004 | Locked-out student waits out the backoff and signs in successfully | P1 | F | Full |
| UC-AUTH-005 | Automated flood from one IP is stopped at the HTTP middleware | P0 | F | Full |
| UC-AUTH-006 | Provisioned student activates the account and sets a first password | P0 | F | Full |
| UC-AUTH-007 | User changing a password receives mail and in-app notifications | P0 | F | Full |

### 3.1 Sign-In & Sign-Out

#### UC-AUTH-001 — Student Signs In with Email

Morning attendance rush is the load this journey was built for: hundreds of students signing in
within minutes on lab machines and phones. The identifier passes detection, the lockout cache is
consulted before any database touch, status gates confirm the account may sign in, credentials
verify, both throttle counters clear, the session regenerates, and the role dashboard loads. A
username identifier walks the identical path — detection is the only branch, everything downstream
is shared, which is exactly why identifier confusion can never desynchronize the two forms.

#### UC-AUTH-003 — User Signs Out

The school day ends on the same shared machines the morning started on, so sign-out has to leave
nothing reusable behind. Authentication state clears, the session invalidates, the CSRF token
rotates, and the browser lands back on the login page. Anyone sitting down afterward finds a
fresh guest session with a fresh token — the previous occupant's presence is cryptographically
gone, not merely hidden behind a redirect.

### 3.2 Lockout & Recovery

#### UC-AUTH-002 — Attacker Meets Exponential Backoff

A stuffing run against one identifier starts cheaply: the first nine failures cost the attacker
nothing but time. The tenth failure plants the lockout flag for ten seconds, the eleventh doubles
it, and by the fifteenth failure each retry costs over five minutes of waiting. The campaign's
economics collapse while a student who simply mistyped twice never notices the machinery at all —
that asymmetry between casual error and sustained attack is the entire point of the curve.

#### UC-AUTH-004 — Locked-Out Student Recovers

The sympathetic twin of the attack story: a student who forgot a password, burned ten attempts,
and now stares at a countdown. Waiting out the shown seconds and entering the correct password
clears both counters and signs the student in with a fresh session, as if the failures never
happened. Recovery needs no administrator, no ticket, no reset flow — just patience measured in
seconds and the correct credential.

#### UC-AUTH-005 — Flood Stopped at the Middleware

Before any of the application machinery even wakes up, the HTTP layer counts rapid login posts
per IP and answers the sixth within a minute with a 429 and a `Retry-After` promise. This is the
cheapest possible defense — no database query, no cache hash, no event — and it exists so the
expensive per-identifier logic never has to absorb raw volumetric floods on its own.

### 3.3 Activation & Credential Awareness

#### UC-AUTH-006 — Provisioned Student Activates

A new cohort arrives as rows: provisioned, passwordless, unreachable by login. Each student
receives an activation code, opens the activation form, and submits code plus chosen password in
one motion. Verification checks revocation, expiry, and hash in that order; success revokes the
token so the code dies with its first use, stores the hashed password, logs the transition, and
moves the account to `ACTIVATED`. The administrator who provisioned three hundred students never
touches any of them again.

#### UC-AUTH-007 — Password Change Announced Twice

The moment a password update commits, two queued listeners wake: one carries the mail
notification, the other the in-app notice. Either channel alone could be missed or controlled by
an attacker; together they mean the legitimate holder learns about the change through whichever
channel still reaches them. Awareness is not a courtesy here — it is the detection mechanism for
takeover, delivered twice by design.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch.
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-AUTH-001 | System accepts both email and username as login identifiers | P0 | F | Full |
| FR-AUTH-002 | LoginAction detects identifier type via FILTER_VALIDATE_EMAIL | P0 | F | Full |
| FR-AUTH-003 | System looks up the user by the detected field (email or username) | P0 | F | Full |
| FR-AUTH-004 | System checks account status via asApprentice()->status()->allowsLogin() | P0 | F | Full |
| FR-AUTH-005 | System rejects login for PROVISIONED, SUSPENDED, and ARCHIVED statuses | P0 | F | Full |
| FR-AUTH-006 | System checks asApprentice()->isLocked() before credential validation | P0 | F | Full |
| FR-AUTH-007 | System checks asApprentice()->requiresSetup() and rejects when true | P1 | F | Full |
| FR-AUTH-008 | Successful login regenerates the session to prevent fixation | P0 | F | Full |
| FR-AUTH-009 | AuthThrottleMiddleware enforces 5 login attempts per 60 seconds | P0 | F | Full |
| FR-AUTH-010 | LoginAction enforces cache-based lockout: 10 failures, then exponential backoff | P0 | F | Full |
| FR-AUTH-011 | Lockout duration follows 10 * 2^(attempts - 10) seconds | P0 | F | Full |
| FR-AUTH-012 | Failed-attempt counter lives under the attempts key with 24h TTL | P0 | F | Full |
| FR-AUTH-013 | Lockout flag lives under the lockout key with duration-based TTL | P0 | F | Full |
| FR-AUTH-014 | Successful login clears both attempts and lockout counters | P0 | F | Full |
| FR-AUTH-015 | Lockout state is read from cache before user lookup | P0 | F | Full |
| FR-AUTH-016 | Failed login dispatches LoginFailed with identifier and reason | P0 | F | Full |
| FR-AUTH-017 | LogLoginFailed records via SmartLogger with PII masking | P0 | F | Full |
| FR-AUTH-018 | Successful login dispatches LoginSucceeded with the user model | P0 | F | Full |
| FR-AUTH-019 | First login sends a role-specific welcome notification | P1 | F | Full |
| FR-AUTH-020 | Login success is recorded via $this->log() with the user as subject | P0 | F | Full |
| FR-AUTH-021 | Login Livewire component validates through the LoginForm form object | P1 | F | Full |
| FR-AUTH-022 | LoginForm requires both identifier and password | P0 | F | Full |
| FR-AUTH-023 | Login page is served at /login behind guest middleware | P1 | F | Full |
| FR-AUTH-024 | Login page surfaces error and lockout feedback to the user | P1 | F | Full |
| FR-AUTH-025 | Logout calls auth()->logout() | P0 | F | Full |
| FR-AUTH-026 | Logout invalidates the session data | P0 | F | Full |
| FR-AUTH-027 | Logout regenerates the CSRF token | P0 | F | Full |
| FR-AUTH-028 | Logout redirects to /login | P1 | F | Full |
| FR-AUTH-029 | ActivateAccountAction verifies via AccessToken::verify() for activation type | P0 | F | Full |
| FR-AUTH-030 | Successful activation revokes the token via AccessToken::revokeFor() | P0 | F | Full |
| FR-AUTH-031 | Successful activation stores the password via Hash::make() | P0 | F | Full |
| FR-AUTH-032 | Successful activation logs account_activated via $this->log() | P0 | F | Full |
| FR-AUTH-033 | Invalid or expired activation token throws RejectedException with a localized message | P0 | F | Full |
| FR-AUTH-034 | AccountActivation entity exposes requiresActivation, isTokenValid, isTokenExpired, hasExceededMaxAttempts | P1 | U | Full |
| FR-AUTH-035 | AccessToken::generateFor() creates hashed tokens with per-type TTL (activation 30d, recovery 7d, default 1d) | P0 | F | Full |
| FR-AUTH-036 | AccessToken::verify() checks revocation, expiry, and hash; failed guesses increment attempts | P0 | F | Full |
| FR-AUTH-037 | AccessToken::revokeFor() stamps revoked_at for the user and type | P0 | F | Full |
| FR-AUTH-038 | AccessToken::revokeAllExpired() bulk-revokes expired unrevoked tokens | P1 | F | Full |
| FR-AUTH-039 | AccessTokenState entity exposes isExpired, isRevoked, isValid, hasExceededMaxAttempts | P1 | U | Full |
| FR-AUTH-040 | ActivationToken entity exposes plainText, tokenId, expiresAt | P1 | U | Full |
| FR-AUTH-041 | PasswordUpdated event fires after every successful password change | P0 | F | Full |
| FR-AUTH-042 | SendPasswordChangedMail listener sends CredentialChangedNotification via mail | P0 | F | Full |
| FR-AUTH-043 | InvalidateSessionOnPasswordChange listener sends the in-app password_changed notice | P0 | F | Full |
| FR-AUTH-044 | CredentialChangedNotification carries localized subject, named greeting, change line, and optional support address | P1 | F | Full |
| FR-AUTH-045 | Login establishes the user's own stored role only; proxy capability resolves later at the policy layer | P0 | F | Full |

### 4.1 Identifier & Credential Validation

#### FR-AUTH-001 — Both Identifiers Accepted

A student who remembers a username but not the registered email must not burn lockout budget
discovering the difference. Accepting both forms at a single field keeps one code path, one
error vocabulary, and one throttle counter per person rather than per guess-shape — and the
detail that matters operationally is that the counter keys on the raw identifier string, so
`Budi.Santoso` and `budi.santoso@gmail.com` are different budgets even when they belong to the
same human.

#### FR-AUTH-002 — Detection via FILTER_VALIDATE_EMAIL

At runtime the Action asks one question — does this string parse as an email — and the answer
selects the lookup column. There is no mode toggle to desynchronize, no session flag to corrupt,
no second form to maintain. The day someone registers a username containing an at-sign is the day
this heuristic needs revisiting; until then its simplicity is load-bearing, because every branch
avoided at the gate is a branch an attacker cannot wedge open.

#### FR-AUTH-003 — Lookup by Detected Field

The query itself is deliberately boring: one `where` on the detected column, first match wins.
Boring is correct here — the lookup must be fast enough that its timing reveals nothing and
plain enough that reviewers can see at a glance no extra condition (status, role, school) leaked
into what should be a pure identity resolution step. Status and role judgments happen afterward,
in the open, where each can fail with its own reason code.

#### FR-AUTH-004 — Status Gate via allowsLogin()

Consider the archived graduate whose record must persist for reporting but who must never sign
in again. The status enum answers one boolean — may this account authenticate — and the Action
trusts it absolutely, before any password work begins. Centralizing the answer in the enum means
a future status addition defaults safely: whatever the new state is, it signs in only if someone
explicitly says so.

#### FR-AUTH-005 — Three Statuses Never Sign In

Provisioned accounts have no password yet, suspended accounts are under discipline, archived
accounts belong to history. Each rejects with the same generic blocked message, because telling a
prober "this account exists but is suspended" hands them a confirmed identifier plus
organizational gossip. The rejection reasons diverge internally for the audit log and converge
externally into one uninformative sentence.

#### FR-AUTH-006 — Locked Check Before Credential Validation

The `locked_at` flag is an administrator's emergency brake — a compromised account frozen
mid-incident. Checking it before touching the password hash serves two masters: the frozen
account never gets a free password oracle during its freeze, and the hash comparison (deliberately
slow bcrypt work) is never spent on an account already decided. Order here is a security property,
not a style preference.

#### FR-AUTH-007 — Setup Requirement Rejects

Some accounts exist structurally but are not ready for their holder — placement pending, profile
incomplete, activation half-finished. The `requiresSetup()` predicate catches these in-between
states and holds the gate with the same blocked message as every other status refusal. Without
this catch, half-provisioned accounts would either sign into a broken dashboard or need ad-hoc
blocks scattered across Livewire components.

#### FR-AUTH-008 — Session Regenerated on Success

The instant credentials verify, the old session identifier dies and a fresh one takes its place.
On a lab PC where the previous occupant's session id may have been observed, planted, or simply
lingered in browser history, this single call is what separates "signed in as Budi" from "signed
in as whoever sat here before". Everything the guest session accumulated is discarded; the
authenticated session starts with a clean slate and a fresh secret.

### 4.2 Dual Throttling & Lockout

#### FR-AUTH-009 — HTTP Middleware Budget

Five login posts per minute per IP-and-identifier is generous to humans and suffocating to
scripts: no student legitimately submits six logins in sixty seconds, while a stuffing tool
measures throughput in hundreds. The middleware answers excess with 429 and a `Retry-After`
header before the request reaches any Action, so floods die at the cheapest layer. General
authenticated traffic carries a wider thirty-per-minute budget under the same middleware.

#### FR-AUTH-010 — Cache Lockout After Ten Failures

Earlier designs stopped at the middleware and learned the distributed lesson the hard way: ten
failures from ten addresses sailed through IP counting untouched. The application-layer counter
follows the identifier instead of the address, so rotation buys the attacker nothing. Ten is the
tripwire because it absorbs a bad week of mistyping while catching any campaign that means
business — and once tripped, the backoff curve takes over.

#### FR-AUTH-011 — The Doubling Curve

Ten seconds, then twenty, forty, eighty — by the fifteenth consecutive failure each guess costs
over five minutes, and by the twentieth the lockout stretches past an hour. The formula needs no
configuration table and no administrator judgment; its shape is the policy. A fixed lockout would
let attackers schedule around it like a train timetable, but a doubling curve turns persistence
itself into the punishment.

#### FR-AUTH-012 — Attempts Counter with 24h TTL

The failure count lives in cache, not in the users table, for reasons both operational and
moral: a database write per failed guess would hand attackers a write-amplification weapon, and
a counter that survived forever would punish someone for mistakes made last semester. Twenty-four
hours bounds the memory of failure — long enough to connect a campaign across an evening, short
enough to forgive.

#### FR-AUTH-013 — Lockout Flag with Duration TTL

Where the counter remembers history, the flag enforces the present: it exists exactly as long as
the current backoff demands, then evaporates. Its time-to-live is the lockout, not a timestamp
someone must compare — expiry is automatic, which means there is no cleanup job to forget, no
stale flag to strand a legitimate user, and no code path where "locked forever" is reachable by
accident.

#### FR-AUTH-014 — Counters Clear on Success

One correct password erases the entire failure history for that identifier. Without this reset,
the student who mistyped nine times in September would trip the lockout on their first October
typo — a ratchet punishing honesty. Clearing on success encodes the belief that authentication,
once proven, absolves prior suspicion; attackers cannot exploit the forgiveness because each new
campaign restarts from zero against the same doubling wall.

#### FR-AUTH-015 — Lockout Read Before Lookup

The cache read happens before the database is even consulted, so the request timeline looks the
same whether the identifier belongs to anyone or not. Had the order been reversed, the extra
hash comparison for real users would have been measurable across a network — a timing oracle
distinguishing registered from unregistered identifiers one millisecond at a time. Sequence is
the countermeasure, and it costs a single cache get.

### 4.3 Events, Logging & Welcome

#### FR-AUTH-016 — LoginFailed Carries Identifier and Reason

Every failed attempt becomes a small structured fact — which identifier, which reason — dispatched
as an event rather than handled inline. The Action stays focused on the gate decision while the
fact fans out to whoever cares: loggers today, anomaly detectors tomorrow. Reason codes stay
internal vocabulary, never user-facing, so the audit trail can be precise where the error message
must be vague.

#### FR-AUTH-017 — Failure Logged with PII Masking

A security log that stores raw identifiers is a breach waiting for a log viewer. The failure
listener routes through SmartLogger with masking engaged, so emails arrive truncated and nothing
resembling a credential ever reaches either sink. Masking happens by key name before persistence,
which means even a developer logging a full payload by mistake gets protected by default — the
safety is structural, not habitual.

#### FR-AUTH-018 — LoginSucceeded Carries the User

Success likewise becomes an event, and its payload is the authenticated user rather than a bare
id — downstream listeners need roles, names, and first-login state without re-querying. The
identifier that started the journey travels alongside for correlation, closing the loop between
"someone knocked" and "this person entered" in the audit trail.

#### FR-AUTH-019 — Role Welcome on First Login

Nobody welcomes a user twice: the listener checks first-login state and stays silent for
returning sessions. A fresh student's first sight of the system is a greeting shaped for their
role — student, teacher, supervisor, admin — which doubles as a quiet confirmation that role
resolution worked. If the wrong welcome ever arrives, it is an early symptom of a role-mapping
bug, caught at the friendliest possible moment.

#### FR-AUTH-020 — Success Recorded with User Subject

Beyond the event fan-out, the Action writes its own activity entry with the user as subject —
actor, action, and identifier in one queryable row. Belt and suspenders is deliberate: events can
be deferred or dropped by queue trouble, but the inline log commits with the transaction. When
the two disagree, the inline record is the ground truth of what the gate decided.

### 4.4 Login Form & Session

#### FR-AUTH-021 — Validation Through LoginForm

The Livewire component never validates inline; it delegates to a form object that owns the rules.
This keeps the component a thin shell — render, delegate, map response — and gives the validation
surface exactly one home. A future rule change (minimum identifier length, new constraints) lands
in one file instead of being hunted across component methods.

#### FR-AUTH-022 — Identifier and Password Required

Both fields are mandatory before any backend work begins, which sounds trivial until the empty
submission that would otherwise burn a throttle attempt and write a failure log for nothing.
Client-visible requiredness plus server-side enforcement means the gate's expensive machinery —
cache reads, database lookup, bcrypt — never spins up for a blank form.

#### FR-AUTH-023 — Guest-Only Login Route

The login page answers only to guests; an authenticated visitor is somewhere else by definition.
Guest middleware plus the throttle middleware wrap the route together, so the page that invites
knocking is itself the most rate-limited surface in the application. There is exactly one such
door, which keeps phishing review simple: any other login-looking URL is by construction fake.

#### FR-AUTH-024 — Error and Lockout Feedback

A lockout the user cannot see is indistinguishable from a broken site, so the page renders both
failure flashes and the remaining-wait countdown. The messages inform without informing too much:
"try again in N seconds" helps the owner of the account and tells an attacker only what the
attacker's own stopwatch already knows.

### 4.5 Logout

#### FR-AUTH-025 — Authentication State Cleared

The framework's logout call detaches the user from the session — guards forget, remember-me
cookies die, the request is anonymous again. It is the first of three cleanup steps because the
order matters: identity detaches while the session still exists to be destroyed, rather than
destroying the container first and hoping the identity went with it.

#### FR-AUTH-026 — Session Data Invalidated

Invalidation burns the session's contents, not just its association — flash data, intended URLs,
any half-completed wizard state from the previous occupant. On shared hardware this is the step
that actually protects the next user from inheriting the last user's context. What regeneration
does for login, invalidation does for logout: the past becomes unreachable.

#### FR-AUTH-027 — CSRF Token Rotated

A stale CSRF token outliving its session is a forgery waiting for a tab left open. Rotation mints
a token bound to nothing, so any captured form from the previous session submits into the void.
It costs one call and closes a hole that session invalidation alone leaves ajar, since tokens can
persist in browser memory independent of session storage.

#### FR-AUTH-028 — Return to Login

After destruction comes direction: the browser lands on the login page, logged-out and
unambiguous. No dashboard flash, no "you are here" limbo — the signed-out state has exactly one
canonical address, which also gives shared-machine users a visually verifiable end to their
session. If the page renders, the logout worked.

### 4.6 Account Activation

#### FR-AUTH-029 — Token Verified by Type

Activation reuses the access-token machinery rather than inventing a parallel secret system: the
submitted code is checked against the user's `activation`-type token for revocation, expiry, and
hash match. Wrong guesses increment the attempt counter, so the activation form cannot become a
quiet brute-force surface while everyone watches the login gate. One token primitive, two flows,
zero duplicated cryptography.

#### FR-AUTH-030 — Token Dies on First Use

The revocation stamp lands in the same breath as success, converting a reusable code into
single-use by construction. Without this, the activation code printed on a slip of paper in a
school office would remain a permanent spare key — anyone finding it later could re-trigger
whatever the code unlocked. Revocation is what makes distribution casual and compromise
irrelevant.

#### FR-AUTH-031 — Password Stored Hashed

The chosen password is bcrypt-hashed before it touches the model, and the plain text never
persists anywhere — not in the token row, not in the log, not in a flash message. Activation is
the only moment a password enters the system outside the reset flow, so this row is where the
"hash at the boundary" discipline starts for every provisioned cohort.

#### FR-AUTH-032 — Activation Logged

The transition from provisioned to activated is an auditable fact with the user as subject,
written through the Action's own logger. Months later, when someone asks when a particular
student first gained access, the answer is a query rather than an archaeology expedition through
mail logs and administrator memory.

#### FR-AUTH-033 — Bad Tokens Speak Gently

Expired, revoked, mistyped, or never-issued — every token failure surfaces as the same localized
rejection, because distinguishing "wrong code" from "no code was ever issued" would let a prober
test which students have pending activations. The friendliness is camouflage: internally the
reason is logged precisely, externally every failure wears the same face.

#### FR-AUTH-034 — Activation Rules in the Entity

The activation predicates — does this account still need it, is the token within its window, has
guessing exceeded the budget — live on a readonly entity constructible without a database. That
keeps the policy unit-testable in milliseconds: token math, expiry edges, and attempt ceilings
are pure logic, and pure logic deserves tests that run before the coffee cools.

### 4.7 Access-Token Lifecycle

#### FR-AUTH-035 — Typed Tokens with Typed Lifetimes

Not all secrets deserve the same lifespan: an activation code mailed to a school office lives
thirty days, a recovery code seven, and anything else one. The generator hashes the secret with
bcrypt before storage — the database never holds a usable token — and upserts per user and type
so re-issuing naturally invalidates its predecessor. Lifetimes encode how urgently each flow is
expected to complete.

#### FR-AUTH-036 — Verification Checks Three Things

Revoked? Expired? Hash mismatch? Verification asks in an order that fails fast on the cheapest
rejection and increments the guess counter only on a genuine hash miss — an expired token is not
a guessing attempt, and treating it as one would let time itself burn a user's budget. Success
resets the counter and stamps last use, so the token's own history stays as auditable as the
account's.

#### FR-AUTH-037 — Revocation by User and Type

One call stamps every live token of a type for a user as revoked, which is how re-issue,
compromise response, and lifecycle transitions all share a single primitive. Revocation is a
timestamp rather than a deletion, preserving the forensic record: the row says when the token
died, not merely that it is gone.

#### FR-AUTH-038 — Expired Tokens Swept in Bulk

Expiry without revocation would leave the table filling with corpses that verification must keep
tripping over. The sweep stamps them all in one query — no per-row ceremony, no scheduler
scaling concerns at school size. It is janitorial work elevated to a requirement because
unbounded token tables are how "temporary" secrets become permanent attack surface.

#### FR-AUTH-039 — Token State as Pure Logic

Expired, revoked, valid, over-budget — the state predicates sit on a readonly entity bridged
from the model, so the trickiest temporal edges (exactly-at-expiry, revoked-yesterday) are
testable without touching a database. The model persists; the entity reasons. That split is what
lets a reviewer verify token semantics by reading one small file instead of tracing queries.

#### FR-AUTH-040 — Activation Token Value Object

The issued token's three faces — the secret shown once to the human, the identifier stored in
the row, the moment it dies — are exposed as named accessors rather than array keys. Named
access beats positional memory every time: no caller can confuse the secret with the id, and the
expiry travels with the value instead of being recomputed wherever convenient.

### 4.8 Credential-Change Notifications

#### FR-AUTH-041 — PasswordUpdated Fires on Change

The password-change Action closes its transaction by announcing what happened, and everything
notification-shaped hangs off that announcement. Producers never name consumers: the Action does
not know about mail or in-app channels, which means adding a third channel later touches zero
lines of password logic. The event is the seam between "it changed" and "who must know".

#### FR-AUTH-042 — Mail Listener Sends the Notice

The queued mail listener checks the user still has an address — deleted or never-set addresses
must not throw inside a queue worker — then delivers the credential-changed notification. Queued
delivery keeps the password-change request fast even when the mail server is slow, at the cost of
at-least-once semantics the notification's idempotent content easily tolerates.

#### FR-AUTH-043 — In-App Notice via SendsNotifications

The sibling listener writes the same fact into the application's own notification center under
the `password_changed` type, so the dashboard itself testifies. A user who never checks email
still meets the notice on next sign-in; a user whose mailbox an attacker controls still has one
channel the attacker cannot silence without the password they just changed.

#### FR-AUTH-044 — Notice Content Contract

Subject, named greeting, change line, optional support address from settings — the four parts
exist so the message is recognizable, personal, factual, and actionable in that order. The
support address resolves from configuration rather than hardcode because every school's help
destination differs, and a notification pointing at the wrong office is worse than none: it
teaches users to ignore security mail.

### 4.9 Role Boundary at Login

#### FR-AUTH-045 — Login Resolves Identity, Not Proxy

A teacher who may verify logbooks as a supervisor still signs in as a teacher — full stop. Proxy
capability is evaluated per action at the policy layer through the registration bridge, never
baked into the session at login, so the audit trail always shows a plain stored role behind
every authenticated request. Conflating the two would have made every login event ambiguous and
every policy check redundant; keeping them apart makes login boring and proxy explicit, which is
precisely the division the cross-role-proxy design demands.

---

## 5. Non-Functional Requirements

Architectural rows carry `N/A` targets and are verified by scans and tests rather than runtime
measurement; user-visible rows carry concrete targets.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-AUTH-001 | Session identifier regenerates on every login and logout | 100% of state changes | P0 | F | Full |
| NFR-AUTH-002 | CSRF token regenerates on logout | 100% of logouts | P0 | F | Full |
| NFR-AUTH-003 | Login failures reveal nothing about identifier existence | Identical message, all modes | P0 | F | Full |
| NFR-AUTH-004 | Lockout duration grows exponentially with consecutive failures | 10·2^(n-10)s from failure 10 | P0 | F | Full |
| NFR-AUTH-005 | Lockout state is read before user lookup on every attempt | 100% of attempts | P0 | F | Full |
| NFR-AUTH-006 | Cache keys hash the identifier with crc32b, never embed it raw | 8-hex-char suffix | P0 | A | Full |
| NFR-AUTH-007 | Lockout feedback states the remaining wait in seconds | Seconds shown, every lockout | P1 | F | Full |
| NFR-AUTH-008 | Credential and existence errors are indistinguishable | Byte-identical responses | P0 | F | Full |
| NFR-AUTH-009 | One identifier field accepts email and username with no mode switch | Single field, both forms | P1 | F | Full |
| NFR-AUTH-010 | Login rejects cleanly when the database is unreachable | Rejection, no stack trace | P1 | F | Full |
| NFR-AUTH-011 | Login proceeds past lockout machinery when cache is unreachable | Attempt allowed, gate intact | P1 | F | Full |
| NFR-AUTH-012 | Login form meets WCAG 2.1 Level AA | AA on interactive elements | P2 | B | Full |
| NFR-AUTH-013 | Every login input carries an associated label, not placeholder-only | 100% labeled | P2 | B | Full |
| NFR-AUTH-014 | Lockout and error messages announce via aria-live regions | Live region coverage | P2 | B | Full |
| NFR-AUTH-015 | Login form completes by keyboard alone in logical tab order | Full keyboard path | P2 | B | Full |
| NFR-AUTH-016 | All login, error, and lockout strings pass through the translation helper | 0 hardcoded strings | P0 | A | Full |
| NFR-AUTH-017 | Translation keys exist in both English and Indonesian locale files | Mirrored keys, en + id | P0 | A | Full |
| NFR-AUTH-018 | Lockout countdown renders with localized number formatting | Locale-aware numerals | P2 | F | Full |
| NFR-AUTH-019 | All auth PHP files declare strict_types=1 | 100% of files | P0 | A | Full |
| NFR-AUTH-020 | Auth events fire from Actions, never from Livewire components | 0 component dispatches | P0 | A | Full |

### 5.1 Security of the Gate

#### NFR-AUTH-001 — Fresh Session on Every State Change

Spot-checking session identifiers across sign-in and sign-out shows rotation without exception —
the target is total because a single missed regeneration is a single exploitable fixation. The
property is verified at the feature layer by asserting the identifier differs across the
transition, not by reading code and hoping.

#### NFR-AUTH-002 — Fresh CSRF Token on Logout

Logout without token rotation would leave the previous token valid into the next occupant's
session on a shared machine — a forgery primitive handed out for free. Every logout therefore
mints a new token, verified the same way as session rotation: compare before and after, expect
difference, accept nothing less.

#### NFR-AUTH-003 — Failures Say Nothing

Wrong password, unknown identifier, locked account, suspended status — the response is one
sentence regardless. Verification is adversarial: submit each failure mode and diff the
responses, expecting nothing to diff. Any future contributor who adds a helpful hint to one
branch breaks this row, which is why the check belongs in the suite rather than in review
memory.

#### NFR-AUTH-004 — Backoff Curve Holds Its Shape

The tenth failure buys ten seconds and each subsequent failure doubles the price; probing the
cache TTLs across a synthetic campaign should trace exactly that curve. If the durations ever
drift from the formula, either the constant or the exponent changed — both are policy decisions
requiring a spec amendment, never silent tuning.

#### NFR-AUTH-005 — Ordering Is the Countermeasure

The lockout-cache read precedes the user query on every attempt without exception, keeping the
timing profile uniform across existent and nonexistent identifiers. This is verified by
construction review plus failure-mode tests asserting identical behavior for unknown identifiers
— including that unknown identifiers still consume throttle budget rather than shortcutting it.

#### NFR-AUTH-006 — Hashed Cache-Key Suffixes

Raw identifiers must never appear in cache keys: an email address in a key namespace is PII in
an operational store, and special characters in usernames are an injection surface against
key-prefix schemes. The eight-hex-character crc32b digest fixes both — constant length, opaque,
and safe to concatenate onto the registered key prefixes from the cache-key registry.

### 5.2 Everyday Usability

#### NFR-AUTH-007 — Countdown, Not Silence

Every lockout response names the remaining seconds, drawn from the same translation key as the
throttle message so wording stays consistent. The number is computed by ceiling the difference to
the lockout timestamp — always rounding up, so "0 seconds" never taunts a user who must still
wait a fraction of one.

#### NFR-AUTH-008 — One Error Voice

Beyond sharing a message, the failure responses must match in shape — same status handling, same
flash structure, same timing envelope. Byte-identical is the aspiration and diff-empty is the
test: any observable distinction between "no such user" and "wrong password" reopens the oracle
this whole section exists to close.

#### NFR-AUTH-009 — Single Field, Both Forms

No radio button, no tab switch, no "login with" prefix choice — one field takes whatever the
user remembers. Usability testing here is refreshingly concrete: hand the form to someone who
knows only their username and someone who knows only their email, and both must reach the
dashboard with no detour through a mode they do not understand.

### 5.3 Graceful Degradation

#### NFR-AUTH-010 — Database Outage Rejects, Never Crashes

When the database is unreachable at 7 a.m. with a queue of students at the lab door, the gate
must say "unavailable" instead of vomiting a stack trace. The failure voice stays generic and
safe — no connection strings, no query fragments — while the full context lands in the system
log where the operator will actually look.

#### NFR-AUTH-011 — Cache Outage Skips, Never Blocks

Losing the cache must not lock out the school: with no lockout state readable, the attempt
proceeds to credential validation rather than failing closed. This is a conscious bias toward
availability at the authentication gate — the HTTP middleware still throttles floods, and cache
outages are rare and loud, while a school unable to sign in at all is an emergency by definition.

### 5.4 Access for Everyone

#### NFR-AUTH-012 — AA Contrast on the Gate

The login page is the one screen every user must pass, including users with low vision, so its
interactive elements hold WCAG 2.1 AA contrast. Gatekeeping by eyesight is the most embarrassing
possible accessibility failure — the requirement is only P2 because the page is simple enough
that meeting it is cheap, not because it matters less.

#### NFR-AUTH-013 — Labels, Not Placeholders

Every input carries a real associated label that survives focus, autofill, and screen readers —
placeholders evaporate exactly when the user needs guidance most. This is verified by inspecting
the rendered form rather than by behavior: the label elements must exist in the markup, paired to
their inputs, in both locales.

#### NFR-AUTH-014 — Lockouts Announced to Assistive Tech

The countdown and error flashes live in aria-live regions so screen-reader users learn about the
lockout at the same moment sighted users do. A security message perceptible to only some users
is a security message half-delivered; the live region closes the gap without changing a single
visual pixel.

#### NFR-AUTH-015 — Keyboard-Complete Login

Tab order follows reading order from identifier through password to submit, and the entire flow
— including lockout recovery — completes without a pointer. The test is physical: unplug the
mouse and sign in. Whatever blocks that path blocks a real user, and the gate cannot afford to
choose its users' hardware.

### 5.5 Language & Structure

#### NFR-AUTH-016 — No Hardcoded Strings

Every user-facing word on the authentication surface passes through the translation helper, and
the D3 scan proves it. Hardcoded strings are not a cosmetic issue here: an untranslatable lockout
message is a lockout message half the school cannot read, which converts a security feature into
a support burden in the language the scan was built to protect.

#### NFR-AUTH-017 — Mirrored Locale Keys

Each key exists in both English and Indonesian files, so toggling locale never surfaces a raw
key or a fallback-language surprise mid-lockout. The check is mechanical — key-set equality
across the two files — and it runs in CI because translators and developers edit those files on
different schedules.

#### NFR-AUTH-018 — Localized Countdown Numerals

The seconds-to-wait render through locale-aware number formatting rather than raw integers,
because a countdown is read under stress and stress punishes unfamiliar numeral shapes. Small
courtesy, large humanity: the user counting down from forty in their own script feels informed
rather than processed.

#### NFR-AUTH-019 — Strict Types Throughout

Every PHP file in the auth surface declares strict types — no coercion surprises in throttle
arithmetic, no silent string-to-int drift in TTL math. The convention scan enforces it so
reviewers never spend attention on what the tooling guarantees for free.

#### NFR-AUTH-020 — Actions Dispatch, Components Render

No Livewire component in the auth surface fires domain events directly; Actions own every
dispatch. The split keeps side effects inside the transactional boundary where rollback
semantics hold — an event fired from a component would escape even when the surrounding write
fails. The class-contract scan watches this seam so the triad never quietly inverts.

---

## 6. API / Data Contracts

### 6.1 LoginData DTO

```php
// app/Modules/Auth/Domain/Login/Data/LoginData.php
final readonly class LoginData extends BaseData
{
    public function __construct(
        public string $identifier,
        public string $password,
        public bool $remember = false,
    ) {}
}
```

### 6.2 LoginAction Pipeline

```php
// app/Modules/Auth/Domain/Login/Actions/LoginAction.php
final class LoginAction extends BaseCommandAction
{
    public function execute(LoginData $data): Authenticatable;

    // 1. Hash identifier: hash('crc32b', $identifier)
    // 2. Read lockout key BEFORE user lookup (FR-AUTH-015)
    // 3. Detect field via filter_var($identifier, FILTER_VALIDATE_EMAIL)
    // 4. User::where($field, $identifier)->first() — null => fail user_not_found
    // 5. Status gates: isLocked(), allowsLogin(), requiresSetup()
    // 6. Auth::attempt([$field => $identifier, 'password' => $password], $remember)
    // 7. Success: clear counters, session()->regenerate(), log + LoginSucceeded
    // 8. Failure: increment counter, backoff from attempt 10, log + LoginFailed
}
```

### 6.3 AuthThrottleMiddleware

```php
// app/Modules/Auth/Domain/Login/Http/Middleware/AuthThrottleMiddleware.php
// Login posts: 5 attempts / 60s, keyed per IP + identifier hash (429 + Retry-After)
// General auth traffic: 30 attempts / 60s, keyed per IP
// Budgets from config('auth.throttle.login_max_attempts') / login_decay_seconds
```

### 6.4 Cache Key Schema

Registered prefixes in `config/cache-keys.php`; the identifier travels only as a `crc32b` digest:

```
{auth_login_attempts}{crc32b(identifier)}  → int failure count, TTL 24h
{auth_login_lockout}{crc32b(identifier)}   → lockout timestamp, TTL = backoff duration
```

### 6.5 AccountStatus Reference

Login eligibility and transitions per `AccountStatus` (`allowsLogin()`, `validTransitions()`):

```
PROVISIONED → ACTIVATED, SUSPENDED            (login: no)
ACTIVATED   → VERIFIED, SUSPENDED, ARCHIVED   (login: yes)
VERIFIED    → RESTRICTED, SUSPENDED, ARCHIVED, INACTIVE (login: yes)
PROTECTED   → (terminal)                      (login: yes)
RESTRICTED  → VERIFIED, SUSPENDED, ARCHIVED   (login: yes)
SUSPENDED   → ACTIVATED, VERIFIED, ARCHIVED   (login: no)
INACTIVE    → VERIFIED, ARCHIVED, SUSPENDED   (login: yes)
ARCHIVED    → (terminal)                      (login: no)
```

### 6.6 Lockout Duration Table

| Failed Attempts | Lockout Duration | Formula |
| --------------- | ---------------- | ------- |
| 1–9 | none | — |
| 10 | 10 seconds | `10 * 2^(10-10)` |
| 11 | 20 seconds | `10 * 2^(11-10)` |
| 12 | 40 seconds | `10 * 2^(12-10)` |
| 15 | 320 seconds (~5 min) | `10 * 2^(15-10)` |
| 20 | 5120 seconds (~85 min) | `10 * 2^(20-10)` |

### 6.7 Events & Listeners

| Event | Dispatched By | Payload | Listener | Queued |
| ----- | ------------- | ------- | -------- | ------ |
| `LoginFailed` | `LoginAction` | identifier, reason | `LogLoginFailed` (SmartLogger, PII masked, system channel) | yes |
| `LoginSucceeded` | `LoginAction` | user, identifier | `SendRoleWelcomeNotification` (first login only, per-role) | no |
| `PasswordUpdated` | password-change Action | user | `SendPasswordChangedMail` (mail) + `InvalidateSessionOnPasswordChange` (in-app) | yes |

### 6.8 Routes

```php
// routes/web/auth.php — middleware: guest + auth.throttle
Route::get('/login', Login::class)->name('login');
Route::get('/activate', ActivateAccount::class)->name('activate');

// routes/web/user.php — logout under auth
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
```

### 6.9 Config Values

```php
// config/auth.php — 'throttle' (env-overridable)
'login_max_attempts' => 5, 'login_decay_seconds' => 60,
'max_attempts' => 30, 'decay_seconds' => 60,
'auto_lock_threshold' => 10,
```

### 6.10 AccessToken Model

```php
// app/Modules/Auth/Domain/AccessTokens/Models/AccessToken.php
class AccessToken extends BaseModel {
    public static function generateFor(User $user, string $type, array $options = []): array;
    public static function verify(User $user, string $type, string $plainText): bool;
    public static function revokeFor(User $user, string $type): void;
    public static function revokeAllExpired(): int;
    public function asActivationToken(): ActivationToken;
    public function asAccessTokenState(): AccessTokenState;
}
```

### 6.11 ActivateAccountAction

```php
// app/Modules/Auth/Domain/Account/Actions/ActivateAccountAction.php
final class ActivateAccountAction extends BaseCommandAction {
    public function execute(ActivateAccountData $data): User;
    // resolve user → verify(type: activation) → revoke → Hash::make password → log
}
```

---

## 7. Design Decisions

Decisions are recorded rationale, not test rows — `Layer` / `Status` stay `—` per the template.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-AUTH-001 | Dual throttling: HTTP middleware plus application cache lockout | P0 | — | — |
| DD-AUTH-002 | Exponential backoff for lockout duration | P0 | — | — |
| DD-AUTH-003 | crc32b-hashed identifiers in cache keys | P0 | — | — |
| DD-AUTH-004 | Lockout state read before user lookup | P0 | — | — |
| DD-AUTH-005 | Session regeneration on login, invalidation plus CSRF rotation on logout | P0 | — | — |
| DD-AUTH-006 | Identical generic errors for every login failure mode | P0 | — | — |
| DD-AUTH-007 | Welcome and credential notices travel by queued events, never observers | P1 | — | — |
| DD-AUTH-008 | Login resolves stored role only; proxy resolves later at the policy layer | P0 | — | — |

### 7.1 Throttling & Lockout

#### DD-AUTH-001 — Two Budgets, Two Attackers

A single gatekeeper cannot watch two doors. The middleware counts posts per address and kills
floods for fractions of a millisecond each; the Action counts failures per identifier and
strangles campaigns that rotate addresses. Either layer alone invites its mirror attack — flood
past an identifier lock, rotate past an IP lock — so the design carries both and lets each do
the job it is cheapest at.

#### DD-AUTH-002 — Punishment That Compounds

Fixed lockouts are timetables: wait, retry, repeat, forever. The doubling curve instead converts
persistence into its own penalty, harmless to the mistyping student at ten seconds and ruinous
to the campaign at eighty-five minutes. Suspension-after-N was considered and rejected — it
trades attacker deterrence for administrator toil, locking honest users out until a human
intervenes.

#### DD-AUTH-003 — Opaque Key Suffixes

Embedding raw emails in cache keys would scatter PII through an operational store and invite
key-injection games via exotic usernames. The crc32b digest compresses any identifier to eight
hex characters — fixed length, opaque, combinable with registered prefixes. Collision theory
says two identifiers could share a budget once in billions of pairs; practice says the error
falls on the safe side, and the safe side is the point.

#### DD-AUTH-004 — Read First, Ask Later

The lockout lookup precedes the user query so every knock — registered or not — walks the same
initial path in the same time. Reversing the order would have made real users measurably slower
to reject than ghosts, handing probers a timing oracle. One cache get buys indistinguishability;
nothing else in the pipeline is that cheap for what it prevents.

### 7.2 Session & Errors

#### DD-AUTH-005 — Fresh Secrets at Every Transition

Login regenerates because fixation plants sessions before authentication; logout invalidates and
rotates because shared machines inherit whatever the previous occupant left. Regenerating only on
login would have protected arrival while abandoning departure — and on school hardware, departure
is where the next stranger sits down.

#### DD-AUTH-006 — One Sentence for All Failures

Helpful errors are enumerable errors: "unknown email" versus "wrong password" is a confirmed-
accounts list with extra steps. The single generic sentence trades a sliver of legitimate-user
convenience — mitigated by accepting both identifier forms — for closing the oracle entirely.
Internal reason codes keep the audit trail precise while the user sees only the mask.

### 7.3 Side-Effect & Role Architecture

#### DD-AUTH-007 — Events for Notices, Never Observers

Welcome and credential-change notifications fail all three observer gates: they fan out beyond
one model, they tolerate deferral (queued delivery survives slow mail servers), and nothing
requires them to complete before the response. An observer would have coupled delivery timing to
the request and rolled notices back with transactions that already committed conceptually. Queued
listeners keep the gate fast and the notices eventual — the correct reliability match for mail
that may legitimately arrive seconds later.

#### DD-AUTH-008 — Proxy Stays Out of the Session

Login authenticates a stored role; proxy authorizes an action under another role's rules, judged
per call through the registration bridge with both identities written to the audit properties.
Baking proxy into login would have made sessions ambiguous and every audit entry a riddle, while
multi-role assignment would have smeared the flat-RBAC model this system is built on. The
session says who you are; the policy decides whose stead you may act in — and never the reverse.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| HTTP throttle enforcement | 6th login post within 60s answers 429 | Feature test bursting one IP |
| Cache lockout trigger | Lockout flag appears on 10th consecutive failure | Cache inspection after synthetic campaign |
| Backoff progression | TTLs trace 10s → 20s → 40s … | TTL sampling across attempts 10–13 |
| Counter hygiene | Both counters gone after one success | Cache inspection post-login |
| Session rotation | Identifier differs across every login and logout | Feature test comparing session ids |
| Logout completeness | Auth cleared, session invalidated, token rotated | Triple assertion in logout test |
| Enumeration resistance | Identical responses across all failure modes | Response diff across failure matrix |
| Activation single-use | Revoked code never verifies again | Second verification attempt fails |
| Notification duality | Mail and in-app both delivered per change | Listener assertions on PasswordUpdated |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | `BaseCommandAction`, `ActionResponse`, `RejectedException`, SmartLogger |
| [rbac-and-authorization.md](T4B26-rbac-and-authorization.md) (T4B26) | Five stored roles, functional roles, policy layer, cross-role proxy |

### Build Guide

Login first — identifier detection, throttle counters, status gates, session regeneration — then
logout, then activation on the access-token primitive, then the credential-change listeners.
Every protected route and every role dashboard in later specs assumes the session this spec
establishes, so the gate must hold before anything stands behind it.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [password-reset.md](D9TKW-password-reset.md) | Self-service recovery for forgotten passwords behind the same throttle philosophy |
| 2 | [password-confirmation.md](CQVSK-password-confirmation.md) | Re-authentication gate for sensitive actions on established sessions |
| 3 | [school-profile.md](81SMS-school-profile.md) | Authenticated admin creates the school identity departments reference |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume superadmin lockout exemption observed in LoginAction comments (NFR-S10/G5) is tracked outside this spec until a spec amendment records it | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Identity & Auth phase, Full status
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — five stored roles, functional roles, cross-role proxy
- [Password reset](D9TKW-password-reset.md) — self-service recovery flow
- [Password confirmation](CQVSK-password-confirmation.md) — re-authentication gate
- [Account recovery slips](SHQ1J-account-recovery-slips.md) — slip-based recovery
- [Base classes](SE5Q9-base-classes.md) — Action, Entity, DTO contracts
- [Logging & error handling](89SRA-logging-and-error-handling.md) — SmartLogger dual-channel, RejectedException voice
- [Event system](NUCY3-event-system.md) — events vs observers discipline
- [Middleware pipeline](2CF4Y-middleware-pipeline.md) — throttle middleware budgets
- [ADR: flat RBAC with functional roles](../adr/adr-flat-rbac-with-functional-roles.md) — why roles stay flat
- [ADR: cross-role proxy](../adr/adr-cross-role-proxy.md) — proxy at the policy layer, never at login
- [ADR: exception hierarchy](../adr/adr-exception-hierarchy.md) — RejectedException for business-rule refusal
- [ADR: SmartLogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — masked, queryable audit trail
- [ADR: eloquent observers](../adr/adr-eloquent-observers.md) — three-gate test keeping notices in events
