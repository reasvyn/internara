# Security — Infrastructure, Privacy & Compliance

> **Last updated:** 2026-07-22 **Changes:** initial — create infrastructure security reference

## Description

Operational reference for the security infrastructure: network hardening, application security
posture, security headers, rate limiting, PII handling, GDPR compliance, dependency auditing, and
production security checklist.

This document covers **infrastructure and operational** security. For coding conventions (XSS, SQLi,
CSRF, file uploads), see [Coding Conventions §3](../conventions.md#3-security-conventions). For
authentication flow and RBAC, see [RBAC](../foundation/rbac.md). For authorization gates and
policies, see [Policy Pattern](../architecture/policy-pattern.md).

---

## 1. Network Security

### Perimeter

| Layer | Configuration |
| ----- | ------------- |
| HTTPS | Let's Encrypt or Cloudflare — enforced via redirect |
| Firewall | Allow ports 80, 443 only; block all others |
| SSH | Fail2ban with aggressive ban policy; key-only auth |
| Reverse proxy | Nginx or Caddy — TLS termination, rate limiting |

### Headers

All responses include security headers via `SecurityHeaders` middleware (applied globally in the
`web` middleware group):

| Header | Value | Purpose |
| ------ | ----- | ------- |
| `Content-Security-Policy` | Strict allowlist | Blocks inline scripts, untrusted sources |
| `X-Frame-Options` | `DENY` | Prevents clickjacking |
| `X-Content-Type-Options` | `nosniff` | Prevents MIME type sniffing |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limits referrer leakage |
| `Permissions-Policy` | Restrictive | Disables unnecessary browser features |
| `Strict-Transport-Security` | `max-age=31536000` | Forces HTTPS for 1 year |

> **CSP enforcement:** The CSP is enforced, not report-only. Violations break pages. Test
> thoroughly before adding new external resources. Add new domains to the CSP directives in
> `SecurityHeaders` before use.

---

## 2. Application Security Posture

### Authentication Infrastructure

| Feature | Configuration | Reference |
| ------- | ------------- | --------- |
| Login throttling | 5 attempts / 60s per IP | [RBAC §1](../foundation/rbac.md#1-authentication-flow) |
| Forgot password throttle | 3 attempts / 3600s | [System Health §5.6](../foundation/system-health.md#56-authentication) |
| Password reset throttle | 5 attempts / 300s | [System Health §5.6](../foundation/system-health.md#56-authentication) |
| Account recovery throttle | 3 attempts / 300s | [System Health §5.6](../foundation/system-health.md#56-authentication) |
| Global rate limit | 30 requests / min / IP | [conventions §3.7](../conventions.md#37-rate-limiting) |
| Account locking | Auto-lock after 10 failed attempts | [RBAC §1](../foundation/rbac.md#1-authentication-flow) |
| Setup wizard throttle | Configurable via `config/setup.php` | [conventions §3.7](../conventions.md#37-rate-limiting) |

### Session Security

| Setting | Value |
| ------- | ----- |
| Driver | `database` (Tier 1) / `redis` (Tier 2+) |
| Lifetime | 120 minutes |
| Cookie | HTTP-only, SameSite=Lax, Secure in production |
| Regeneration | On login and logout (session fixation prevention) |

Full session reference: [Session](session.md).

### Password Policy

| Rule | Value |
| ---- | ----- |
| Minimum length | 8 characters |
| Hashing | bcrypt (Laravel default) |
| Reset mechanism | Token-based via `password_reset_tokens` table |

---

## 3. Rate Limiting Reference

All rate limiters are defined in `bootstrap/app.php` and referenced by name in route middleware.

| Limiter | Limit | Window | Scope |
| ------- | ----- | ------ | ----- |
| `auth.throttle` | 5 | 60s | Per IP |
| Global | 30 | 60s | Per IP |
| Setup wizard | Configurable | Configurable | Per IP |
| Forgot password | 3 | 3600s | Per email |
| Password reset | 5 | 300s | Per token |
| Account recovery | 3 | 300s | Per email |

---

## 4. PII Handling

### Data Classification

| Field | Classification | Stored In | Masked In Logs |
| ----- | -------------- | --------- | -------------- |
| Email | PII | `users.email` | Yes |
| Phone | PII | `profiles.phone` | Yes |
| NISN | PII | `student_profiles.nisn` | Yes |
| Password | Secret | `users.password` (bcrypt) | Yes |
| Address | PII | `student_profiles.address` | Yes |
| Attendance GPS | Sensitive | `attendances.latitude/longitude` | Yes |
| IP address | PII | `sessions.ip_address` | No (functional) |

### SmartLogger PII Masking

SmartLogger applies PII masking automatically via `PiiMasker` before writing to the activity
channel. The masking is field-aware — it knows which columns contain PII and applies appropriate
redaction.

```php
// Automatic masking in activity log
SmartLogger::activity()->info('User profile updated', ['user_id' => $user->id]);
// email, phone, address are auto-masked in the log entry
```

PII masking reference: [System Observability §PII Masking](../foundation/system-observability.md#pii-masking).

---

## 5. GDPR Compliance

### Data Erasure

- Account deletion requests are processed through the designated erasure workflow
- Deleted user records are logged (append-only) in GDPR deletion logs
- GDPR logs are viewable in **Admin → GDPR Logs**
- Deletion logs are immutable — they cannot be edited or removed

### Deletion Log Format

| Field | Description |
| ----- | ----------- |
| User ID | UUID of the deleted user (anonymized after deletion) |
| Deleted at | Timestamp of deletion |
| Requested by | Who initiated the deletion (self or admin) |
| Reason | Optional reason for deletion |

GDPR reference: [System Observability §GDPR Deletion Logs](../foundation/system-observability.md#gdpr-deletion-logs).

---

## 6. Security Scanning

### Automated Scanners

| Script | Purpose | Command |
| ------ | ------- | ------- |
| `scan_security.py` | XSS, SQLi, CSRF, auth patterns | `python3 scripts/scan_security.py` |
| `scan_violations.py` | Architecture invariant violations (C1-C8, D1-D6) | `python3 scripts/scan_violations.py` |
| `scan_conventions.py` | `declare(strict_types)`, `#[Fillable]`, debug calls | `python3 scripts/scan_conventions.py` |
| `scan_class_contracts.py` | Action/Entity/DTO/Model contract compliance | `python3 scripts/scan_class_contracts.py` |
| `scan_naming.py` | Naming convention violations | `python3 scripts/scan_naming.py` |

### Dependency Auditing

```bash
composer audit          # Check for known vulnerabilities in PHP dependencies
npm audit               # Check for known vulnerabilities in JS dependencies
```

### Static Analysis

```bash
vendor/bin/phpstan analyse --no-progress   # PHPStan level 8
```

---

## 7. Vulnerability Reporting

External security reports are accepted via email (not public issues). Response SLA: 48 hours.
Disclosure window: 90 days.

Full policy: [SECURITY.md](../../SECURITY.md).

---

## 8. Production Security Checklist

| # | Check | Status |
| --- | ----- | ------ |
| 1 | `APP_DEBUG=false` and `APP_ENV=production` | — |
| 2 | `APP_KEY` set to random 32-char base64 string | — |
| 3 | HTTPS enforced at web server / reverse proxy | — |
| 4 | `SecurityHeaders` middleware active (CSP, HSTS, X-Frame-Options) | — |
| 5 | Fail2ban configured for SSH | — |
| 6 | Firewall allows only ports 80, 443 | — |
| 7 | Rate limiting active on all auth endpoints | — |
| 8 | Account lockout enabled (10 failed attempts) | — |
| 9 | Session cookie: HTTP-only, SameSite=Lax, Secure | — |
| 10 | `composer audit` — no known vulnerabilities | — |
| 11 | `npm audit` — no known vulnerabilities (or accepted) | — |
| 12 | PII masking active in SmartLogger | — |
| 13 | Backup automation configured | — |
| 14 | `php artisan system:health` — no FAIL results | — |

---

## Quick References

| Concern | Document |
| ------- | -------- |
| Authentication flow & RBAC | [Foundation — RBAC](../foundation/rbac.md) |
| Authorization gates & policies | [Architecture — Policy Pattern](../architecture/policy-pattern.md) |
| Coding security conventions (XSS, SQLi, CSRF, CSP) | [Conventions §3](../conventions.md#3-security-conventions) |
| Session configuration & security | [Infrastructure — Session](session.md) |
| Security headers middleware | [Conventions §3.5](../conventions.md#35-content-security-policy) |
| Rate limiting configuration | [Conventions §3.7](../conventions.md#37-rate-limiting) |
| SmartLogger & PII masking | [Foundation — System Observability](../foundation/system-observability.md) |
| Account recovery flow | [Foundation — Account Recovery](../foundation/account-recovery.md) |
| Backup & restoration | [Foundation — Backup & Recovery](../foundation/backup-recovery.md) |
| Vulnerability reporting | [SECURITY.md](../../SECURITY.md) |
| Infrastructure security posture | [Infrastructure Overview §9](infrastructure.md#9-security-posture) |
| Troubleshooting auth issues | [Foundation — System Health §5.6](../foundation/system-health.md#56-authentication) |
