# Security Rule Checks — S1-S10 Anti-Patterns

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

`scan_security.py` checks Internara against ten security rules (S1-S10) based on OWASP and
Laravel security best practices. These are the security dimension of the quality gate; auth and PII
handling beyond surface anti-patterns are covered by the `security-audit` skill. This rule documents
each S-rule, why it exists, and the baseline detection.

---

## Security Rules Table

| ID | Rule | Detection |
|----|------|-----------|
| **S1** | XSS: `{!! !!}` only for trusted content, `{!! e() !!}` for escaping | Blade: `{!! $var !!}` without `e()` wrapper |
| **S2** | SQL Injection: parameterized queries only | Raw SQL without bindings |
| **S3** | Mass Assignment: `Model::create($validated)` with only allowed fields | `create($request->all())` |
| **S4** | CSRF: `@csrf` in forms, `csrf_token()` in AJAX | Missing CSRF tokens |
| **S5** | Authentication: `auth()->check()` or `@auth` before sensitive operations | Unprotected endpoints |
| **S6** | Authorization: `$this->authorize()` or `@can` before actions | Missing authorization checks |
| **S7** | Rate limiting: `RateLimiter::` or `throttle` middleware | No rate limiting on auth endpoints |
| **S8** | Secrets: no hardcoded passwords/tokens/keys | Hardcoded credentials |
| **S9** | File upload: validate type, size, scan content | Unrestricted uploads |
| **S10** | Headers: security headers set | Missing CSP, X-Frame-Options, etc. |

## Intent

Each rule exists to neutralize a specific, well-known exploit class that a self-hosted PKL platform
with PII (student records) cannot afford. They apply at the code surface the scanner can see —
Blade, routes, controllers, models, and config.

## Per-Rule Rationale & How to Apply

### S1 — XSS

**Why it exists:** `{!! !!}` outputs raw HTML. If the value is user-controlled (a logbook entry, a
company name), an attacker's `<script>` executes in the victim's browser with the victim's session —
the classic stored XSS.

**How to apply:** Only render `{!! !!}` content that is provably trusted (server-rendered safe HTML
from a markdown renderer with sanitization). Everything else uses `{{ $var }}` (Blade auto-escapes).

**Failure mode if ignored:** An intern pastes `<img src=x onerror=...>` into a logbook; the teacher's
session is hijacked when they review it.

### S2 — SQL Injection

**Why it exists:** Raw SQL with concatenated input is a direct query-injection vector (same C3
invariant). The scanner treats any raw clause without parameter bindings as suspicious.

**How to apply:** Query builder / Eloquent first; `->whereRaw('...', [$binds])` if raw is needed.

**Failure mode if ignored:** A search filter concatenates `"name LIKE '%$q%'"` and the attacker
turns it into `'; DROP TABLE...` or a data exfiltration UNION.

### S3 — Mass Assignment

**Why it exists:** `create($request->all())` passes every client-supplied key into the model. Even
with `#[Fillable]`, unexpected keys slip through and `is_admin`-style fields get set.

**How to apply:** `->only([...])` / `->toArray()` slices, or a DTO constructed from validated fields
(see D5 in `invariant-enforcement.md`).

**Failure mode if ignored:** A crafted POST sets an `active` or `role` flag the form never
rendered, escalating the requester.

### S4 — CSRF

**Why it exists:** Laravel's session CSRF token paired with `VerifyCsrfToken` middleware is the
default defense; a form missing `@csrf` (or an AJAX call missing the token header) is a cross-site
request-forgery window.

**How to apply:** `@csrf` in every form; `csrf_token()` for AJAX payloads; keep the middleware on all
web routes.

**Failure mode if ignored:** A third-party site posts to `/logout`, `/update-profile`, or a
privileged admin endpoint while the user is logged in — state changed without consent.

### S5 — Authentication

**Why it exists:** Sensitive operations must not be reachable anonymously.

**How to apply:** `auth()->check()` guards, `@auth` Blade directives, or route-level `auth`
middleware on sensitive endpoints.

**Failure mode if ignored:** An unauthenticated request reaches an endpoint that echoes or mutates
PII, leaking student data to the public internet.

### S6 — Authorization

**Why it exists:** Authentication proves *who* you are; authorization proves you *may* do this. A
logged-in student who can grade their own submission is a higher-severity bug than a public logout.

**How to apply:** `$this->authorize('update', $model)` / `@can` at every ability boundary; Policy
classes per module.

**Failure mode if ignored:** Any registered user visits
`/assessment/{id}/grade`, mutates a graded record, and the audit log records the change as
legitimate.

### S7 — Rate Limiting

**Why it exists:** Auth endpoints (login, password reset, account recovery) are brute-force targets.

**How to apply:** `RateLimiter` / `throttle` middleware on auth and sensitive endpoints.

**Failure mode if ignored:** An attacker runs an offline dictionary against the login endpoint —
thousands of guesses per minute, no lockout, until one succeeds.

### S8 — Secrets

**Why it exists:** Hardcoded passwords, tokens, and keys embed credentials that survive repository
history and every clone.

**How to apply:** Env-backed config (`.env`) for all secrets; never default secrets in committed
config.

**Failure mode if ignored:** A committed `admin` password ships in the repo; every current and future
clone of the project inherits a back door.

### S9 — File upload

**Why it exists:** Unrestricted uploads accept executable content, oversized files (disk exhaustion),
and malformed media (parser crashes).

**How to apply:** Validate `type`/`mime`/`size`; reject executable/polyglot content; store outside
webroot (Spatie MediaLibrary with validated collections).

**Failure mode if ignored:** An upload accepts a `.php` disguised as `.jpg` and executes server-side,
compromising the host.

### S10 — Security headers

**Why it exists:** CSP, X-Frame-Options / frame-ancestors, `X-Content-Type-Options`, and HSTS
mitigate clickjacking, MIME confusion, and injection amplification at the response layer.

**How to apply:** Configure the header middleware/CSP in a single config location applied globally.

**Failure mode if ignored:** An iframe-wrapped admin panel performs clickjacked "click on the
approve" actions; MIME-sniffing executes script disguised as text.

---

## Severity Guidance

- **S1, S2, S3, S8, S9** — always **CRITICAL** when a live exploit path is confirmed (user input
  reaches an unsafe sink). **HIGH** when the sink exists but is unreachable from user input today.
- **S4, S6** — **HIGH** (authorization/CSRF bypass risk).
- **S5, S7, S10** — **MEDIUM** to **HIGH** depending on endpoint exposure (auth endpoints without
  rate limiting are **HIGH**).

When in doubt, err high: a security finding under-rated becomes a "known gap" nobody schedules.

## Verification

```bash
python3 scripts/scan_security.py              # XSS, SQLi, mass assignment, auth patterns
python3 scripts/scan_security.py --module {Name}   # module scope
```

**Interpretation guidance:** a detected anti-pattern is a *baseline* — confirm the live exploit path
before classifying. Pair every confirmed finding with `security-audit` (deep OWASP/PII review) when
the surface touches authentication or student PII. Findings are filed via `issue-writing` with the
`security` scope.