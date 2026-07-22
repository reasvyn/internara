---
name: security-audit
description: "SDLC Phase: ANALYSIS. Dedicated security and privacy audit — OWASP Top 10, PII handling, authentication, authorization, session security, rate limiting, secrets management, and dependency vulnerabilities."
upstream:
  - audit-protocol
downstream:
  - roadmap-planning
  - code-refactoring
---

# Security Audit

> **Prerequisite:** Load `context-awareness` for project orientation. Running `audit-protocol` first
> provides the baseline code quality audit.

## When to Activate

Use this skill for a dedicated security and privacy audit. Covers OWASP Top 10, PII handling,
authentication, authorization, session security, rate limiting, secrets management, and dependency
vulnerabilities.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Security Audit Execution

- Audit authentication: password hashing, rate limiting, recovery flows
- Audit authorization: Policy methods, super admin bypass, permissions config
- Audit XSS: Blade escaping, {!! !!} occurrences, CSP headers
- Audit SQL injection: parameterized binding, raw SQL check
- Audit mass assignment: #[Fillable], no $request->all()
- Audit PII: data isolation, log masking, GDPR deletion path
- Output: GitHub Issues with security vulnerability reports including severity, impact, and fix
  recommendations

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of security findings by category
    - Severity distribution (critical/high/medium/low)
    - Vulnerabilities confirmed closed
- Feeds into: roadmap-planning (prioritize security fixes), code-refactoring (fix vulnerabilities)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                                  |
| -------------- | ---------------------------------------------------------------------- |
| **Upstream**   | `audit-protocol` (baseline audit)                                      |
| **This skill** | **ANALYSIS** — security-specific                                       |
| **Downstream** | `roadmap-planning` (prioritize fixes), `code-refactoring` (fix issues) |

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
- CSP enforced via `SecurityHeaders` middleware
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

- ALL uploads go through Spatie MediaLibrary
- MIME type validated server-side (not just extension)
- Filenames sanitized with `Str::slug()`
- File size limits defined per collection

### PII & Data Protection

- User profiles stored in separate table from credentials
- Check `app/Core/Support/PiiMasker.php` — PII masking in logs
- Activity log does not store raw PII
- GDPR deletion path exists (`gdpr_deletion_logs` table)

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
- Rate limiting on: login, password reset, recovery slip, setup token
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
