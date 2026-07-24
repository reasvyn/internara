# Middleware Pipeline — HTTP Request Processing Chain

> **Last updated:** 2026-07-23 **Changes:** feat — initial middleware pipeline specification

## Description

Defines the HTTP middleware pipeline: core and module middleware classes, their execution order,
registration mechanism, rate limiting configuration, and when to add new middleware. This spec
ensures every request passes through the correct security and context-enrichment layers.

---

## 1. Problem Statements

### PS-1 — Middleware Order Affects Security

Middleware executes in registration order. If `CheckRoleMiddleware` runs before `AuthThrottleMiddleware`,
unauthenticated users bypass rate limiting. Incorrect ordering creates security vulnerabilities.

### PS-2 — No Guideline for New Middleware

When a developer needs to add request-level processing (e.g., maintenance mode check, API versioning),
there is no spec defining where it fits in the pipeline or how to register it.

### PS-3 — Rate Limiting Inconsistency

Rate limiters are defined in `AppServiceProvider` but not documented. Developers don't know which
limiter to apply to which route group.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Middleware execution order is explicit and documented |
| G2  | Core middleware applies globally to all requests |
| G3  | Module middleware applies only to its route group |
| G4  | Rate limiters are reusable and named |
| G5  | New middleware can be added without modifying existing classes |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Middleware for REST API versioning (no API layer exists) |
| NG2  | WebSocket middleware |
| NG3  | Middleware hot-swapping at runtime |
| NG4  | Request/response transformation middleware |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Adds New Core Middleware

**Actor:** Developer
**Preconditions:** New global request processing needed (e.g., maintenance mode)
**Flow:**
1. Create class in `app/Core/Http/Middleware/`
2. Register in `bootstrap/app.php` or `app/Http/Kernel.php`
3. Position in pipeline relative to existing middleware
4. Test that existing middleware still executes in correct order
**Postconditions:** New middleware runs on every request at the correct position

### UC-2 — Developer Adds Module-Specific Middleware

**Actor:** Developer
**Preconditions:** New route-group processing needed (e.g., setup gating)
**Flow:**
1. Create class in `{Module}/Http/Middleware/`
2. Register as alias in the module's route file
3. Apply to specific route group via `->middleware()`
**Postconditions:** Middleware runs only on targeted routes

### UC-3 — Developer Configures Rate Limiting

**Actor:** Developer
**Preconditions:** Route needs rate limiting
**Flow:**
1. Choose existing rate limiter (`admin` or `global`) or define new one
2. Apply via `->middleware('throttle:admin')` on route group
3. Verify throttling behavior with test requests
**Postconditions:** Rate limiting active, correct limits applied

---

## 4. Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| FR-MW1 | Core middleware MUST apply to all HTTP requests globally |
| FR-MW2 | `LogContext` MUST attach `request_id` (UUID), `user_id`, `user_role`, `duration_ms` to log context |
| FR-MW3 | `SecurityHeaders` MUST set CSP, HSTS, X-Frame-Options, Referrer-Policy, Permissions-Policy headers |
| FR-MW4 | `SecurityHeaders` MUST inject Vite dev URL into CSP when `APP_ENV=local` |
| FR-MW5 | `AuthThrottleMiddleware` MUST enforce login rate limiting (5 attempts/60s per IP) |
| FR-MW6 | `CheckRoleMiddleware` MUST verify user has required role before route execution |
| FR-MW7 | `SetLocaleMiddleware` MUST set locale from user preference or session |
| FR-MW8 | `ProtectSetupRouteMiddleware` MUST block setup routes after installation is complete |
| FR-MW9 | `RequireSetupAccessMiddleware` MUST require valid setup access token |
| FR-MW10 | Rate limiters MUST be registered in `AppServiceProvider` with named aliases |
| FR-MW11 | Module middleware MUST be registrable via route files without modifying core |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-MW1 | Middleware execution overhead MUST be < 2ms per layer |
| NFR-MW2 | Security headers MUST NOT break Vite hot module replacement in development |
| NFR-MW3 | Rate limit counters MUST use cache driver (not database) for performance |
| NFR-MW4 | `LogContext` MUST NOT fail the request if logging infrastructure is down |

---

## 6. API / Data Contracts

### Middleware Execution Order (Global)

```
Request
  → LogContext (attach request context)
  → SecurityHeaders (set security headers)
  → ValidateCsrfToken (Laravel built-in)
  → Authenticate (Laravel built-in)
  → CheckRoleMiddleware (if route requires role)
  → SetLocaleMiddleware (if route requires locale)
  → Route Handler
```

### Module Middleware (Per Route Group)

| Module | Middleware | Applies To |
|--------|-----------|------------|
| Auth | `AuthThrottleMiddleware` | Login routes |
| Auth | `CheckRoleMiddleware` | All protected routes |
| Settings | `SetLocaleMiddleware` | All settings routes |
| Setup | `ProtectSetupRouteMiddleware` | All setup routes |
| Setup | `RequireSetupAccessMiddleware` | All setup routes |

### Rate Limiters (AppServiceProvider)

| Name | Limit | Use Case |
|------|-------|----------|
| `admin` | 60/min per user | Admin actions (settings, user management) |
| `global` | 120/min per IP | General authenticated routes |

### LogContext Payload

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

### DD-1 — LogContext as First Middleware

**Decision:** `LogContext` is the first middleware in the pipeline.

**Rationale:** Every subsequent middleware and the route handler can rely on request context
being available. Placing it later would mean some operations lack traceability.

**Trade-off:** Adds ~1ms to every request for context assembly. Negligible for the debugging
benefit.

### DD-2 — Security Headers Before Route Handling

**Decision:** `SecurityHeaders` runs before the route handler, not as a response middleware.

**Rationale:** Headers must be set before any response content is generated. Running as
response middleware risks headers being omitted on early returns or exceptions.

**Trade-off:** Headers are set even for 404/403 responses, which is correct behavior.

### DD-3 — Named Rate Limiters Over Inline Configuration

**Decision:** Rate limiters are registered by name in `AppServiceProvider`, not configured
inline in route files.

**Rationale:** Centralizes rate limiting policy. Makes it easy to audit and adjust limits
without modifying individual route files.

**Trade-off:** Adds a layer of indirection. Developers must check `AppServiceProvider` to
understand actual limits.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Routes with appropriate authorization middleware | 100% |
| Security headers present on all responses | 100% |
| Middleware execution overhead per request | < 10ms total |
| Rate-limited endpoints functioning correctly | 100% |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [core-foundation.md](core-foundation.md) | `SecurityHeaders`, `LogContext`, `RequireSetupAccessMiddleware`, `SetLocaleMiddleware` base implementations |
| [rbac-and-authorization.md](rbac-and-authorization.md) | `CheckRoleMiddleware` for route-level role enforcement |

### Build Guide
After implementing this spec, the system has a complete middleware stack: security headers on every response, request logging with duration tracking, setup gating for uninstalled instances, locale resolution, and role-based route protection. The next step is to implement security headers details, which extends the CSP and HSTS policies defined in this pipeline.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [security-headers.md](security-headers.md) | `SecurityHeaders` middleware from this spec applies CSP, HSTS, X-Frame-Options defined in that spec |

---

## Quick References

- `app/Core/Http/Middleware/LogContext.php` — Request context enrichment
- `app/Core/Http/Middleware/SecurityHeaders.php` — Security header injection
- `app/Auth/Login/Http/Middleware/AuthThrottleMiddleware.php` — Login rate limiting
- `app/Auth/Permissions/Http/Middleware/CheckRoleMiddleware.php` — Role-based access
- `app/Settings/Locale/Http/Middleware/SetLocaleMiddleware.php` — Locale switching
- `app/Setup/Installation/Http/Middleware/ProtectSetupRouteMiddleware.php` — Setup protection
- `app/Setup/Installation/Http/Middleware/RequireSetupAccessMiddleware.php` — Setup token
- `app/Providers/AppServiceProvider.php` — Rate limiter registration
- `docs/architecture/livewire-pattern.md` — Livewire authorization patterns
- `docs/specs/security-headers.md` — CSP and header details
