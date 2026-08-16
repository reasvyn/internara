---
name: security-audit
description: "SDLC Phase: ANALYSIS. Dedicated security and privacy audit — OWASP Top 10, PII handling, authentication, authorization, session security, rate limiting, secrets management, and dependency vulnerabilities."
downstream:
  - roadmap-planning
  - code-refactoring
---

# Security Audit

> **Prerequisite:** Load `context-awareness` for project orientation. Running `arch-guard` first
> provides the baseline code quality audit.

## When to Activate

Use this skill for a dedicated security and privacy audit. Covers OWASP Top 10, PII handling,
authentication, authorization, session security, rate limiting, secrets management, and dependency
vulnerabilities.

## Workflow

Follow the `agent-workflow` skill for the canonical 9-step pipeline / 4-phase model: spec-first
doctrine (verify each audited behavior maps to a **governing spec** FR/NFR/UC ID — a finding with
no requirement is a spec gap), **Size Triage** (S/M/L session splitting — a full-project security
audit is L, split by module or category), verification strategy, and commit format. This skill adds
the audit categories, issue format, and key rules below — nothing else.

### Execute — Security Audit Execution

- Run `python3 scripts/scan_security.py` first (Automation-First) — XSS, SQLi, mass assignment, auth
- Audit authentication: password hashing, rate limiting, recovery flows
- Audit authorization: Policy methods, super admin bypass, permissions config
- Audit XSS: Blade escaping, `{!! !!}` occurrences, CSP headers
- Audit SQL injection: parameterized binding, raw SQL check
- Audit mass assignment: `#[Fillable]`, no `$request->all()`
- Audit PII: data isolation, log masking, GDPR deletion path
- Output: GitHub Issues with security vulnerability reports including severity, impact, and fix
  recommendations

## Audit Categories

### Authentication

- Password hashing uses bcrypt/argon2 via Laravel defaults
- Login rate limiting applied (check `bootstrap/app.php`)
- Account recovery rate limited (recovery slip, password reset)
- Session management follows Laravel best practices
- MFA readiness (future)

### Authorization

- Super admin bypass via `Gate::before` — verify it exists and is not removable
- Policy methods return boolean — check every method
- No inline `Gate::authorize()` bypassing Policy layer
- Permissions defined in `config/permission.php` — no magic strings in code
- Check that 5 flat roles are enforced (no role inheritance)

### XSS Prevention

- All Blade output uses `{{ }}` (double curly braces) — escaped
- Every `{!! !!}` (unescaped) has an inline justification comment
- No inline `<script>` tags — everything uses Alpine.js
- CSP enforced via `SecurityHeaders` middleware; no bypass without justification
- Check CSP allows only necessary external resources

### SQL Injection

- No `whereRaw()` / `DB::raw()` without parameterized binding
- No string concatenation in query builder
- `where('column', $value)` used over `whereRaw("column = '$value'")`

### Mass Assignment

- Every model uses `#[Fillable]` attribute — not `$fillable` or `$guarded`
- No `Model::create($request->all())` anywhere
- No `Model::create($this->all())` in Livewire

### File Upload Security

- ALL uploads go through Spatie MediaLibrary (never `Storage::put()`)
- MIME type validated server-side (not just extension)
- Filenames sanitized with `Str::slug()`
- File size limits defined per collection

### PII & Data Protection

- User profiles stored in separate table from credentials
- Check `app/Core/Support/PiiMasker.php` — PII masking in logs
- Activity log does not store raw PII
- GDPR deletion path exists (`gdpr_deletion_logs` table)
- PII masked in logs via `SmartLogger::withPiiMasking()`

### Secrets & Configuration

- No hardcoded secrets in code or config files
- `.env` excluded from version control (check `.gitignore`)
- APP_KEY must be unique per installation
- Database credentials in `.env` only

### Dependencies

- Check `composer.json` for known vulnerabilities
- Verify package versions are current
- Check `dependabot.yml` for automated scanning

### Cross-Cutting

- CSRF: all state-changing HTML forms include `@csrf` or use Livewire
- Rate limiting on: login, password reset, recovery slip, setup token, account recovery
- Session timeout configured appropriately
- HTTPS enforced in production

## Issue Format

Each finding should include:

- **Category:** Which audit category
- **Location:** File path and line number
- **Vulnerability:** OWASP category or specific risk
- **Severity:** Critical / High / Medium / Low
- **Reproduction:** Steps if applicable
- **Fix:** Recommendation

## Key Rules

1. Verify each finding manually — automated scans produce false positives
2. Record all findings even if out of scope — prioritization happens downstream
3. Do NOT fix during audit — separate concerns
4. Check existing issues before filing duplicates
5. Automation-First — run `scan_security.py` and `scan_violations.py` before manual checks

## References

| Topic                 | Doc                                            |
| --------------------- | ---------------------------------------------- |
| Security conventions  | `docs/conventions.md` (§3)                     |
| RBAC & authentication | `docs/foundation/rbac.md`                      |
| Account recovery      | `docs/foundation/account-recovery.md`          |
| Exception hierarchy   | `docs/architecture/exception-pattern.md`       |
| CSP & middleware      | `app/Core/Http/Middleware/SecurityHeaders.php` |
| File upload security  | `docs/infrastructure/media-library.md`         |
| Session configuration | `docs/infrastructure/session.md`               |
| Rate limiting         | `bootstrap/app.php`                            |
