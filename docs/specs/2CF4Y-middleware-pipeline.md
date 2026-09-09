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

**Actor:** Developer
**Preconditions:** New global request processing needed (e.g., maintenance mode).
**Flow:**
1. Create class in `app/Modules/Core/Http/Middleware/`
2. Register in `bootstrap/app.php` (alias or global position)
3. Position in pipeline relative to existing middleware per §6.1
4. Test that existing middleware still executes in correct order
**Postconditions:** New middleware runs on every request at the correct position.
**Governing guidance:** FR-MID-001/012, DD-MID-001.

#### UC-MID-002 — Developer Adds Module-Specific Middleware

**Actor:** Developer
**Preconditions:** New route-group processing needed (e.g., setup gating).
**Flow:**
1. Create class in `{Module}/Http/Middleware/` (or the domain path, e.g. `Setup/Domain/Installation/Http/Middleware/`)
2. Register as alias in the module's route file
3. Apply to specific route group via `->middleware()`
**Postconditions:** Middleware runs only on targeted routes.
**Governing guidance:** FR-MID-012.

#### UC-MID-003 — Developer Configures Rate Limiting

**Actor:** Developer
**Preconditions:** Route needs rate limiting.
**Flow:**
1. Choose the existing named limiter (`admin` or `global`) or a canonical per-endpoint budget from §6.4
2. Apply via `->middleware('throttle:admin')` on the route group — or via the governing spec's enforcement point for auth endpoints
3. Verify throttling behavior with test requests using the canonical values, never ad-hoc numbers
**Postconditions:** Rate limiting active with the correct, auditable limits.
**Governing guidance:** FR-MID-010/011; per-endpoint governing specs linked in §6.4.

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

- Order: LogContext → SecurityHeaders → CSRF → Authenticate → CheckRole → SetLocale → handler (§6.1). Registration lives in `bootstrap/app.php`.
- **Verification:** `bootstrap/app.php` review + header/context feature tests proving order effects (layer `A`).

#### FR-MID-002 — Request log context

- Payload in §6.5; `request_id` is a UUID v4 generated per request so every downstream log line is correlatable.
- **Edge case:** logging infrastructure down MUST NOT fail the request (NFR-MID-003) — context assembly degrades silently.
- **Verification:** feature test asserts context keys on a logged request (layer `F`).

#### FR-MID-003 — Security headers before handling

- Values contracted in [security-headers](1PGM4-security-headers.md); this row owns the position (before the handler, never as an afterthought on rendered views only).
- **Verification:** feature test asserts headers on normal, redirect, and error responses (layer `F`).

#### FR-MID-004 — Auth and CSRF positions

- Authentication and forgery validation run after context/headers (so denials are logged and hardened) and before role/locale (which need identity). Mechanics in FR-MID-013.
- **Verification:** `bootstrap/app.php` review (layer `A`).

### 4.2 Module Middleware Behaviors

#### FR-MID-005 — Login throttling

- Canonical values in §6.4; governing spec is [authentication](YB7RG-authentication.md). Throttling keys on IP so credential-stuffing from one source is contained.
- **Verification:** feature test exceeds 5 attempts in 60s and is throttled (layer `F`).

#### FR-MID-006 — Role gate

- Role semantics owned by [rbac-and-authorization](T4B26-rbac-and-authorization.md); this middleware is the route-level enforcement point returning 403 on failure.
- **Verification:** feature tests for allow/deny per role (layer `F`).

#### FR-MID-007 — Locale resolution and persistence

- Resolution order: authenticated user preference → session → application default; the resolved locale persists so the next request needs no re-negotiation.
- **Verification:** feature test asserts locale switch persists across requests (layer `F`).

#### FR-MID-008 — Setup route protection

- After installation completes, setup routes are unreachable — reinstall-via-URL is impossible.
- **Verification:** feature test hits a setup route post-install and is blocked (layer `F`).

#### FR-MID-009 — Setup access token

- Setup routes additionally require a valid access token, so network access alone is insufficient during installation.
- **Verification:** feature test with missing/invalid/valid token (layer `F`).

### 4.3 Rate Limiting

#### FR-MID-010 — Named route limiters

- Registered via `RateLimiter::for()`; applied with `->middleware('throttle:admin')` / `throttle:global` on route groups. `global` is per-IP at 30/min per QLHDO §10.
- **Verification:** limiter registration review + throttling feature test (layer `F`).

#### FR-MID-011 — Canonical per-endpoint budgets

- The §6.4 table is the single registration point for the QLHDO §10 budgets (FR-GLB-005): login 5/60s, forgot 3/3600s, reset 5/300s, recovery 3/300s, plus password confirmation 5/300s. Enforcement mechanics live in the governing specs; login via `AuthThrottleMiddleware`, the rest via inline `RateLimiter` in Actions/Components.
- **Verification:** each governing spec's throttling test uses these exact values (layer `F`).

### 4.4 Request Integrity & Extensibility

#### FR-MID-012 — Extension without core edits

- New middleware ships as an alias applied in the module's route file; `bootstrap/app.php` and existing middleware stay untouched.
- **Edge case:** genuinely global new middleware (maintenance mode) registers in `bootstrap/app.php` at an explicit §6.1-relative position with a DD note.
- **Verification:** alias registration review (layer `A`).

#### FR-MID-013 — CSRF on web, session auth, no token layer

- `preventRequestForgery` is active with the `setup` exception (setup runs pre-install without sessions); authentication is session-cookie via Laravel defaults. No Sanctum/token guard exists because there is no API surface (see [tech-stack](FB792-tech-stack.md) non-goals).
- **Verification:** `bootstrap/app.php` review + CSRF rejection feature test (layer `F`).

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-MID-001 | Security headers MUST NOT break Vite hot module replacement in development | HMR works with headers active | P0 | F | Full |
| NFR-MID-002 | Rate limit counters MUST use the cache driver (not the database) for performance | 0 DB-backed counters | P1 | A | Full |
| NFR-MID-003 | `LogContextMiddleware` MUST NOT fail the request if logging infrastructure is down | 0 logging-caused 500s | P0 | F | Full |

### 5.1 Pipeline Resilience

#### NFR-MID-001 — Headers coexist with HMR

- Development CSP relaxations for the Vite dev server are contracted in [security-headers](1PGM4-security-headers.md) (FR-SEC-010); this row asserts the observable outcome.
- **Verification:** manual HMR smoke in local env (layer `F`).

#### NFR-MID-002 — Cache-backed counters

- **Verification:** limiter definitions review — all `RateLimiter::for()` closures resolve against cache (layer `A`).

#### NFR-MID-003 — Logging never breaks requests

- **Verification:** feature test with logging disabled/broken asserts the response still succeeds (layer `F`).

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

**Decision:** `LogContextMiddleware` is the first middleware in the pipeline.
**Rationale:** Every subsequent middleware and the route handler can rely on request context
being available. Placing it later would mean some operations lack traceability.
**Trade-off:** Adds ~1ms to every request for context assembly. Negligible for the debugging
benefit.

#### DD-MID-002 — Security Headers Before Route Handling

**Decision:** `SecurityHeadersMiddleware` runs before the route handler, not as a response middleware.
**Rationale:** Headers must be set before any response content is generated. Running as
response middleware risks headers being omitted on early returns or exceptions.
**Trade-off:** Headers are set even for 404/403 responses, which is correct behavior.

#### DD-MID-003 — Named Rate Limiters Over Inline Configuration

**Decision:** Rate limiters are registered by name in `AppServiceProvider`, not configured
inline in route files.
**Rationale:** Centralizes rate limiting policy. Makes it easy to audit and adjust limits
without modifying individual route files.
**Trade-off:** Adds a layer of indirection. Developers must check `AppServiceProvider` to
understand actual limits — mitigated by the §6.3/§6.4 tables.

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
