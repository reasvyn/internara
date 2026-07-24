# Security Headers — HTTP Response Protection

> **Last updated:** 2026-07-23 **Changes:** feat — initial security headers specification

## Description

Defines the HTTP security headers infrastructure: Content Security Policy (CSP), HTTP Strict
Transport Security (HSTS), X-Frame-Options, Referrer-Policy, Permissions-Policy, and the
`SecurityHeaders` middleware that injects them. Covers production CSP policy, development-mode
relaxation for Vite, and configuration via `config/security-headers.php`.

---

## 1. Problem Statements

### PS-1 — XSS and Data Exfiltration Risk

Without Content Security Policy, injected scripts can exfiltrate user data to external servers.
CSP restricts which sources the browser is allowed to load resources from.

### PS-2 — Clickjacking Attacks

Without X-Frame-Options, attackers can embed the application in invisible iframes to trick users
into clicking unintended actions.

### PS-3 — Transport Security Gaps

Without HSTS, browsers may downgrade HTTPS to HTTP on first visit, exposing credentials to
man-in-the-middle attacks.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | All responses include a restrictive CSP header |
| G2  | HSTS enforces HTTPS for all subdomains with long max-age |
| G3  | Clickjacking prevented via X-Frame-Options |
| G4  | Development mode relaxes CSP for Vite hot module replacement |
| G5  | Security header configuration is centralized in a config file |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Report-URI endpoint for CSP violation reports |
| NG2  | Subresource Integrity (SRI) for all assets |
| NG3  | Cookie-level security flags (handled by `config/session.php`) |
| NG4  | Rate limiting headers (handled by middleware) |

---

## 3. User Stories / Use Cases

### UC-1 — Production Deployment with Strict CSP

**Actor:** DevOps / Deployer
**Preconditions:** Application deployed to production with HTTPS
**Flow:**
1. `SecurityHeaders` middleware reads `config/security-headers.php`
2. Applies CSP, HSTS, X-Frame-Options, Referrer-Policy, Permissions-Policy to response
3. Browser enforces CSP policy, blocks unauthorized script sources
**Postconditions:** All responses carry security headers

### UC-2 — Development with Vite Hot Reload

**Actor:** Developer
**Preconditions:** `APP_ENV=local`, Vite dev server running
**Flow:**
1. `SecurityHeaders` detects local environment
2. Injects Vite dev server URL (`http://localhost:5173`) into CSP `script-src` and `connect-src`
3. HSTS disabled (HTTP is acceptable in development)
**Postconditions:** Vite hot reload works, CSP is relaxed but still present

### UC-3 — Security Audit Verification

**Actor:** Security auditor
**Preconditions:** Application running
**Flow:**
1. Send request to any endpoint
2. Inspect response headers for CSP, HSTS, X-Frame-Options
3. Verify CSP policy is restrictive (no `unsafe-inline` in production)
**Postconditions:** All required headers present with correct values

---

## 4. Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| FR-SEC1 | `SecurityHeaders` middleware MUST set `Content-Security-Policy` header on all responses |
| FR-SEC2 | CSP MUST include `default-src 'self'` as baseline |
| FR-SEC3 | CSP MUST include `script-src 'self'` (production) or with Vite dev URL (development) |
| FR-SEC4 | CSP MUST include `style-src 'self' 'unsafe-inline'` (Tailwind requires inline styles) |
| FR-SEC5 | CSP MUST include `img-src 'self' data: blob:` for uploaded images |
| FR-SEC6 | `Strict-Transport-Security` MUST be set to `max-age=31536000; includeSubDomains` in production |
| FR-SEC7 | `X-Frame-Options` MUST be set to `DENY` |
| FR-SEC8 | `Referrer-Policy` MUST be set to `strict-origin-when-cross-origin` |
| FR-SEC9 | `Permissions-Policy` MUST disable unnecessary browser features (camera, microphone, geolocation) |
| FR-SEC10 | In development (`APP_ENV=local`), CSP MUST include Vite dev server URL in `script-src` and `connect-src` |
| FR-SEC11 | In development, HSTS header MUST be omitted |
| FR-SEC12 | All header values MUST be configurable via `config/security-headers.php` |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-SEC1 | Security header injection MUST add < 1ms overhead per request |
| NFR-SEC2 | CSP MUST NOT break application functionality in production |
| NFR-SEC3 | Vite dev URL injection MUST NOT occur in production |
| NFR-SEC4 | Header configuration MUST be overridable per-environment via `.env` |

---

## 6. API / Data Contracts

### SecurityHeaders Middleware

```php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->buildCsp());
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', $this->buildPermissionsPolicy());

        if (!app()->environment('local')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
```

### config/security-headers.php Structure

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

### Development Mode CSP Override

When `APP_ENV=local`, the middleware appends Vite dev server URL to:
- `script-src`: `http://localhost:5173`
- `connect-src`: `http://localhost:5173`

---

## 7. Design Decisions

### DD-1 — Middleware-Based Header Injection

**Decision:** Security headers are set via HTTP middleware, not via response middleware or
Blade layout.

**Rationale:** Middleware guarantees headers are present on ALL responses (including JSON, redirects,
error pages). Blade layouts only cover rendered views.

**Trade-off:** Headers are set even for API responses (if any exist in future). This is correct
behavior — APIs benefit from security headers too.

### DD-2 — `unsafe-inline` for Styles

**Decision:** CSP allows `'unsafe-inline'` for `style-src` because Tailwind CSS generates
inline styles.

**Rationale:** Tailwind's JIT compiler produces inline `style` attributes. Restricting to
`'self'` only would break all styling. The risk is mitigated because inline scripts (not styles)
are the primary XSS vector.

**Trade-off:** Inline styles can theoretically be injected by XSS, but the practical risk is
low compared to inline scripts.

### DD-3 — HSTS Disabled in Development

**Decision:** HSTS header is omitted when `APP_ENV=local`.

**Rationale:** Local development often uses HTTP (e.g., `http://localhost:8000`). HSTS would
force the browser to remember HTTPS-only, potentially breaking local development workflows.

**Trade-off:** Security testing of HSTS requires a staging environment with HTTPS configured.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Responses with CSP header | 100% |
| Responses with X-Frame-Options | 100% |
| Production CSP violations | 0 |
| Vite HMR working in development | 100% |
| CSP breakage in production | 0 incidents |

---

## Quick References

- `app/Core/Http/Middleware/SecurityHeaders.php` — Security headers middleware
- `config/security-headers.php` — Header configuration
- `docs/specs/middleware-pipeline.md` — Middleware execution order
- `docs/conventions.md` — Security conventions
