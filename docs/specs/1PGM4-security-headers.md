# Security Headers — HTTP Response Protection

> **Spec ID:** 1PGM4

## Description

HTTP security-header infrastructure for Internara: Content Security Policy (CSP), HTTP Strict
Transport Security (HSTS), X-Frame-Options, Referrer-Policy, Permissions-Policy, output-escaping
discipline, and the `SecurityHeadersMiddleware` that injects the headers. Covers the production
CSP policy, development-mode relaxation for Vite, and configuration via
`config/security-headers.php`.

Pipeline position and registration are contracted in
[middleware-pipeline](2CF4Y-middleware-pipeline.md) (FR-MID-001/003); this spec owns the header
values, the environment behavior, and the escaping rule.

---

## 1. Problem Statements

### PS-1 — XSS and Data Exfiltration Risk

Without Content Security Policy, injected scripts can exfiltrate user data to external servers.
CSP restricts which sources the browser is allowed to load resources from.
**→ Requirement:** FR-SEC-001/002/003 (CSP header, default-src, script-src), FR-SEC-014 (escaped output).

### PS-2 — Clickjacking Attacks

Without X-Frame-Options, attackers can embed the application in invisible iframes to trick users
into clicking unintended actions.
**→ Requirement:** FR-SEC-007 (DENY), FR-SEC-013 (every response carries it).

### PS-3 — Transport Security Gaps

Without HSTS, browsers may downgrade HTTPS to HTTP on first visit, exposing credentials to
man-in-the-middle attacks.
**→ Requirement:** FR-SEC-006 (HSTS value), FR-SEC-011 (config-gated enablement).

### PS-4 — Unescaped Output Bypasses Headers

Headers constrain where resources load from, but a `{!! !!}` echo of untrusted content executes
inside the trusted origin — CSP cannot help there. Output escaping is the last line of defense
and needs the same contractual force as the headers.
**→ Requirement:** FR-SEC-014 (escaped-output rule).

---

## 2. Goals & Non-Goals

### Goals

- **Send a restrictive CSP on all responses** — `default-src 'self'` baseline with tight per-directive sources. *Why:* CSP is the primary mitigation for injected-script exfiltration.
- **Enforce HTTPS via HSTS with long max-age and subdomain coverage** — once enabled by the operator. *Why:* first-visit downgrade attacks steal credentials before the app ever sees the request.
- **Prevent clickjacking via X-Frame-Options** — `DENY` on every response. *Why:* framing the app invisibly turns user clicks into attacker actions.
- **Relax CSP for Vite hot reload in development only** — dev-server URL injected into `script-src`/`connect-src` when `APP_ENV=local`. *Why:* HMR is unloadable under a production CSP; the relaxation must never leak into production.
- **Centralize header configuration in one config file** — `config/security-headers.php` overridable per environment. *Why:* header policy must be reviewable and deployable without code edits.
- **Hold the escaped-output line** — `{{ }}` by default, `{!! !!}` only with a sanitizer and an inline justification. *Why:* untrusted content echoed raw executes inside the trusted origin where CSP cannot stop it.

### Non-Goals

- **Report-URI endpoint for CSP violation reports**. *Why:* collection infrastructure with no consumer at MVP; violations are caught pre-release by the breakage NFR.
- **Subresource Integrity (SRI) for all assets**. *Why:* first-party Vite-bundled assets under a `self` CSP already constrain the load surface.
- **Cookie-level security flags**. *Why:* handled by `config/session.php`, not by response headers.
- **Rate limiting headers**. *Why:* handled by the middleware pipeline ([middleware-pipeline](2CF4Y-middleware-pipeline.md)).

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SEC-001 | Deployer ships production with a strict CSP on every response | P0 | F | Full |
| UC-SEC-002 | Developer runs Vite hot reload with a relaxed-but-present CSP | P1 | F | Full |
| UC-SEC-003 | Auditor verifies the security posture by inspecting response headers | P1 | — | — |

### 3.1 Deploy, Develop & Audit

#### UC-SEC-001 — Production Deployment with Strict CSP

**Actor:** DevOps / Deployer
**Preconditions:** Application deployed to production with HTTPS; HSTS explicitly enabled.
**Flow:**
1. `SecurityHeadersMiddleware` reads `config/security-headers.php`
2. Applies CSP, HSTS, X-Frame-Options, Referrer-Policy, Permissions-Policy to the response
3. Browser enforces the CSP policy, blocking unauthorized script sources
**Postconditions:** All responses carry security headers.
**Governing guidance:** FR-SEC-001–009, FR-SEC-013.

#### UC-SEC-002 — Development with Vite Hot Reload

**Actor:** Developer
**Preconditions:** `APP_ENV=local`, Vite dev server running.
**Flow:**
1. `SecurityHeadersMiddleware` detects the local environment
2. Injects the Vite dev server URL (`http://localhost:5173`) into CSP `script-src` and `connect-src`
3. HSTS stays off (HTTP is acceptable in development)
**Postconditions:** Vite hot reload works; CSP is relaxed but still present.
**Governing guidance:** FR-SEC-010/011.

#### UC-SEC-003 — Security Audit Verification

**Actor:** Security auditor
**Preconditions:** Application running.
**Flow:**
1. Send a request to any endpoint
2. Inspect response headers for CSP, HSTS, X-Frame-Options
3. Verify the CSP policy is restrictive (no `unsafe-inline` in `script-src` in production)
**Postconditions:** All required headers present with correct values.
**Governing guidance:** Manual verification procedure — no code-testable consequence at this spec's level, hence `—`.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB) · `F` = Feature
(Action/Livewire/Console, real DB) · `B` = Browser (E2E journey) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-SEC-001 | `SecurityHeadersMiddleware` MUST set the `Content-Security-Policy` header on all responses | P0 | F | Full |
| FR-SEC-002 | CSP MUST include `default-src 'self'` as the baseline | P0 | F | Full |
| FR-SEC-003 | CSP MUST include `script-src 'self'` in production (Vite dev URL appended only in development) | P0 | F | Full |
| FR-SEC-004 | CSP MUST include `style-src 'self' 'unsafe-inline'` (Tailwind requires inline styles) | P0 | F | Full |
| FR-SEC-005 | CSP MUST include `img-src 'self' data: blob:` for uploaded images | P0 | F | Full |
| FR-SEC-006 | `Strict-Transport-Security` MUST be `max-age=31536000; includeSubDomains` when enabled | P0 | F | Full |
| FR-SEC-007 | `X-Frame-Options` MUST be `DENY` | P0 | F | Full |
| FR-SEC-008 | `Referrer-Policy` MUST be `strict-origin-when-cross-origin` | P1 | F | Full |
| FR-SEC-009 | `Permissions-Policy` MUST disable unnecessary browser features (camera, microphone, geolocation) | P1 | F | Full |
| FR-SEC-010 | In development (`APP_ENV=local`), CSP MUST include the Vite dev server URL in `script-src` and `connect-src` | P1 | F | Full |
| FR-SEC-011 | HSTS MUST be omitted by default and sent only when `security-headers.hsts_enabled` is enabled (config-gated, independent of `APP_ENV`) | P0 | F | Full |
| FR-SEC-012 | All header values MUST be configurable via `config/security-headers.php`, overridable per environment through `.env` | P0 | F | Full |
| FR-SEC-013 | Every response MUST carry `X-Frame-Options: DENY` and `Referrer-Policy` in production (feature test asserts headers) | P0 | F | Full |
| FR-SEC-014 | Untrusted content MUST render via escaped `{{ }}`; `{!! !!}` is allowed only with a sanitizer and an inline justification at the call site | P0 | A | Full |

### 4.1 Content Security Policy

#### FR-SEC-001 — CSP on every response

- Set in middleware (DD-SEC-001), never in Blade layouts — JSON, redirects, and error pages are covered.
- **Verification:** feature test asserts the header on normal, redirect, and error responses (layer `F`).

#### FR-SEC-002 — Baseline default-src

- The catch-all baseline; every other directive tightens from here.
- **Verification:** header-value assertion in the security-headers feature test (layer `F`).

#### FR-SEC-003 — Production script-src

- Production allows `'self'` only — no `unsafe-inline`, no remote script hosts. Development appends the Vite URL per FR-SEC-010.
- **Edge case:** a third-party script need (analytics, embeds) requires a spec amendment adding the host — never an inline exception.
- **Verification:** production header assertion contains exactly `script-src 'self'` (layer `F`).

#### FR-SEC-004 — Style-src with unsafe-inline

- Tailwind's JIT compiler produces inline `style` attributes; `'self'`-only would break all styling. Inline *scripts* (the primary XSS vector) stay forbidden — see DD-SEC-002.
- **Verification:** header assertion + visual smoke that styling is intact (layer `F`).

#### FR-SEC-005 — Image sources for uploads

- `data:` and `blob:` cover preview/upload flows for user-supplied media alongside first-party images.
- **Verification:** header assertion (layer `F`).

### 4.2 Transport, Framing & Client Hints

#### FR-SEC-006 — HSTS value

- One-year max-age with subdomain coverage; sent only when `security-headers.hsts_enabled` is on (FR-SEC-011).
- **Verification:** header assertion with the flag enabled (layer `F`).

#### FR-SEC-007 — Deny framing

- No embedding, same-origin included — the app never legitimately frames itself.
- **Verification:** every-response assertion (FR-SEC-013 covers the guarantee; layer `F`).

#### FR-SEC-008 — Referrer policy

- Cross-origin navigations leak origin only, never the full URL (which may contain IDs and tokens in query strings).
- **Verification:** header assertion (layer `F`).

#### FR-SEC-009 — Permissions policy

- Camera, microphone, and geolocation are disabled; the school-admin domain has no legitimate use for them.
- **Verification:** header assertion (layer `F`).

### 4.3 Environment Behavior

#### FR-SEC-010 — Vite development relaxation

- Appends `http://localhost:5173` to `script-src` and `connect-src` only; every other directive keeps its production value.
- **Edge case:** NFR-SEC-003 asserts the injection never occurs in production — the middleware branches on `APP_ENV=local`, not on host detection.
- **Verification:** feature test with `APP_ENV=local` asserts the appended URL; production-env test asserts its absence (layer `F`).

#### FR-SEC-011 — Config-gated HSTS

- Operators enable HSTS explicitly (typically in production) via `.env`; local HTTP workflows (`http://localhost:8000`) keep working until they do. Staging can trial HSTS with HTTPS on.
- **Verification:** default-off assertion + enabled assertion (layer `F`).

### 4.4 Configuration & Output Escaping

#### FR-SEC-012 — Centralized header config

- Full structure in §6.2; environment overrides flow through `.env` keys, never through code branches.
- **Verification:** config-override feature test (layer `F`).

#### FR-SEC-013 — Headers on every response

- The backstop guarantee behind FR-SEC-001/007/008: a dedicated feature test hits representative endpoints (page, redirect, 404) and asserts the framing and referrer headers each time.
- **Verification:** the assertion test itself (layer `F`).

#### FR-SEC-014 — Escaped-output rule

- `{{ }}` auto-escapes; raw `{!! !!}` of untrusted content executes inside the trusted origin where no header can stop it. Each raw echo needs two things inline: the sanitizer used and why raw output is required.
- **Verification:** `scan_security.py` XSS rule + review gate (layer `A`).

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SEC-001 | CSP MUST NOT break application functionality in production | 0 CSP-breakage incidents | P0 | F | Full |
| NFR-SEC-002 | Vite dev URL injection MUST NOT occur in production | 0 dev URLs in prod headers | P0 | F | Full |
| NFR-SEC-003 | Header configuration MUST be overridable per environment via `.env` | 0 code edits for env overrides | P1 | F | Full |

### 5.1 Header Safety

#### NFR-SEC-001 — No production breakage

- **Verification:** post-deploy smoke across representative pages plus the FR-SEC-001/013 header tests; any blocked legitimate resource is a release defect.

#### NFR-SEC-002 — No dev leakage

- **Verification:** production-env header test asserts `localhost:5173` is absent (paired with FR-SEC-010).

#### NFR-SEC-003 — Env overrides without code edits

- **Verification:** `.env`-driven override test (e.g., toggling `hsts_enabled`) with no code change (layer `F`).

---

## 6. API / Data Contracts

### 6.1 SecurityHeadersMiddleware

```php
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->buildCsp());
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', $this->buildPermissionsPolicy());

        if (config('security-headers.hsts_enabled', false)) {
            $response->headers->set('Strict-Transport-Security', $this->buildHsts());
        }

        return $response;
    }
}
```

### 6.2 config/security-headers.php Structure

```php
return [
    'csp' => [
        'default_src' => ["'self'"],
        'script_src' => ["'self'"],
        'style_src' => ["'self'", "'unsafe-inline'"],
        'img_src' => ["'self'", 'data:', 'blob:'],
        'font_src' => ["'self'"],
        'connect_src' => ["'self'"],
        'frame_ancestors' => ["'none'"],
    ],
    'hsts' => [
        'max_age' => 31536000,
        'include_subdomains' => true,
    ],
    'x_frame_options' => 'DENY',
    'referrer_policy' => 'strict-origin-when-cross-origin',
    'permissions_policy' => [
        'camera' => false,
        'microphone' => false,
        'geolocation' => false,
    ],
];
```

### 6.3 Development Mode CSP Override

When `APP_ENV=local`, the middleware appends the Vite dev server URL to:
- `script-src`: `http://localhost:5173`
- `connect-src`: `http://localhost:5173`

### 6.4 Escaped-Output Convention (FR-SEC-014)

```blade
{{-- Default: auto-escaped --}}
{{ $userContent }}

{{-- Exception: sanitized + justified inline --}}
{!! clean($trustedHtml) !!} {{-- sanitized via HTMLPurifier; raw needed for formatted handbook body --}}
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). These are recorded decisions,
not test rows, so `Layer`/`Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-SEC-001 | Middleware-based header injection | P0 | — | — |
| DD-SEC-002 | `unsafe-inline` allowed for styles, never for scripts | P0 | — | — |
| DD-SEC-003 | HSTS config-gated (off by default) | P0 | — | — |

### 7.1 Header Strategy

#### DD-SEC-001 — Middleware-Based Header Injection

**Decision:** Security headers are set via HTTP middleware, not via response middleware or
Blade layout.
**Rationale:** Middleware guarantees headers are present on ALL responses (including JSON, redirects,
error pages). Blade layouts only cover rendered views.
**Trade-off:** Headers are set even for API responses (if any exist in future). This is correct
behavior — APIs benefit from security headers too.

#### DD-SEC-002 — `unsafe-inline` for Styles

**Decision:** CSP allows `'unsafe-inline'` for `style-src` because Tailwind CSS generates
inline styles — while `script-src` stays `'self'`-only.
**Rationale:** Tailwind's JIT compiler produces inline `style` attributes. Restricting to
`'self'` only would break all styling. The risk is mitigated because inline scripts (not styles)
are the primary XSS vector, and FR-SEC-014 holds the output-escaping line regardless.
**Trade-off:** Inline styles can theoretically be injected by XSS, but the practical risk is
low compared to inline scripts.

#### DD-SEC-003 — HSTS Config-Gated (Off by Default)

**Decision:** HSTS is gated on the `security-headers.hsts_enabled` config flag (default `false`),
independent of `APP_ENV`. Operators explicitly enable it (typically in production) via `.env`.
**Rationale:** Local development often uses HTTP (e.g., `http://localhost:8000`). HSTS would
force the browser to remember HTTPS-only, potentially breaking local development workflows.
Config-gating (instead of `APP_ENV` detection) keeps the behavior explicit and lets staging
instances test HSTS with HTTPS enabled.
**Trade-off:** HSTS can be accidentally enabled in non-production environments if the flag is set;
operators must configure it deliberately.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Production CSP violations | 0 | Browser reports + smoke tests |
| CSP breakage in production | 0 incidents | Post-deploy smoke (NFR-SEC-001) |
| Responses missing framing headers | 0 | FR-SEC-013 assertion test |
| Raw `{!! !!}` without justification | 0 | `scan_security.py` + review |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [middleware-pipeline](2CF4Y-middleware-pipeline.md) | `SecurityHeadersMiddleware` registration in the global stack |

### Build Guide

After implementing this spec, every HTTP response has CSP, HSTS, X-Frame-Options,
Referrer-Policy, and Permissions-Policy headers. This spec is a leaf in the foundation chain —
the headers are applied automatically by the middleware. The next step is to build the job queue
infrastructure for async processing.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md) | Queued jobs run in the same security context; headers applied to web responses only |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume operators enable `hsts_enabled` in production via `.env`; fresh installs without it serve no HSTS header until configured | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture design](D2FT3-architecture.md) — layer model and defense-in-depth (NFR-ARC-005)
- [Middleware pipeline](2CF4Y-middleware-pipeline.md) — pipeline position of `SecurityHeadersMiddleware`
- [Job & queue infrastructure](8FVZA-job-queue-infrastructure.md) — downstream consumer context
- [Tech stack](FB792-tech-stack.md) — Tailwind/Vite versions behind the CSP relaxations
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — global security requirements (NFR-SEC-004)
