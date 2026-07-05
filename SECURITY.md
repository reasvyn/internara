# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in Internara, please report it privately. **Do not open a
public issue.**

Send details to **[reasvyn@gmail.com](mailto:reasvyn@gmail.com)** with the subject line
`[INTERNARA SECURITY]` and include:

- Type of vulnerability
- Steps to reproduce
- Affected version(s)
- Any relevant configuration or environment details

You should receive a response within **48 hours**. If you don't, follow up to ensure the message was
received.

## Disclosure

We follow a **90-day disclosure window** — we aim to release a fix within 90 days of receiving a
report. After a fix is released, we will credit the reporter (if desired) in the release notes.

## Scope

The following are **in scope** for security reports:

- Authentication bypass or privilege escalation
- SQL injection, XSS, CSRF, SSRF
- Sensitive data exposure (PII leakage, improper access control)
- Remote code execution
- Cryptographic weaknesses in certificate generation or recovery flows

The following are **out of scope**:

- Reports on unauthenticated endpoints that are intentionally public
- Missing security headers that do not result in a demonstrable vulnerability
- Rate limiting bypasses without demonstrated impact
- Version disclosure without exploitation path

## Supported Versions

| Version | Supported                                              |
| ------- | ------------------------------------------------------ |
| 0.x     | ✅ (latest only; security fixes backported on request) |

## Security Practices

Internara follows these security practices by design:

- **PII isolation** — credentials and personal data live in separate tables
- **Layered auth** — route middleware + Livewire authorization + Policies
- **No mass assignment** — `#[Fillable]` whitelist on every model, never `$request->all()`
- **CSP enforced** — strict Content-Security-Policy header via global middleware
- **File uploads** — through Spatie MediaLibrary only (never `Storage::put()`)
- **Rate limiting** — on auth endpoints, recovery flows, and setup token validation
- **SQL injection prevention** — parameterized queries only; raw SQL forbidden without binding
