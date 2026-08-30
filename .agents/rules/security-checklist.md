# Security Audit Coverage — Full-Sweep Vulnerability Review

## Intent

A dedicated security and privacy audit sweeps the codebase across the categories below — OWASP
Top 10, PII handling, authentication, authorization, session security, rate limiting, secrets
management, and dependency vulnerabilities — and produces **GitHub Issues** with severity, impact,
and fix recommendations. This rule is the umbrella: it defines the audit process, the cross-cutting
controls checked in every sweep, the issue format, and the key rules that govern the auditor. The
per-category depth lives in the sibling rule files.

## Rationale

Security is not a byproduct of feature work. A dedicated audit exists because:

- **Vulnerabilities are cross-cutting.** A single XSS in one Blade file, a missing policy check on
  one mutation, or a leaked secret in a config undermines the whole application regardless of how
  well the rest is built.
- **Automated scans produce false positives** — and false negatives. Every finding must be verified
  manually against the code, or the audit outputs noise and misses real attacks.
- **Findings without structure are unactionable.** An issue with a category, location, severity,
  reproduction, and fix can be triaged and assigned; a vague note cannot.
- **The audit must not fix.** Auditing while fixing conflates two concerns and skips the systematic
  sweep that finds everything first.

## How to Apply — Audit Execution

1. **Run the scanners first (Automation-First):**

   ```bash
   python3 tools/scan_security/cli.py      # XSS, SQLi, mass assignment, auth patterns
   python3 tools/scan_violations/cli.py    # C1-C8, D1-D6 invariants
   ```

2. **Audit each category in order** — see the Coverage Map below for which rule file holds the
   checks. Work through: authentication, authorization, XSS, SQL injection, mass assignment, file
   upload, PII, secrets & configuration, dependencies, then the cross-cutting controls.
3. **Verify every scanner hit manually** — read the code, confirm or dismiss the finding. Automated
   scans are triage, not truth.
4. **Record all findings, even out of scope** — prioritization happens downstream, not during the
   audit.
5. **Check existing GitHub issues before filing duplicates.**
6. **Output:** one GitHub Issue per finding with the Issue Format below.

## Cross-Cutting Controls

Checked in **every** sweep, independent of the category-specific files:

- **CSRF** — all state-changing HTML forms include `@csrf` or use Livewire (which handles CSRF
  automatically). No state-changing route is CSRF-exempt without a justification.
- **Rate limiting** on: login, password reset, recovery slip, setup token, account recovery.
- **Session timeout** configured appropriately for the sensitivity of the application.
- **HTTPS** enforced in production — no cleartext on authenticated paths.

## Issue Format

Each finding is filed with exactly this structure:

| Field | Content |
|-------|---------|
| **Category** | Which audit category (e.g. XSS Prevention) |
| **Location** | File path and line number |
| **Vulnerability** | OWASP category or specific risk |
| **Severity** | Critical / High / Medium / Low |
| **Reproduction** | Steps if applicable |
| **Fix** | Recommendation |

## Key Rules

1. **Verify each finding manually** — automated scans produce false positives.
2. **Record all findings even if out of scope** — prioritization happens downstream.
3. **Do NOT fix during audit** — separate concerns; fixes happen in a follow-up.
4. **Check existing issues before filing duplicates.**
5. **Automation-First** — run `scan_security.py` and `scan_violations.py` before manual checks.

## Coverage Map

| Category | Rule asset |
|----------|-----------|
| Authentication & Authorization | `rules/authentication-authorization.md` |
| XSS Prevention | `rules/xss-and-injection.md` |
| SQL Injection | `rules/xss-and-injection.md` |
| Mass Assignment | `rules/mass-assignment-and-uploads.md` |
| File Upload Security | `rules/mass-assignment-and-uploads.md` |
| PII & Data Protection | `rules/pii-data-protection.md` |
| Secrets & Configuration | `rules/secrets-and-dependencies.md` |
| Dependencies | `rules/secrets-and-dependencies.md` |
| Cross-Cutting (CSRF, rate limiting, session, HTTPS) | This file — §Cross-Cutting Controls |

## Anti-Patterns & Pitfalls

- **Trusting scanner output verbatim** — filing a false positive as a Critical issue erodes trust in
  the audit; verify every hit.
- **Fixing findings mid-audit** — scope creeps, the sweep stalls, and the fix is unverified against
  the remaining categories.
- **Skipping out-of-scope findings** — a leaked secret in the docs or a dev-only route is still a
  finding; record it.
- **Filing duplicates** — same issue, multiple categories; check existing issues and cross-reference
  instead.
- **No reproduction steps** — a finding without a way to confirm it cannot be triaged or verified
  after the fix.

## Verification & Detection

- The audit output (issues) covers all categories in the Coverage Map with no empty rows.
- Every scanner hit was either confirmed into an issue or explicitly dismissed with a reason.
- Each issue carries the full Issue Format — no missing severity, location, or fix.
- `scan_security.py` findings map to issues; no un-issued Critical/High scanner hit remains.
