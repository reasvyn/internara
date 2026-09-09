# Middleware Pipeline — HTTP Request Processing Chain

> **Spec ID:** 2CF4Y

## Description

HTTP middleware pipeline for Internara: core and module middleware classes, their execution
order, registration mechanism, rate-limit budgets, and the rule for adding new middleware. Every
request passes through the same security and context-enrichment layers in a documented order.

Header values applied by this pipeline are contracted in
[security-headers](1PGM4-security-headers.md); role semantics come from
[rbac-and-authorization](T4B26-rbac-and-authorization.md). Middleware base implementations live
in [base-classes](SE5Q9-base-classes.md), and the global rate-limit budgets originate in
[QLHDO](QLHDO-project-initialization.md) §10 (FR-GLB-005).

---

## 1. Problem Statements

### PS-1 — Middleware Order Affects Security

Middleware executes in registration order. If role checks run before authentication or
throttling, unauthenticated users bypass rate limiting and identity context. Incorrect ordering
creates security vulnerabilities.
**→ Requirement:** FR-MID-001/002 (global order, LogContext first), FR-MID-003 (headers before handling).

### PS-2 — No Guideline for New Middleware

When a developer needs to add request-level processing (e.g., maintenance mode check, API
versioning), there is no rule defining where it fits in the pipeline or how to register it.
**→ Requirement:** FR-MID-012 (module registration without core changes), DD-MID-001/003.

### PS-3 — Rate Limiting Inconsistency

Rate limiters defined in `AppServiceProvider` but not documented leave developers guessing which
limiter applies to which route group — and per-endpoint auth limits drift into ad-hoc numbers.
**→ Requirement:** FR-MID-010/011 (named limiters + canonical per-endpoint budgets from QLHDO FR-GLB-005).

### PS-4 — Forgery and Session-Auth Gaps

A Livewire-only frontend still posts through HTTP: without enforced CSRF validation and a
stated session-auth position, state-changing requests are exposed to cross-site forgery.
**→ Requirement:** FR-MID-013 (CSRF on web, explicit setup exception), FR-MID-004 (Authenticate position).

---

## 2. Goals & Non-Goals

### Goals

- **Make middleware execution order explicit and documented** — one ordered pipeline, no implied positions. *Why:* order is security-relevant; undocumented order rots into PS-1 vulnerabilities.
- **Apply core middleware globally to all requests** — logging context, security headers, CSRF, auth. *Why:* cross-cutting guarantees must not depend on each route remembering to opt in.
- **Scope module middleware to its route group** — setup gating, locale, role checks where they belong. *Why:* global application of group-specific logic creates false denials and wasted work.
- **Keep rate limiters reusable and named** — `admin` and `global` plus canonical per-endpoint budgets. *Why:* named policies are auditable in one place instead of scattered inline numbers.
- **Let new middleware slot in without modifying existing classes** — alias registration in route files. *Why:* pipeline growth must not require editing (and risk breaking) working middleware.
- **Enforce CSRF on state-changing web requests** — Laravel forgery validation active, session-cookie auth positioned in the pipeline. *Why:* Livewire posts are HTTP posts; forgery protection is non-negotiable per NFR-SEC-004.

### Non-Goals

- **Middleware for REST API versioning**. *Why:* no API layer exists (Livewire-only frontend per [tech-stack](FB792-tech-stack.md)).
- **WebSocket middleware**. *Why:* broadcasting stays on the log driver; no socket surface exists.
- **Middleware hot-swapping at runtime**. *Why:* pipeline is registration-time configuration; runtime mutation would defeat auditability.
- **Request/response transformation middleware**. *Why:* payload shaping belongs in Form Objects/DTOs and `ActionResponse`, not in the transport layer.

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-MID-001 | Developer adds new core middleware at the correct pipeline position | P0 | A | Full |
| UC-MID-002 | Developer adds module-specific middleware scoped to its route group | P0 | A | Full |
| UC-MID-003 | Developer applies rate limiting from the named limiters and canonical budgets | P0 | F | Full |

### 3.1 Pipeline Workflows

#### UC-MID-001 — Developer Adds New Core Middleware

When an SMK needed a maintenance-mode banner during enrollment-week database work, the fix had to run on every request without disturbing authentication. The developer creates the class in `app/Modules/Core/Http/Middleware/`, registers it in `bootstrap/app.php` as an alias or at a global position, slots it into the pipeline relative to the existing order in §6.1, then proves the neighbors still execute in sequence. The new middleware ends up running on every request at the correct position, under FR-MID-001 and FR-MID-012 with DD-MID-001 fixing the first-slot rationale.

#### UC-MID-002 — Developer Adds Module-Specific Middleware

For route-group work like setup gating, the class lives beside its module in `{Module}/Http/Middleware/` or the domain path such as `Setup/Domain/Installation/Http/Middleware/`, never in Core. Registration happens as an alias inside the module's own route file, and the route group applies it with `->middleware()`, so the check runs only on targeted routes and global traffic never pays for it. FR-MID-012 owns that extension path.

#### UC-MID-003 — Developer Configures Rate Limiting

The edge that keeps biting is the tempting inline number: a developer throttles a new admin route at 100 per minute because it feels safe, and the audit now holds two truths. The disciplined path starts from the named limiter — `admin` or `global` — or a canonical per-endpoint budget from §6.4, applies it with `->middleware('throttle:admin')` on the route group or through the governing spec's enforcement point for auth endpoints, then verifies with test requests using those canonical values, never ad-hoc numbers. The route ends rate-limited with auditable limits under FR-MID-010 and FR-MID-011, with per-endpoint owners linked in §6.4.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB) · `F` = Feature
(Action/Livewire/Console, real DB) · `B` = Browser (E2E journey) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-MID-001 | Core middleware MUST apply to all HTTP requests globally, in the documented §6.1 order | P0 | A | Full |
| FR-MID-002 | `LogContextMiddleware` MUST attach `request_id` (UUID), `user_id`, `user_role`, `duration_ms` (plus method, URL, IP) to the log context | P0 | F | Full |
| FR-MID-003 | `SecurityHeadersMiddleware` MUST set CSP, HSTS, X-Frame-Options, Referrer-Policy, Permissions-Policy headers before route handling | P0 | F | Full |
| FR-MID-004 | Laravel built-in `Authenticate` and CSRF validation MUST hold their §6.1 positions (after context/headers, before role and locale) | P0 | A | Full |
| FR-MID-005 | `AuthThrottleMiddleware` MUST enforce login rate limiting per config keys `auth.throttle.login_max_attempts` (5) / `login_decay_seconds` (60) — 5 attempts/60s per IP | P0 | F | Full |
| FR-MID-006 | `CheckRoleMiddleware` MUST verify the user has the required role before route execution | P0 | F | Full |
| FR-MID-007 | `SetLocaleMiddleware` MUST set the locale from user preference or session, and persist the selection for subsequent requests | P1 | F | Full |
| FR-MID-008 | `ProtectSetupRouteMiddleware` MUST block setup routes after installation is complete | P0 | F | Full |
| FR-MID-009 | `RequireSetupAccessMiddleware` MUST require a valid setup access token | P0 | F | Full |
| FR-MID-010 | `AppServiceProvider` MUST register the route-level named limiters `admin` (60/min per user) and `global` (30/min per IP) | P0 | F | Full |
| FR-MID-011 | Per-endpoint auth limits (login/forgot/reset/recovery/confirm) are enforced at the Action/Component layer in their governing specs and MUST use the canonical §6.4 values, never ad-hoc numbers | P0 | F | Full |
| FR-MID-012 | Module middleware MUST be registrable via route files without modifying core | P0 | A | Full |
| FR-MID-013 | CSRF validation MUST be active on web routes (setup excepted) with session-cookie auth; no token/Sanctum layer exists — the frontend is Livewire-only per NFR-SEC-004 | P0 | F | Full |

### 4.1 Global Pipeline

#### FR-MID-001 — Global pipeline with fixed order

The fixed order exists because every permutation was once tried and each broke something: role checks before authentication let anonymous users probe admin routes, headers after handling missed every error page. Now `bootstrap/app.php` pins one sequence — LogContext, then SecurityHeaders, then CSRF, then Authenticate, then CheckRole, then SetLocale, then the handler in §6.1 — applied to all HTTP requests globally. Review of `bootstrap/app.php` plus header and context feature tests prove the order effects at layer `A`.

#### FR-MID-002 — Request log context

Lose the request identifier and a production incident becomes seven disconnected log lines nobody can join. `LogContextMiddleware` attaches the full payload from §6.5 — `request_id` as a UUID v4 generated per request, plus `user_id`, `user_role`, `duration_ms`, method, URL, and IP — so every downstream line stays correlatable. If logging infrastructure itself goes down, context assembly degrades silently and the request still succeeds per NFR-MID-003. A feature test asserts the context keys on a logged request at layer `F`.

#### FR-MID-003 — Security headers before handling

An SMK in Solo once passed its homepage security scan yet served its 404 page with no headers at all, because headers had been added in the Blade layout instead of the pipeline. This row owns the position, not the values: `SecurityHeadersMiddleware` sets CSP, HSTS, X-Frame-Options, Referrer-Policy, and Permissions-Policy before route handling, with values contracted in [security-headers](1PGM4-security-headers.md). A feature test asserts the headers on normal, redirect, and error responses alike at layer `F`.

#### FR-MID-004 — Auth and CSRF positions

A request enters with context attached and headers already set, then meets Laravel built-in `Authenticate` and CSRF validation in that sheltered position — late enough that denials are logged and hardened, early enough that role and locale downstream already have an identity to work with. The mechanics live in FR-MID-013. Review of `bootstrap/app.php` confirms the two slots hold at layer `A`.

### 4.2 Module Middleware Behaviors

#### FR-MID-005 — Login throttling

The credential-stuffing edge is a single IP hammering logins at dawn before staff arrive. `AuthThrottleMiddleware` keys on IP and enforces 5 attempts per 60 seconds per the canonical values in §6.4, driven by config keys `auth.throttle.login_max_attempts` holding 5 and `login_decay_seconds` holding 60, with [authentication](YB7RG-authentication.md) as governing spec. A feature test exceeds five attempts inside sixty seconds and proves the sixth is throttled, at layer `F`.

#### FR-MID-006 — Role gate

Route protection once lived inside each Livewire component, and every forgotten check was a silent privilege grant. `CheckRoleMiddleware` pulled that decision into one route-level enforcement point that verifies the required role before execution and returns 403 on failure, while role semantics themselves stay owned by [rbac-and-authorization](T4B26-rbac-and-authorization.md). Feature tests walk allow and deny per role at layer `F`.

#### FR-MID-007 — Locale resolution and persistence

Without persistence every click renegotiates language, and an Indonesian teacher flips to English on each navigation because the session forgot. `SetLocaleMiddleware` resolves once in strict order — authenticated user preference, then session, then application default — and persists the selection so the next request inherits it without renegotiation. A feature test asserts a locale switch survives across requests at layer `F`.

#### FR-MID-008 — Setup route protection

A curious student at an SMK in Bogor once guessed the `/setup` URL months after go-live and reached a live installer pointed at the production database. `ProtectSetupRouteMiddleware` closes that door: after installation completes, setup routes become unreachable and reinstall-via-URL is impossible. The feature test hits a setup route post-install and proves it is blocked, at layer `F`.

#### FR-MID-009 — Setup access token

During installation the request first meets `RequireSetupAccessMiddleware`, which demands a valid setup access token before any setup screen renders — network reachability alone never suffices. The feature test walks the three outcomes in sequence, missing token rejected, invalid token rejected, valid token admitted, at layer `F`.

### 4.3 Rate Limiting

#### FR-MID-010 — Named route limiters

The shared-lab edge is thirty students behind one school IP hitting the dashboard at once, which a naive per-user limiter would wave through and a global one must absorb fairly. `AppServiceProvider` registers two named limiters via `RateLimiter::for()` — `admin` at 60 per minute per user for settings and user management, `global` at 30 per minute per IP for general authenticated routes per QLHDO §10 — applied with `->middleware('throttle:admin')` or `throttle:global` on route groups. Limiter registration review plus a throttling feature test holds both at layer `F`.

#### FR-MID-011 — Canonical per-endpoint budgets

Per-endpoint limits once drifted because each auth spec invented its own numbers, and forgot-password ended up stricter than login on some deploys. The §6.4 table stopped that by becoming the single registration point for the QLHDO §10 budgets under FR-GLB-005: login 5 per 60 seconds, forgot 3 per 3600, reset 5 per 300, recovery 3 per 300, plus password confirmation 5 per 300. Enforcement mechanics stay in the governing specs — login through `AuthThrottleMiddleware`, the rest through inline `RateLimiter` in Actions and Components — and each governing spec's throttling test uses these exact values at layer `F`.

### 4.4 Request Integrity & Extensibility

#### FR-MID-012 — Extension without core edits

Editing Core to add a module check is how regressions ship: one touched line in `bootstrap/app.php` reorders the whole pipeline. New middleware therefore ships as an alias applied in the module's own route file, leaving `bootstrap/app.php` and every existing middleware untouched. Only genuinely global middleware such as maintenance mode registers in `bootstrap/app.php`, at an explicit §6.1-relative position with a DD note. Alias registration review enforces the boundary at layer `A`.

#### FR-MID-013 — CSRF on web, session auth, no token layer

A Livewire-only frontend still posts through HTTP, and an SMK lab browser with a stale session proved that forgery protection cannot be assumed away. `preventRequestForgery` stays active on web routes with only the `setup` exception, since setup runs pre-install without sessions, and authentication remains session-cookie via Laravel defaults. No Sanctum or token guard exists because there is no API surface, per the [tech-stack](FB792-tech-stack.md) non-goals. Review of `bootstrap/app.php` plus a CSRF rejection feature test holds the line at layer `F`.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-MID-001 | Security headers MUST NOT break Vite hot module replacement in development | HMR works with headers active | P0 | F | Full |
| NFR-MID-002 | Rate limit counters MUST use the cache driver (not the database) for performance | 0 DB-backed counters | P1 | A | Full |
| NFR-MID-003 | `LogContextMiddleware` MUST NOT fail the request if logging infrastructure is down | 0 logging-caused 500s | P0 | F | Full |

### 5.1 Pipeline Resilience

#### NFR-MID-001 — Headers coexist with HMR

In a local run the request passes the full header middleware and still has to reach the Vite dev server for hot reload. The development CSP relaxations contracted in [security-headers](1PGM4-security-headers.md) under FR-SEC-010 carve that path, and this row asserts only the observable outcome: HMR works with headers active. A manual smoke in the local environment confirms it at layer `F`.

#### NFR-MID-002 — Cache-backed counters

The slow edge is a database-backed throttle table under a login flood: every attempt writes a row, and the guard meant to protect authentication becomes the bottleneck that topples it. Counters therefore resolve against the cache driver, never the database, holding zero DB-backed counters. Limiter definitions review confirms every `RateLimiter::for()` closure points at cache, at layer `A`.

#### NFR-MID-003 — Logging never breaks requests

Observability must never outrank availability — a full disk on the log volume once turned every admin page into a 500 before this rule existed. `LogContextMiddleware` therefore never fails the request when logging infrastructure is down, holding zero logging-caused 500s. A feature test with logging disabled and then broken asserts the response still succeeds at layer `F`.

---

## 6. API / Data Contracts

### 6.1 Middleware Execution Order (Global)

```
Request
  → LogContextMiddleware (attach request context)
  → SecurityHeadersMiddleware (set security headers)
  → ValidateCsrfToken (Laravel built-in)
  → Authenticate (Laravel built-in)
  → CheckRoleMiddleware (if route requires role)
  → SetLocaleMiddleware (if route requires locale)
  → Route Handler
```

### 6.2 Module Middleware (Per Route Group)

| Module | Middleware | Applies To |
|--------|-----------|------------|
| Auth | `AuthThrottleMiddleware` | Login routes |
| Auth | `CheckRoleMiddleware` | All protected routes |
| Settings | `SetLocaleMiddleware` | All settings routes |
| Setup | `ProtectSetupRouteMiddleware` | All setup routes |
| Setup | `RequireSetupAccessMiddleware` | All setup routes |

### 6.3 Rate Limiters (Route-Level Named)

| Name | Limit | Use Case |
|------|-------|----------|
| `admin` | 60/min per user | Admin actions (settings, user management) |
| `global` | 30/min per IP | General authenticated routes (QLHDO §10) |

Registered in `AppServiceProvider` via `RateLimiter::for()` and applied with `->middleware('throttle:admin')` / `throttle:global` on route groups.

### 6.4 Auth Endpoint Limits (Canonical Values)

| Endpoint | Limit | Enforcement Point | Governing Spec |
|----------|-------|-------------------|----------------|
| Login | 5/60s per IP | `AuthThrottleMiddleware` (config `auth.throttle.login_max_attempts`/`login_decay_seconds`) | [authentication](YB7RG-authentication.md) |
| Forgot-password link | 3/3600s per email+IP | `SendPasswordResetLinkAction` (inline `RateLimiter`) | [password-reset](D9TKW-password-reset.md) |
| Password reset | 5/300s per email+IP | `ResetPasswordAction` (inline `RateLimiter`) | [password-reset](D9TKW-password-reset.md) |
| Recovery-slip redemption | 3/300s per IP | `RedeemRecoverySlipAction` / `AccountRecovery` (inline `RateLimiter`) | [account-recovery-slips](SHQ1J-account-recovery-slips.md) |
| Password confirmation | 5/300s per user+IP | `ConfirmPassword` component (inline `RateLimiter`) | [password-confirmation](CQVSK-password-confirmation.md) |

> The table above is the **single registration point** for the canonical values of
> QLHDO §10 (FR-GLB-005: login 5/60s, forgot 3/3600s, reset 5/300s, recovery 3/300s) plus
> password confirmation. Enforcement mechanics live in the governing specs (linked above) —
> login via `AuthThrottleMiddleware`, the others via inline `RateLimiter` in Actions/Components.
> Implementations MUST use these values; never ad-hoc numbers.

### 6.5 LogContextMiddleware Payload

```php
[
    'request_id' => 'uuid-v4',
    'method' => 'POST',
    'url' => '/admin/users',
    'ip' => '192.168.1.100',
    'user_id' => 'uuid-of-user',
    'user_role' => 'admin',
    'duration_ms' => 142,
]
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). These are recorded decisions,
not test rows, so `Layer`/`Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-MID-001 | LogContext as first middleware | P0 | — | — |
| DD-MID-002 | Security headers before route handling | P0 | — | — |
| DD-MID-003 | Named rate limiters over inline configuration | P0 | — | — |

### 7.1 Pipeline Shape

#### DD-MID-001 — LogContext as First Middleware

Place context assembly anywhere but first and the failure is a blind denial: a rejected CSRF token or failed role check logs without a request identifier, and the incident is untraceable. `LogContextMiddleware` runs first so every later middleware and the handler inherits request context. The assembly costs roughly one millisecond per request, negligible against the debugging benefit.

#### DD-MID-002 — Security Headers Before Route Handling

An SMK penetration test once flagged headerless 403 pages: the app set headers in a response wrapper that early denials never reached. `SecurityHeadersMiddleware` now runs before the route handler rather than as response middleware, so headers are bound before any content or exception path executes. Headers land on 404 and 403 responses too, which is correct behavior rather than overhead.

#### DD-MID-003 — Named Rate Limiters Over Inline Configuration

At boot `AppServiceProvider` registers each limiter once by name, and route files only reference that name instead of embedding numbers. Centralizing policy this way means an audit or a tuning pass touches one file rather than hunting inline values across routes. The indirection costs developers a lookup in `AppServiceProvider` to see actual limits, mitigated by the §6.3 and §6.4 tables that publish every value in one place.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Middleware execution overhead per request | < 10ms total | Request duration profiling |
| Throttling uses canonical values | 100% (0 ad-hoc numbers) | `grep -R "RateLimiter" app/` vs §6.4 |
| Security headers on all responses | 100% incl. 404/403 | Header feature test (FR-MID-003) |
| CSRF rejection on forged POST | 100% (setup excepted) | Forgery feature test (FR-MID-013) |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes](SE5Q9-base-classes.md) | `SecurityHeadersMiddleware`, `LogContextMiddleware`, `RequireSetupAccessMiddleware`, `SetLocaleMiddleware` base implementations |
| [rbac-and-authorization](T4B26-rbac-and-authorization.md) | `CheckRoleMiddleware` for route-level role enforcement |

### Build Guide

After implementing this spec, the system has a complete middleware stack: security headers on
every response, request logging with duration tracking, setup gating for uninstalled instances,
locale resolution, and role-based route protection. The next step is to implement security
headers details, which extends the CSP and HSTS policies defined in this pipeline.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [security-headers](1PGM4-security-headers.md) | `SecurityHeadersMiddleware` from this spec applies the CSP, HSTS, X-Frame-Options defined in that spec |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume QLHDO §10 (FR-GLB-005) remains the canonical source for auth rate-limit budgets; any budget change there propagates to §6.4 first | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture design](D2FT3-architecture.md) — layer model this pipeline enforces
- [Security headers](1PGM4-security-headers.md) — header values applied by FR-MID-003
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — role semantics behind FR-MID-006
- [Authentication](YB7RG-authentication.md) — governing spec for login throttling
- [Password reset](D9TKW-password-reset.md) — governing spec for forgot/reset limits
- [Account recovery slips](SHQ1J-account-recovery-slips.md) — governing spec for recovery limits
- [Password confirmation](CQVSK-password-confirmation.md) — governing spec for confirmation limits
- [Base classes](SE5Q9-base-classes.md) — middleware base implementations
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — §10 / FR-GLB-005 canonical budgets
