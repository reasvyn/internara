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

An SMK in Bekasi shipped its production site on HTTPS with HSTS explicitly enabled, then watched the browser block an injected crypto miner on a lab machine because the headers held. `SecurityHeadersMiddleware` reads `config/security-headers.php` and applies CSP, HSTS, X-Frame-Options, Referrer-Policy, and Permissions-Policy to the response, so the browser enforces the CSP and unauthorized script sources never execute. Every response leaves carrying those headers, under FR-SEC-001 through 009 plus the every-response backstop of FR-SEC-013.

#### UC-SEC-002 — Development with Vite Hot Reload

With `APP_ENV=local` and the Vite dev server running, the middleware detects the local environment and injects `http://localhost:5173` into CSP `script-src` and `connect-src`, while HSTS stays off because plain HTTP remains acceptable in development. Hot reload flows through the relaxed directives, yet CSP stays present rather than disappearing. FR-SEC-010 and FR-SEC-011 own that split.

#### UC-SEC-003 — Security Audit Verification

The edge an auditor hunts is the quiet exception: one endpoint serving a permissive policy while the rest look strict. The manual pass sends a request to any running endpoint, inspects the response headers for CSP, HSTS, and X-Frame-Options, and confirms the CSP stays restrictive with no `unsafe-inline` in production `script-src`. All required headers present with correct values is the bar, and because this is a manual verification procedure with no code-testable consequence at this spec's level, the row carries `—`.

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

Headers once lived in the main Blade layout, which meant JSON endpoints, redirects, and error pages shipped without any policy at all. Setting `Content-Security-Policy` in middleware per DD-SEC-001 closed those gaps in one move — every response shape inherits the same header because none of them can bypass the pipeline. A feature test asserts the header on normal, redirect, and error responses at layer `F`.

#### FR-SEC-002 — Baseline default-src

Without a catch-all baseline, any directive the policy forgets to name falls open to every source. `default-src 'self'` shuts that default, and every other directive tightens from there rather than inventing its own floor. The header-value assertion in the security-headers feature test pins the baseline at layer `F`.

#### FR-SEC-003 — Production script-src

An SMK webmaster once pasted an analytics snippet into a handbook page and watched production refuse to run it — exactly the intended outcome. Production `script-src` allows `'self'` only, with no `unsafe-inline` and no remote script hosts; development alone appends the Vite URL per FR-SEC-010. A future third-party need such as analytics or embeds requires a spec amendment adding the host, never an inline exception. The production header assertion contains exactly `script-src 'self'`, checked at layer `F`.

#### FR-SEC-004 — Style-src with unsafe-inline

At render time Tailwind's JIT compiler emits inline `style` attributes on countless elements, so a `style-src 'self'`-only policy would strip all styling and ship an unstyled page. The policy therefore carries `style-src 'self' 'unsafe-inline'`, while inline scripts — the primary XSS vector — stay forbidden per DD-SEC-002. Header assertion plus a visual smoke that styling survives confirms the split at layer `F`.

#### FR-SEC-005 — Image sources for uploads

The preview edge breaks a strict image policy first: a student selects a photo for upload and the browser renders it from a `blob:` URL, while a cropped avatar arrives as a `data:` URL. `img-src 'self' data: blob:` keeps those user-supplied flows working alongside first-party images instead of blocking every preview. A header assertion pins the three sources at layer `F`.

### 4.2 Transport, Framing & Client Hints

#### FR-SEC-006 — HSTS value

First-visit downgrade attacks kept stealing credentials before the application ever saw the request, which is why the policy carries a full year of memory. `Strict-Transport-Security` sends `max-age=31536000; includeSubDomains` with subdomain coverage, but only when `security-headers.hsts_enabled` is on per FR-SEC-011, so operators opt in deliberately. A header assertion with the flag enabled proves the value at layer `F`.

#### FR-SEC-007 — Deny framing

Allow same-origin framing and an attacker page earns one invisible iframe around the grade-approval button — the classic clickjacking conversion of a teacher's click into the attacker's action. `X-Frame-Options: DENY` forbids all embedding, same-origin included, because the app never legitimately frames itself. The every-response assertion behind FR-SEC-013 carries the guarantee at layer `F`.

#### FR-SEC-008 — Referrer policy

An SMK coordinator once shared a support link to an external helpdesk and the full URL — student identifier and reset token in the query string — arrived in the vendor's logs via the referrer. `Referrer-Policy: strict-origin-when-cross-origin` stops that class of leak: cross-origin navigations expose the origin only, never the full URL. A header assertion locks the value at layer `F`.

#### FR-SEC-009 — Permissions policy

When the browser builds a page context it checks which device capabilities the response permits, and this response permits none of the risky ones. Camera, microphone, and geolocation arrive disabled because the school-admin domain has no legitimate use for them. A header assertion confirms the disabled trio at layer `F`.

### 4.3 Environment Behavior

#### FR-SEC-010 — Vite development relaxation

The leak edge would be a dev URL surviving into production headers, quietly widening the script surface for every school. The relaxation therefore appends `http://localhost:5173` to `script-src` and `connect-src` only, while every other directive keeps its production value, and per NFR-SEC-003 the injection never occurs in production because the middleware branches on `APP_ENV=local`, not on host detection. A feature test with `APP_ENV=local` asserts the appended URL appears, while the production-env test asserts its absence, both at layer `F`.

#### FR-SEC-011 — Config-gated HSTS

HSTS was once tied to environment detection, and local developers on `http://localhost:8000` found their browsers force-remembering HTTPS and breaking every subsequent run. Config-gating fixed that: HSTS is omitted by default and sent only when `security-headers.hsts_enabled` is enabled, independent of `APP_ENV`, so operators enable it explicitly via `.env` typically in production while local HTTP keeps working until they do, and staging can trial HSTS with HTTPS on. Default-off plus enabled assertions prove both states at layer `F`.

### 4.4 Configuration & Output Escaping

#### FR-SEC-012 — Centralized header config

Scatter header values through conditionals and the failure is a deploy that cannot be tuned without a code edit — an operator wanting a longer HSTS max-age waits on a developer. Centralizing every value in `config/security-headers.php` with the full structure in §6.2 means environment overrides flow through `.env` keys, never through code branches. A config-override feature test proves an env change takes effect untouched at layer `F`.

#### FR-SEC-013 — Headers on every response

An SMK security scan once flagged a single redirect endpoint missing its framing headers while every page passed — the one gap an attacker would have framed. This backstop behind FR-SEC-001, FR-SEC-007, and FR-SEC-008 demands every response carry `X-Frame-Options: DENY` and `Referrer-Policy` in production. A dedicated feature test hits representative endpoints — a page, a redirect, a 404 — and asserts the framing and referrer headers each time, at layer `F`.

#### FR-SEC-014 — Escaped-output rule

At render time `{{ }}` auto-escapes every byte, while a raw `{!! !!}` echo of untrusted content executes inside the trusted origin where no header can intervene. That is why each raw echo carries two things inline at the call site: the sanitizer used and why raw output is required. The `scan_security.py` XSS rule plus review gate enforces the discipline at layer `A`.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SEC-001 | CSP MUST NOT break application functionality in production | 0 CSP-breakage incidents | P0 | F | Full |
| NFR-SEC-002 | Vite dev URL injection MUST NOT occur in production | 0 dev URLs in prod headers | P0 | F | Full |
| NFR-SEC-003 | Header configuration MUST be overridable per environment via `.env` | 0 code edits for env overrides | P1 | F | Full |

### 5.1 Header Safety

#### NFR-SEC-001 — No production breakage

The strict-policy edge is self-inflicted downtime: a tightened directive silently blocks the certificate logo or the Livewire bundle, and the app looks hacked on launch day. Holding zero CSP-breakage incidents takes a post-deploy smoke across representative pages alongside the FR-SEC-001 and FR-SEC-013 header tests — any blocked legitimate resource counts as a release defect, not a security win.

#### NFR-SEC-002 — No dev leakage

The relaxation was built local-first, and the earliest version keyed off host detection — which meant a production deploy behind a localhost-named proxy inherited the dev URL. Branching on `APP_ENV` instead closed that hole, holding zero dev URLs in production headers. A production-env header test asserts `localhost:5173` is absent, paired with FR-SEC-010.

#### NFR-SEC-003 — Env overrides without code edits

Force operators to edit PHP to toggle HSTS and the change waits for a deploy window that never comes during enrollment season. Keeping every header override behind `.env` holds zero code edits for environment changes. An env-driven override test toggling `hsts_enabled` with no code change proves it at layer `F`.

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

An SMK audit once showed clean headers on every page but bare JSON attendance feeds, because headers had been baked into the Blade layout that API-style responses never touch. Setting headers via HTTP middleware instead of response middleware or Blade layout guarantees they ride along on all responses — JSON, redirects, and error pages alike, where layouts only cover rendered views. Headers land on future API responses too, which is correct behavior since those endpoints benefit from the same protections.

#### DD-SEC-002 — `unsafe-inline` for Styles

At runtime Tailwind's JIT compiler stamps inline `style` attributes across the markup, so a `style-src 'self'`-only policy arrives as a broken, unstyled page while `script-src` can safely stay `'self'`-only. The policy therefore allows `'unsafe-inline'` for `style-src` alone. Inline scripts remain the primary XSS vector rather than styles, and FR-SEC-014 holds the output-escaping line regardless, leaving the theoretical style-injection residue low beside the script risk it avoids.

#### DD-SEC-003 — HSTS Config-Gated (Off by Default)

Consider a developer testing on `http://localhost:8000` after visiting a staging host with HSTS on — the browser remembers HTTPS-only and the local workflow breaks for weeks with no server-side fix. Gating HSTS on the `security-headers.hsts_enabled` flag defaulting to `false`, independent of `APP_ENV`, avoids that trap: operators enable it explicitly via `.env` typically in production, local HTTP keeps working until they do, and staging can still trial HSTS with HTTPS on. The remaining edge is an operator accidentally enabling the flag outside production, so the flag must be set deliberately.

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
