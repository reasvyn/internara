# Authentication — Login, Throttling & Session Lifecycle

> **Last updated:** 2026-07-24 **Changes:** feat — split from login-and-dashboard.md;
> authentication, throttling, session lifecycle, logout, events, lockout recovery,
> account activation, access token lifecycle, credential change notifications

## Description

Complete specification of Internara's authentication system: login flow (email/username identifier detection), dual throttling (HTTP middleware rate limiting + cache-based lockout with exponential backoff), session lifecycle (regeneration on login/logout, fixation prevention), logout with CSRF rotation, domain events and listener pipeline, AccountStatus state machine reference, lockout recovery UX, account activation (token verification, attempt limiting, password setup), access token lifecycle (generate, verify, revoke with type-based TTLs), and credential change notifications (email + in-app). Covers the full path from unauthenticated visitor through credential validation to established session.

---

## 1. Problem Statements

### PS-1 — Credential Stuffing & Brute Force Attacks

Schools are high-value targets for credential stuffing — students and teachers reuse passwords
across services. Without rate limiting, an attacker can attempt thousands of password
combinations per minute against the login endpoint. A single throttling layer is insufficient:
HTTP-level throttling stops volumetric attacks from one IP but cannot prevent distributed
attacks targeting the same username from many IPs. The system must throttle at both the HTTP
layer and the application layer to prevent automated attacks.

### PS-2 — Session Fixation on Shared Computers

Schools operate on shared computers (computer labs, teacher offices, library kiosks). If the
session ID is not regenerated on login, an attacker who pre-set a session ID in the browser
can hijack the authenticated session once the victim logs in. This is a well-documented
OWASP vulnerability (Session Fixation, A07:2021). Session regeneration on every authentication
state change is mandatory.

### PS-3 — Account Lockout Recovery

When a user is locked out after 10 failed attempts, they need clear feedback about when they
can retry. Without exponential backoff, an attacker can simply wait the minimum lockout period
and retry indefinitely. With aggressive backoff, legitimate users who forgot their password
may be locked out for hours. The system must balance security (deterring attacks) with usability
(recovering from lockout in a reasonable time) and provide transparent feedback about the
remaining wait time.

### PS-4 — User Enumeration Prevention

Attackers probe login forms to determine which email addresses or usernames are registered.
If the system returns different error messages for "user not found" versus "wrong password,"
an attacker can enumerate valid accounts. The system must return identical responses for all
failure modes and perform lockout checks before user lookup to prevent timing-based enumeration.

### PS-5 — Login Identifier Ambiguity

Indonesian users commonly identify with either email or username. Forcing users to remember
which identifier they registered with creates friction. The system must accept both email and
username transparently, detecting the type automatically without requiring the user to select
a login mode.

### PS-6 — Account Activation

New users provisioned by school administrators start with `PROVISIONED` status and cannot
log in until they complete an activation flow. The system must provide a secure activation
process with token verification (time-limited, hashed storage), attempt limiting to prevent
brute-force token guessing, and a one-time password setup step. Without this, provisioned
accounts remain inaccessible and administrators must manually intervene for each new user.

### PS-7 — Credential Change Notifications

When a user changes their password, they need both email and in-app notification for security
awareness. If a password change occurs without notification, a compromised account could be
taken over silently — the legitimate user would not know their credentials were changed.
Dual-channel notifications (email + in-app) ensure the user is informed even if one channel
is unavailable or compromised.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide dual throttling: HTTP middleware (5 attempts/60s per IP) + cache lockout (10 failures → exponential backoff) |
| G2  | Support both email and username as login identifiers with automatic detection |
| G3  | Regenerate session ID on every authentication state change (login and logout) |
| G4  | Enforce exponential backoff on lockout: `10 * 2^(attempts - 10)` seconds |
| G5  | Provide transparent lockout recovery UX showing remaining wait time |
| G6  | Prevent user enumeration via identical error responses and timing-safe lockout checks |
| G7  | Dispatch domain events for login success/failure with SmartLogger integration |
| G8  | Enforce account status checks (PROVISIONED, SUSPENDED, ARCHIVED reject login) |
| G9  | Support secure account activation with token verification, attempt limiting, and password setup |
| G10 | Notify users via email and in-app notification when credentials change |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Multi-factor authentication (MFA) |
| NG2  | Social login (Google, GitHub, Microsoft) |
| NG3  | Passwordless login (magic links, WebAuthn, passkeys) |
| NG4  | OAuth2 / OpenID Connect provider functionality |
| NG5  | Password reset or account recovery flows (separate initiative) |
| NG6  | Remember-me / persistent token authentication beyond Laravel's built-in |
| NG7  | Multi-factor authentication enrollment beyond password (separate initiative) |

---

## 3. User Stories / Use Cases

### UC-1 — Student Logs In with Email

**Actor:** Student
**Preconditions:** Student account exists, status is `activated` or `verified`
**Flow:**
1. Student navigates to `/login`
2. `Login` Livewire component renders login form via `LoginForm` Form Object
3. Student enters email address and password
4. `LoginAction` detects identifier type via `FILTER_VALIDATE_EMAIL` — matches email
5. Lockout check runs: queries `auth.login.lockout:{hash}` cache key (BEFORE user lookup)
6. User looked up by email: `User::where('email', $identifier)->first()`
7. Account status checked: `$user->asApprentice()->status()->allowsLogin()` returns true
8. `Auth::attempt(['email' => $identifier, 'password' => $password])` validates credentials
9. On success: failed attempts and lockout counters cleared
10. Session regenerated: `session()->regenerate()` prevents session fixation
11. `LoginSucceeded` event dispatched with user model
12. `SendRoleWelcomeNotification` sends first-login welcome notification by role
13. Redirect to `/dashboard` → role-based dashboard routing
**Postconditions:** Student is authenticated, session is fresh, role-based dashboard loaded

### UC-2 — Account Locked After 10 Failed Attempts (Exponential Backoff)

**Actor:** Attacker (or student who forgot password)
**Preconditions:** No prior lockout for this identifier
**Flow:**
1. 10 consecutive failed login attempts for the same identifier
2. Each attempt increments `auth.login.attempts:{hash}` (24h TTL)
3. On 10th failure: lockout key `auth.login.lockout:{hash}` set to `10 * 2^(10-10) = 10 seconds`
4. 11th failure: lockout duration = `10 * 2^(11-10) = 20 seconds`
5. 15th failure: lockout duration = `10 * 2^(15-10) = 320 seconds` (~5 minutes)
6. After lockout expires: attempts counter still holds, next failure extends lockout
7. On successful login: both attempts and lockout counters cleared
**Postconditions:** Attacker is rate-limited with increasing delays; legitimate user can retry after lockout expires

### UC-3 — User Logs Out

**Actor:** Any authenticated user
**Preconditions:** User is logged in
**Flow:**
1. User clicks logout button in the UI
2. `POST /logout` dispatched to `AuthController::logout()`
3. `auth()->logout()` — clears authentication state
4. `session()->invalidate()` — destroys session data
5. `session()->regenerateToken()` — regenerates CSRF token
6. Redirect to `/login`
**Postconditions:** Session destroyed, CSRF token rotated, user returned to login page

### UC-4 — Account Locked, Wait Expires, Retry Succeeds

**Actor:** Student who forgot password
**Preconditions:** Account locked after 10+ failed attempts; lockout expired
**Flow:**
1. Student navigates to `/login`, sees lockout notification with remaining seconds
2. Lockout expires (e.g., 10 seconds after 10th failure)
3. Student enters correct credentials
4. `LoginAction` checks lockout: cache key expired, proceeds
5. Credentials validated successfully
6. Both attempts counter and lockout key cleared
7. Session regenerated, `LoginSucceeded` dispatched
**Postconditions:** User authenticated, counters reset, session fresh

### UC-5 — Login Attempt Blocked by HTTP Middleware

**Actor:** Automated script sending rapid requests
**Preconditions:** No prior HTTP throttling for this IP
**Flow:**
1. Script sends 5 login requests within 60 seconds from the same IP
2. `AuthThrottleMiddleware` tracks attempts using Laravel's `ThrottlesAttempts` trait
3. On 6th request: middleware returns 429 (Too Many Requests)
4. Response includes `Retry-After` header with seconds to wait
5. After 60 seconds: HTTP throttle resets
**Postconditions:** Volumetric attack from single IP blocked at HTTP layer

### UC-6 — Account Activation

**Actor:** Student (new user)
**Preconditions:** Student account exists with `PROVISIONED` status; activation token issued
**Flow:**
1. Student receives activation code (email or manual distribution)
2. Student navigates to activation form (Livewire component in `app/Auth/Account/Livewire/`)
3. Student enters activation code and desired password
4. `ActivateAccountAction` receives user, code, and password
5. `AccessToken::verify($user, 'activation', $code)` validates the token (not revoked, not expired, hash matches)
6. On valid token: `AccessToken::revokeFor($user, 'activation')` revokes the token
7. Password set via `Hash::make()` and saved to user model
8. `account_activated` logged via `$this->log()` with user as subject
9. Account transitions from `PROVISIONED` to `ACTIVATED`
**Postconditions:** Account activated, password set, activation token revoked, user can now log in

### UC-7 — Password Changed Notification

**Actor:** Any authenticated user
**Preconditions:** User is logged in and changes their password
**Flow:**
1. User initiates password change via profile or settings
2. Password change action validates and updates the password
3. `PasswordUpdated` event dispatched with user model
4. `SendPasswordChangedMail` listener (queued) receives event
5. Listener sends `CredentialChangedNotification('password')` via mail channel
6. `InvalidateSessionOnPasswordChange` listener (queued) receives event
7. Listener sends in-app notification via `SendsNotifications` interface with type `password_changed`
8. `CredentialChangedNotification` includes localized subject, greeting with user name, change type line, and optional support email from settings
**Postconditions:** Password updated, email notification sent, in-app notification sent, user aware of credential change

---

## 4. Functional Requirements

### Login — Authentication (FR-LI)

| ID   | Requirement |
| ---- | ----------- |
| FR-LI1 | System must accept both email and username as login identifiers |
| FR-LI2 | `LoginAction` must detect identifier type via `FILTER_VALIDATE_EMAIL` |
| FR-LI3 | System must look up user by the detected field (`email` or `username`) |
| FR-LI4 | System must check account status via `$user->asApprentice()->status()->allowsLogin()` |
| FR-LI5 | System must reject login for `PROVISIONED`, `SUSPENDED`, and `ARCHIVED` statuses |
| FR-LI6 | System must check `$user->asApprentice()->isLocked()` before credential validation |
| FR-LI7 | System must check `$user->asApprentice()->requiresSetup()` and reject if true |
| FR-LI8 | Successful login must call `session()->regenerate()` to prevent session fixation |

### Login — Throttling (FR-LT)

| ID   | Requirement |
| ---- | ----------- |
| FR-LT1 | `AuthThrottleMiddleware` must enforce 5 login attempts per 60 seconds per IP |
| FR-LT2 | `LoginAction` must enforce cache-based lockout: 10 failures → exponential backoff |
| FR-LT3 | Lockout duration formula: `10 * 2^(attempts - 10)` seconds |
| FR-LT4 | Failed attempt counter stored in `auth.login.attempts:{hash}` with 24h TTL |
| FR-LT5 | Lockout key stored in `auth.login.lockout:{hash}` with duration-based TTL |
| FR-LT6 | Successful login must clear both attempts and lockout counters |
| FR-LT7 | Lockout check must happen BEFORE user lookup (prevent user enumeration timing) |

### Login — Events & Logging (FR-LE)

| ID   | Requirement |
| ---- | ----------- |
| FR-LE1 | Failed login must dispatch `LoginFailed` event with identifier and reason |
| FR-LE2 | `LogLoginFailed` listener must log via SmartLogger with PII masking |
| FR-LE3 | Successful login must dispatch `LoginSucceeded` event with user model |
| FR-LE4 | `SendRoleWelcomeNotification` must send first-login welcome notification by role |
| FR-LE5 | Login success must be logged via `$this->log()` with user as subject |

### Login — Form & UI (FR-LF)

| ID   | Requirement |
| ---- | ----------- |
| FR-LF1 | `Login` Livewire component must use `LoginForm` Form Object for validation |
| FR-LF2 | `LoginForm` must validate: identifier required, password required |
| FR-LF3 | Login page must be accessible at `/login` with `guest` middleware |
| FR-LF4 | Login page must display flash messages for errors and lockout notifications |

### Logout (FR-LO)

| ID   | Requirement |
| ---- | ----------- |
| FR-LO1 | `POST /logout` must call `auth()->logout()` |
| FR-LO2 | Must call `session()->invalidate()` to destroy session data |
| FR-LO3 | Must call `session()->regenerateToken()` to rotate CSRF token |
| FR-LO4 | Must redirect to `/login` after logout |

### Account Activation (FR-ACT)

| ID     | Requirement |
| ------ | ----------- |
| FR-ACT1 | `ActivateAccountAction` must verify token via `AccessToken::verify($user, 'activation', $code)` |
| FR-ACT2 | Must revoke activation token after successful verification via `AccessToken::revokeFor()` |
| FR-ACT3 | Must set hashed password via `Hash::make()` on successful activation |
| FR-ACT4 | Must log `account_activated` via `$this->log()` |
| FR-ACT5 | Must throw `RejectedException` with localized message on invalid/expired token |
| FR-ACT6 | `AccountActivation` entity must expose `requiresActivation()`, `isTokenValid()`, `isTokenExpired()`, `hasExceededMaxAttempts()` |

### Access Token Lifecycle (FR-TOK)

| ID     | Requirement |
| ------ | ----------- |
| FR-TOK1 | `AccessToken::generateFor()` must create hashed token with configurable TTL per type (activation: 30d, recovery: 7d, default: 1d) |
| FR-TOK2 | `AccessToken::verify()` must check not revoked, not expired, hash matches; increment attempts on failure |
| FR-TOK3 | `AccessToken::revokeFor()` must set `revoked_at` timestamp for user+type |
| FR-TOK4 | `AccessToken::revokeAllExpired()` must bulk-revoke all expired unrevoked tokens |
| FR-TOK5 | `AccessTokenState` entity must expose `isExpired()`, `isRevoked()`, `isValid()`, `hasExceededMaxAttempts()` |
| FR-TOK6 | `ActivationToken` entity must expose `plainText()`, `tokenId()`, `expiresAt()` |

### Credential Change Notifications (FR-CRED)

| ID      | Requirement |
| ------- | ----------- |
| FR-CRED1 | `PasswordUpdated` event must be dispatched after successful password change |
| FR-CRED2 | `SendPasswordChangedMail` listener (queued) must send `CredentialChangedNotification` via mail channel |
| FR-CRED3 | `InvalidateSessionOnPasswordChange` listener (queued) must send in-app notification via `SendsNotifications` interface |
| FR-CRED4 | `CredentialChangedNotification` must include localized subject, greeting with user name, change type line, and optional support email from settings |

---

## 5. Non-Functional Requirements

### Security (NFR-S)

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | Session ID must be regenerated on every login and logout |
| NFR-S2 | CSRF token must be regenerated on logout |
| NFR-S3 | Login failures must not reveal whether the identifier exists (generic error message) |
| NFR-S4 | Lockout duration must increase exponentially to deter automated attacks |
| NFR-S5 | Lockout check must occur before user lookup to prevent timing-based user enumeration |
| NFR-S6 | Cache keys must use `crc32b` hash of identifier (not raw value) to prevent key injection |

### Performance (NFR-P)

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | Login action must complete in < 500ms (cache miss) or < 100ms (cache hit) |
| NFR-P2 | HTTP throttle check must add < 5ms overhead per request |
| NFR-P3 | Cache-based lockout check must add < 10ms overhead per request |

### Usability (NFR-U)

| ID    | Requirement |
| ----- | ----------- |
| NFR-U1 | Lockout message must include the number of seconds to wait |
| NFR-U2 | Error messages must be identical for invalid credentials and non-existent users |
| NFR-U3 | Login form must support both email and username without requiring a mode selector |

### Reliability (NFR-R)

| ID    | Requirement |
| ----- | ----------- |
| NFR-R1 | Login must gracefully handle database unavailability (reject, don't crash) |
| NFR-R2 | Login must gracefully handle cache unavailability (skip lockout check, allow login attempt) |

### Accessibility (NFR-A)

| ID    | Requirement |
| ----- | ----------- |
| NFR-A1 | Login form must meet WCAG 2.1 Level AA |
| NFR-A2 | Login form must have associated labels for all inputs (not just placeholders) |
| NFR-A3 | Lockout and error messages must be announced to screen readers via `aria-live` regions |
| NFR-A4 | Login form must be navigable via keyboard alone (tab order follows logical reading order) |

### Localization (NFR-L)

| ID    | Requirement |
| ----- | ----------- |
| NFR-L1 | All user-facing strings (login form, error messages, lockout notifications) must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-L3 | Lockout duration display must use localized number formatting |

### Maintainability (NFR-M)

| ID    | Requirement |
| ----- | ----------- |
| NFR-M1 | All PHP files must declare `strict_types=1` |
| NFR-M2 | Login Actions must extend appropriate base classes (BaseCommandAction, BaseReadAction) |
| NFR-M3 | Events must follow Action Triad convention: dispatched by Actions, not Livewire |

---

## 6. API / Data Contracts

### 6.1 LoginData DTO

```php
// app/Auth/Login/Data/LoginData.php
final readonly class LoginData extends BaseData
{
    public function __construct(
        public string $identifier,
        public string $password,
        public bool $remember = false,
    ) {}
}
```

### 6.2 LoginAction

```php
// app/Auth/Login/Actions/LoginAction.php
final class LoginAction extends BaseCommandAction
{
    public function execute(string $identifier, string $password, bool $remember = false): Authenticatable;

    // Pipeline:
    // 1. DTO creation (LoginData)
    // 2. Lockout check — cache key auth.login.lockout:{crc32b(identifier)}
    // 3. User lookup — User::where(detectedField, identifier)->first()
    // 4. Account status — $user->asApprentice()->status()->allowsLogin()
    // 5. Credential validation — Auth::attempt()
    // 6. On success: clear attempts + lockout counters
    // 7. Session regeneration — session()->regenerate()
    // 8. Dispatch LoginSucceeded / LoginFailed events
}
```

### 6.3 AuthThrottleMiddleware

```php
// app/Auth/Login/Http/Middleware/AuthThrottleMiddleware.php
// HTTP layer: 5 login attempts per 60 seconds per IP
// General auth: 30 attempts per 60 seconds
// Config keys: config('auth.throttle.login_max_attempts'), config('auth.throttle.login_decay_seconds')
// Returns 429 with Retry-After header when exceeded
```

### 6.4 Cache Key Schema

```
auth.login.attempts:{crc32b(identifier)}  → int (failure count), TTL: 24 hours
auth.login.lockout:{crc32b(identifier)}  → 1 (flag), TTL: lockout duration in seconds
```

The `crc32b` hash provides:
- Fixed-length cache keys regardless of identifier length
- Prevention of special-character injection in cache keys
- One-way obscuration — cache access does not reveal the original identifier

### 6.5 AccountStatus State Machine Reference

```
States:
  PROVISIONED → ACTIVATED, SUSPENDED
  ACTIVATED   → VERIFIED, SUSPENDED, ARCHIVED
  VERIFIED    → RESTRICTED, SUSPENDED, ARCHIVED, INACTIVE
  PROTECTED   → (terminal)
  RESTRICTED  → VERIFIED, SUSPENDED, ARCHIVED
  SUSPENDED   → ACTIVATED, VERIFIED, ARCHIVED
  INACTIVE    → VERIFIED, ARCHIVED, SUSPENDED
  ARCHIVED    → (terminal)

Login-eligible statuses (allowsLogin):
  ACTIVATED, VERIFIED, PROTECTED, RESTRICTED, INACTIVE

Terminal states (isTerminal):
  PROTECTED, ARCHIVED
```

### 6.6 Events

| Event | Dispatched By | Payload | Listener |
| ----- | ------------- | ------- | -------- |
| `LoginFailed` | `LoginAction` | `identifier: string, reason: string` | `LogLoginFailed` |
| `LoginSucceeded` | `LoginAction` | `user: Authenticatable` | `SendRoleWelcomeNotification` |

### 6.7 Listeners

| Listener | Event | Queued | Action |
| -------- | ----- | ------ | ------ |
| `LogLoginFailed` | `LoginFailed` | No | SmartLogger with PII masking |
| `SendRoleWelcomeNotification` | `LoginSucceeded` | No | First-login welcome notification by role |

### 6.8 Routes

```php
// routes/web/auth.php
Route::get('/login', Login::class)
    ->middleware(['guest', 'auth.throttle'])
    ->name('login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
```

### 6.9 Config Values

```php
// config/auth.php
return [
    'throttle' => [
        'login_max_attempts' => 5,   // HTTP middleware: attempts per window
        'login_decay_seconds' => 60, // HTTP middleware: window duration in seconds
    ],
];
```

### 6.10 Lockout Duration Table

| Failed Attempts | Lockout Duration | Formula |
| --------------- | ---------------- | ------- |
| 1–9 | 0 seconds (no lockout) | — |
| 10 | 10 seconds | `10 * 2^(10-10)` |
| 11 | 20 seconds | `10 * 2^(11-10)` |
| 12 | 40 seconds | `10 * 2^(12-10)` |
| 13 | 80 seconds | `10 * 2^(13-10)` |
| 14 | 160 seconds (~2.5 min) | `10 * 2^(14-10)` |
| 15 | 320 seconds (~5 min) | `10 * 2^(15-10)` |
| 20 | 5120 seconds (~85 min) | `10 * 2^(20-10)` |
| 25 | ~9 hours | `10 * 2^(25-10)` |

### 6.11 AccessToken Model

```php
// app/Auth/AccessTokens/Models/AccessToken.php
class AccessToken extends BaseModel {
    // Types: activation (30d), recovery (7d), default (1d)
    public static function generateFor(User $user, string $type, array $options = []): array;
    public static function verify(User $user, string $type, string $plainText): bool;
    public static function revokeFor(User $user, string $type): void;
    public static function revokeAllExpired(): int;
    public function asActivationToken(): ActivationToken;
    public function asAccessTokenState(): AccessTokenState;
}
```

### 6.12 ActivateAccountAction

```php
// app/Auth/Account/Actions/ActivateAccountAction.php
final class ActivateAccountAction extends BaseCommandAction {
    public function execute(User $user, string $code, string $password): User;
    // Pipeline: verify token → revoke → set password → log
}
```

### 6.13 Credential Change Events & Listeners

| Event/Listener | Trigger | Channel | Action |
| -------------- | ------- | ------- | ------ |
| `PasswordUpdated` | Password change | n/a | Dispatched by password change action |
| `SendPasswordChangedMail` | `PasswordUpdated` | mail | Sends `CredentialChangedNotification` |
| `InvalidateSessionOnPasswordChange` | `PasswordUpdated` | in-app | Sends `password_changed` notification via `SendsNotifications` |

---

## 7. Design Decisions

### DD-1 — Dual Throttling (HTTP Middleware + Cache Lockout)

**Decision:** Two independent throttling layers: `AuthThrottleMiddleware` (HTTP, IP-based)
and `LoginAction` cache lockout (application, identifier-based).
**Rationale:** HTTP throttling prevents volumetric attacks (thousands of requests from one IP).
Cache lockout prevents credential stuffing across IPs (same username targeted from many IPs).
Neither alone is sufficient — IP-based throttling can be bypassed with distributed bots;
identifier-based throttling can be bypassed with IP rotation.
**Trade-off:** Two independent systems to maintain. Mitigated by clear separation of concerns:
middleware handles HTTP-level, Action handles application-level.
**Rejected alternative:** Single throttling layer (either IP-only or identifier-only) —
insufficient against modern distributed attacks.

### DD-2 — Exponential Backoff for Lockout

**Decision:** Lockout duration increases exponentially: `10 * 2^(attempts - 10)` seconds.
**Rationale:** Linear backoff (e.g., fixed 5 minutes) allows attackers to simply wait and retry
at a constant rate. Exponential backoff makes repeated attacks progressively more expensive.
After 15 attempts, the lockout is ~5 minutes; after 20 attempts, ~85 minutes. This provides
strong deterrence while keeping initial lockouts short for legitimate users who mistype.
**Trade-off:** Legitimate users who forget their password and retry repeatedly face increasing
delays. Mitigated by clear lockout messages showing the exact wait time, and the short initial
lockout (10 seconds) for the first threshold breach.
**Rejected alternative:** Fixed lockout duration — too lenient for sustained attacks.
**Rejected alternative:** Account suspension after N failures — too aggressive; locks out
legitimate users and requires admin intervention.

### DD-3 — Identifier Hash for Cache Keys

**Decision:** Cache keys use `crc32b` hash of the identifier, not the raw identifier.
**Rationale:** Prevents cache key injection (e.g., special characters in username that could
manipulate cache namespaces) and keeps cache keys a fixed 8-character hex length. The hash is
one-way — an attacker who gains cache access cannot recover the original identifier.
**Trade-off:** CRC32B is not collision-resistant (theoretical ~1 in 4 billion collision rate),
but for rate-limiting purposes, a collision means two different identifiers share a lockout —
an acceptable false positive that errs on the side of security.
**Rejected alternative:** Raw identifier as cache key — vulnerable to injection and exposes
identifiers in cache.

### DD-4 — Lockout Check Before User Lookup

**Decision:** `LoginAction` checks the lockout cache key BEFORE querying the database for the
user.
**Rationale:** If the lockout check happens after user lookup, an attacker can distinguish
between "user not found" (fast, no DB query needed beyond the miss) and "user found, wrong
password" (slightly slower due to hash comparison). Performing lockout check first means every
request — regardless of whether the identifier exists — takes the same code path up to the
lockout gate, preventing timing-based user enumeration.
**Trade-off:** Legitimate users whose identifier is not registered still experience the
lockout check overhead. Mitigated by the check being a single cache `get()` (< 10ms).

### DD-5 — Session Regeneration on Both Login and Logout

**Decision:** Call `session()->regenerate()` on login and `session()->invalidate()` +
`session()->regenerateToken()` on logout.
**Rationale:** Login regeneration prevents session fixation attacks (OWASP A07:2021). Logout
invalidation ensures the old session cannot be reused after the user explicitly ends their
session. CSRF token rotation on logout prevents cross-site request forgery using stale tokens.
**Trade-off:** Regeneration destroys existing session data. For login, this is benign (user
just authenticated). For logout, user data is already cleared by `auth()->logout()`.
**Rejected alternative:** Regenerate only on login — leaves stale session vulnerable after
logout on shared computers.

### DD-6 — Generic Error Messages for All Login Failures

**Decision:** All login failures return identical error messages regardless of whether the
identifier exists, the password is wrong, or the account is locked.
**Rationale:** Specific error messages ("account not found" vs "incorrect password") allow
user enumeration — an attacker can probe for valid email addresses. Generic messages eliminate
this attack vector entirely.
**Trade-off:** Slightly worse UX for legitimate users (cannot distinguish "forgot email" from
"forgot password"). Mitigated by the login form accepting both email and username, reducing
the likelihood of identifier confusion.

---

## 8. Success Metrics

### 8.1 Throttle Enforcement

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| HTTP throttle enforcement | 5 attempts/60s per IP | `AuthThrottleMiddleware` rejects 6th request with 429 |
| Cache lockout trigger | 10 failures → lockout | `auth.login.lockout:{hash}` key created on 10th failure |
| Lockout duration doubling | Duration doubles each failure after 10 | Cache TTL inspection: 10s → 20s → 40s → ... |
| Counter clearance on success | Both counters cleared | `auth.login.attempts:{hash}` and `auth.login.lockout:{hash}` removed |

### 8.2 Session Security

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Session regeneration on login | 100% of logins | `session()->regenerate()` in `LoginAction` |
| Session invalidation on logout | 100% of logouts | `session()->invalidate()` in `AuthController::logout()` |
| CSRF token rotation on logout | 100% of logouts | `session()->regenerateToken()` in `AuthController::logout()` |
| Session fixation prevention | 0 successful fixation attacks | Session ID changes on every authentication state change |

### 8.3 User Enumeration Prevention

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Lockout check before user lookup | 100% of login attempts | `LoginAction::checkLockout()` called before `User::where()` |
| Generic error messages | Identical for all failure modes | No "user not found" vs "wrong password" distinction |
| Timing consistency | < 5ms variance between valid/invalid identifiers | Lockout check + cache miss overhead uniform |

### 8.4 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Login action (cache miss) | < 500ms | Full pipeline: DTO → lockout → lookup → attempt → session |
| Login action (cache hit) | < 100ms | Lockout check skipped, user exists in DB |
| HTTP throttle overhead | < 5ms per request | Middleware adds minimal latency |
| Cache lockout check | < 10ms per request | Single `Cache::get()` call |

### 8.5 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Lockout feedback | Shows seconds to wait | `auth.throttle` translation key with seconds |
| Login form usability | Both email and username accepted | `LoginForm` identifier field accepts either |
| Logout completeness | Session + CSRF invalidated | Triple cleanup: `logout()` + `invalidate()` + `regenerateToken()` |
| Accessibility | WCAG 2.1 Level AA | Labels, `aria-live` regions, keyboard navigation |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseAuthenticatable`, `PasswordRules`, exception hierarchy |
| [rbac-and-authorization.md](rbac-and-authorization.md) | Role assignment on login, `BasePolicy` auto-allow for super_admin |

### Build Guide
After implementing this spec, the system has login, account activation (token verification, attempt limiting, password setup), credential change notifications (email + in-app), password reset, recovery slips, account lockout, and session management. The access token lifecycle supports activation tokens (30-day TTL), recovery tokens (7-day TTL), and general tokens (1-day TTL) with hashed storage, attempt tracking, and revocation. Every protected route and Livewire component depends on the authentication state established here. The next step is to build the school profile, which stores the school identity that departments and companies reference.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [school-profile.md](school-profile.md) | Authenticated admin creates school profile; profile data used in documents and certificates |

---

## Quick References

- `app/Auth/Login/Actions/LoginAction.php` — login pipeline with dual throttling
- `app/Auth/Login/Data/LoginData.php` — login DTO
- `app/Auth/Login/Events/LoginFailed.php` — failed login event
- `app/Auth/Login/Events/LoginSucceeded.php` — successful login event
- `app/Auth/Login/Http/Middleware/AuthThrottleMiddleware.php` — HTTP rate limiting
- `app/Auth/Login/Listeners/LogLoginFailed.php` — SmartLogger integration
- `app/Auth/Login/Listeners/SendRoleWelcomeNotification.php` — first-login welcome
- `app/Auth/Login/Livewire/Login.php` — login Livewire component
- `app/Auth/Login/Livewire/Forms/LoginForm.php` — login form validation
- `app/User/Http/Controllers/AuthController.php` — logout handler
- `app/User/Enums/AccountStatus.php` — 8-state status machine with transition guards
- `config/auth.php` — throttle configuration
- `routes/web/auth.php` — login/logout routes
- `docs/modules/auth.md` — Auth module overview
- `docs/modules/auth-reference.md` — Auth module technical reference
- `app/Auth/AccessTokens/Models/AccessToken.php` — token lifecycle (generate, verify, revoke)
- `app/Auth/AccessTokens/Entities/AccessTokenState.php` — token validity state
- `app/Auth/AccessTokens/Entities/ActivationToken.php` — activation token value object
- `app/Auth/Account/Actions/ActivateAccountAction.php` — account activation pipeline
- `app/Auth/Account/Entities/AccountActivation.php` — activation state entity
- `app/Auth/Password/Events/PasswordUpdated.php` — credential change event
- `app/Auth/Password/Listeners/SendPasswordChangedMail.php` — email notification on password change
- `app/Auth/Password/Listeners/InvalidateSessionOnPasswordChange.php` — in-app notification on password change
- `app/Auth/Notifications/CredentialChangedNotification.php` — credential change mail notification
- **Related specs:** [registration.md](registration.md) — account provisioning and registration
