---
name: qa-protocol
description: "SDLC Phase: QUALITY GATE. Independent blind QA audit against global industry standards (OWASP, ISO 25010, CWE/SANS, WCAG, PSR, Laravel best practices). 5-phase protocol producing GitHub Issues and a compliance scorecard. No project-specific rules — purely external benchmarks."
downstream:
  - writing-issues
  - roadmap-planning
  - code-refactoring
  - security-audit
---

# QA Protocol — Independent / Blind Quality Assurance

> **Prerequisite:** None. This skill is INDEPENDENT of project documentation.
> Do NOT load project conventions, architecture docs, or module references before executing.
> This is a blind test against industry standards, not project-specific rules.

## When to Activate

Use this skill for comprehensive quality assurance audits against global industry standards.
Activates during ANALYSIS phase or as a periodic quality gate. Completely independent from
internal audit protocols — this skill evaluates the codebase as if it were an unknown project
being reviewed for the first time.

**Key distinction from `audit-protocol`:**
- `audit-protocol` checks compliance with project-defined rules (C1-C8, D1-D6, etc.)
- `qa-protocol` checks compliance with global industry standards (OWASP, ISO 25010, CWE, etc.)
- This skill does NOT know or care about the project's internal conventions
- Findings are evaluated purely against external benchmarks

## Agent Workflow

This skill follows **6 phases**, each corresponding to a major quality domain. Execute phases
in order. Each phase can produce blockers — if a phase encounters a blocker that prevents
execution, fix it before continuing (minimal intervention only).

```
PHASE 1: Automated Scanning
  → PHASE 2: Security Audit
    → PHASE 3: Quality & Reliability
      → PHASE 4: Standards Compliance
        → PHASE 5: Performance & Efficiency
          → PHASE 6: Report, Issues & Commit
```

---

## Phase 1 — Automated Scanning

**Goal:** Run all available static analysis, dependency audit, and code style tools.
**ISO 25010 Mapping:** Maintainability, Security (partial)
**Time estimate:** 10-20 minutes

### 1.1 Dependency Vulnerability Audit

```bash
composer audit 2>&1
npm audit 2>&1
```

**Rules:** See `rules/dependency-audit.md`

**What to record:**
- Any advisory IDs (CVE numbers)
- Severity (critical/high/medium/low)
- Package name and affected version
- Whether a fix is available

### 1.2 Static Analysis (PHPStan/Larastan)

```bash
vendor/bin/phpstan analyse --no-progress --memory-limit=1G 2>&1
```

If the project has a custom phpstan.neon, use its configured level. Otherwise, default to level 5.

**Rules:** See `rules/static-analysis.md`

**What to record:**
- Number of errors by severity (error vs warning vs tip)
- Error categories (type safety, undefined methods, dead code, etc.)
- Files with most errors

### 1.3 Code Style Check

```bash
vendor/bin/pint --test 2>&1
```

**Rules:** See `rules/psr-standards.md`

**What to record:**
- Number of style violations
- Common violation types
- Files with most violations

### 1.4 Dead Code Detection

Search for:
- Unused PHP classes (defined but never imported/instantiated)
- Unused methods (defined but never called outside their own class)
- Orphan event classes (defined but no listener registered)
- Unused migration columns (defined in migration but never referenced in code)
- Empty directories (skeleton leftovers)
- Unused composer packages (installed but never used in code)

```bash
# Quick checks
grep -rn "class.*Exception" app/ | head -20  # Check for unused exceptions
```

### 1.5 Build Verification

```bash
npm run build 2>&1
```

**What to record:**
- Build success/failure
- Bundle size
- Warnings

### Blockers for Phase 1

| Blocker | Action |
|---------|--------|
| `composer audit` finds critical CVE | Record as finding; do NOT auto-update (out of scope) |
| PHPStan crashes (OOM) | Reduce memory or level; record inability to complete |
| Build fails | Record as finding; attempt minimal fix if simple |

### Phase 1 Output

```json
{
  "phase": 1,
  "dependency_vulnerabilities": [...],
  "static_analysis_errors": [...],
  "code_style_violations": [...],
  "dead_code": [...],
  "build_status": "pass|fail",
  "blockers": [...]
}
```

---

## Phase 2 — Security Audit

**Goal:** Evaluate the application against OWASP Top 10 (2021) and CWE/SANS Top 25.
**ISO 25010 Mapping:** Security
**Time estimate:** 30-60 minutes

### OWASP Top 10 (2021) Checklist

Execute each category independently. See `rules/owasp-top10.md` for detailed check procedures.

#### A01: Broken Access Control
- [ ] Every route has middleware-based authorization
- [ ] Every mutation has server-side authorization check
- [ ] Insecure Direct Object Reference (IDOR) — can user A access user B's resources?
- [ ] CORS configuration is restrictive
- [ ] Directory traversal prevention
- [ ] File upload restrictions enforced server-side

#### A02: Cryptographic Failures
- [ ] No plaintext passwords or secrets in code/config
- [ ] Passwords hashed with bcrypt/argon2 (not md5/sha1)
- [ ] Sensitive data encrypted at rest (PII, tokens)
- [ ] TLS enforced for all connections
- [ ] No sensitive data in URLs (tokens in query params)
- [ ] Secrets not in version control (.env in .gitignore)

#### A03: Injection
- [ ] SQL injection — no raw SQL without parameterized binding
- [ ] XSS — all output escaped, `{!! !!}` sanitized
- [ ] Command injection — no `exec()`, `system()`, `passthru()` with user input
- [ ] LDAP injection (if applicable)
- [ ] NoSQL injection (if applicable)
- [ ] Template injection (if using Blade without user-controlled template names)

#### A04: Insecure Design
- [ ] No business logic bypasses (e.g., price manipulation, privilege escalation)
- [ ] Rate limiting on sensitive endpoints (login, registration, password reset)
- [ ] Resource consumption limits (file upload size, request size)
- [ ] Separation of duties enforced
- [ ] Trust boundaries respected (user input never trusted)

#### A05: Security Misconfiguration
- [ ] APP_DEBUG=false in production
- [ ] .env not web-accessible
- [ ] Default credentials changed
- [ ] Unnecessary features disabled (debug toolbar, excepts page)
- [ ] Security headers present (CSP, X-Frame-Options, X-Content-Type-Options)
- [ ] Error pages don't leak stack traces or sensitive info

#### A06: Vulnerable and Outdated Components
- [ ] No known CVEs in dependencies (from Phase 1)
- [ ] Dependencies not abandoned/unmaintained
- [ ] Minimum version constraints appropriate

#### A07: Identification and Authentication Failures
- [ ] Brute force protection on login
- [ ] Password complexity requirements enforced
- [ ] Session fixation prevention
- [ ] Multi-factor authentication available (if applicable)
- [ ] Credential stuffing protection

#### A08: Software and Data Integrity Failures
- [ ] Deserialization of untrusted data avoided
- [ ] CI/CD pipeline integrity
- [ ] Auto-update mechanisms verified
- [ ] Signed packages/dependencies
- [ ] No `eval()`, `unserialize()` with user input

#### A09: Security Logging and Monitoring Failures
- [ ] Security events logged (login failures, access denials, mutations)
- [ ] Logs don't contain sensitive data (passwords, tokens, PII)
- [ ] Log injection prevention
- [ ] Audit trail for administrative actions

#### A10: Server-Side Request Forgery (SSRF)
- [ ] No user-controlled URLs fetched server-side without validation
- [ ] If URL fetching exists, allowlist of permitted domains
- [ ] Internal network access restricted from user input

### CWE/SANS Top 25 Cross-Reference

See `rules/sans-top25.md` and `rules/cwe-sans.md` for the specific CWE IDs to check.
Focus on CWEs that map to Laravel/PHP:

| CWE | Name | Laravel Relevance |
|-----|------|-------------------|
| CWE-79 | XSS | Blade `{{ }}` vs `{!! !!}` |
| CWE-89 | SQL Injection | Raw queries, DB::raw |
| CWE-78 | OS Command Injection | exec, system, passthru |
| CWE-22 | Path Traversal | Storage::get, file_get_contents with user path |
| CWE-287 | Improper Authentication | Session fixation, weak password policy |
| CWE-862 | Missing Authorization | Routes without middleware, missing Policy checks |
| CWE-798 | Hard-coded Credentials | API keys, passwords in source code |
| CWE-502 | Deserialization | unserialize() with user data |
| CWE-200 | Information Exposure | Stack traces, debug info to users |
| CWE-352 | CSRF | Missing CSRF protection on state-changing requests |
| CWE-434 | Unrestricted Upload | File upload without type/size validation |
| CWE-918 | SSRF | User-controlled URLs fetched server-side |

### Blockers for Phase 2

| Blocker | Action |
|---------|--------|
| Critical XSS (stored) | Record; do NOT fix (audit scope) |
| Critical SQL injection | Record; this IS a blocker — flag for immediate fix |
| Hardcoded credentials in source | Record; flag for immediate rotation |

### Phase 2 Output

```json
{
  "phase": 2,
  "owasp_findings": [
    {
      "category": "A01",
      "title": "...",
      "severity": "critical|high|medium|low",
      "location": "file:line",
      "evidence": "...",
      "cwe": "CWE-xxx"
    }
  ],
  "cwe_findings": [...],
  "blockers": [...]
}
```

---

## Phase 3 — Quality & Reliability

**Goal:** Evaluate code quality, error handling, logging, and test coverage.
**ISO 25010 Mapping:** Reliability, Maintainability, Functional Suitability
**Time estimate:** 30-60 minutes

### 3.1 Error Handling

See `rules/error-handling.md`

- [ ] No exceptions silently swallowed (empty catch blocks)
- [ ] No raw exceptions shown to users (no stack traces in production)
- [ ] Consistent exception hierarchy
- [ ] Graceful degradation for non-critical failures
- [ ] Database transaction usage for multi-step mutations
- [ ] No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in committed code

### 3.2 Logging

See `rules/logging.md`

- [ ] No PII in log files (passwords, tokens, email addresses, national IDs)
- [ ] Log levels appropriate (not logging debug in production)
- [ ] Structured logging (consistent format)
- [ ] Sensitive data masked/redacted before logging
- [ ] No user input directly interpolated into log messages (log injection risk)

### 3.3 Input Validation

See `rules/input-validation.md`

- [ ] All user input validated before processing
- [ ] Server-side validation (not just client-side)
- [ ] Type coercion handled safely
- [ ] Array inputs validated (not just scalar)
- [ ] File uploads validated (type, size, content)
- [ ] Date/time inputs validated
- [ ] No `$_GET`, `$_POST`, `$_REQUEST` direct usage

### 3.4 Authentication & Authorization

See `rules/authentication-authorization.md`

- [ ] Authentication middleware on protected routes
- [ ] Authorization checked on every mutation
- [ ] Role-based access properly implemented
- [ ] Password reset flow secure
- [ ] Account lockout after failed attempts
- [ ] Session invalidation on logout

### 3.5 Test Coverage

See `rules/test-coverage.md`

- [ ] Critical business logic has tests
- [ ] Edge cases tested (empty inputs, boundary values)
- [ ] Error paths tested (what happens when things fail)
- [ ] No test pollution (tests don't depend on each other)
- [ ] Database seeding in tests uses factories (not hardcoded data)

### 3.6 Code Duplication

- [ ] No significant code duplication across modules
- [ ] Copy-paste patterns identified
- [ ] Shared logic extracted to base classes or services

### Blockers for Phase 3

| Blocker | Action |
|---------|--------|
| `dd()`/`dump()` in production code | Record as finding; remove immediately (D2-level issue) |
| Test suite won't run | Record as blocker; fix if simple |
| Empty catch blocks swallowing errors | Record as finding |

### Phase 3 Output

```json
{
  "phase": 3,
  "error_handling_findings": [...],
  "logging_findings": [...],
  "validation_findings": [...],
  "auth_findings": [...],
  "test_findings": [...],
  "duplication_findings": [...],
  "blockers": [...]
}
```

---

## Phase 4 — Standards Compliance

**Goal:** Evaluate adherence to PSR standards, WCAG accessibility, and Laravel best practices.
**ISO 25010 Mapping:** Maintainability, Usability, Portability
**Time estimate:** 20-40 minutes

### 4.1 PHP Standards (PSR)

See `rules/psr-standards.md`

- [ ] PSR-1: Basic Coding Standard (namespace, class naming, side effects)
- [ ] PSR-4: Autoloading (directory structure matches namespace)
- [ ] PSR-12: Extended Coding Style (formatting, visibility, declare strict types)

### 4.2 Laravel Best Practices

See `rules/laravel-best-practices.md`

- [ ] No business logic in controllers
- [ ] No Eloquent queries in Blade views
- [ ] Form Requests used for complex validation
- [ ] Resource/Collection classes for API responses
- [ ] Events used for cross-module side effects
- [ ] Service classes for infrastructure (not domain logic)
- [ ] No use of `DB::table()` when Eloquent suffices
- [ ] Proper use of Laravel collections vs raw arrays
- [ ] Service provider registration for custom bindings

### 4.3 WCAG Accessibility

See `rules/wcag.md`

- [ ] All images have `alt` attributes
- [ ] Form inputs have associated `<label>` elements
- [ ] Color is not the sole means of conveying information
- [ ] Keyboard navigation works (tab order, focus visible)
- [ ] ARIA attributes used where needed (modals, alerts, navigation)
- [ ] Heading hierarchy is logical (h1 > h2 > h3, no skipping)
- [ ] Link text is descriptive (not "click here")
- [ ] Error messages are associated with form fields
- [ ] Sufficient color contrast (WCAG AA: 4.5:1 text, 3:1 large text)

### 4.4 Session Management

See `rules/session-management.md`

- [ ] Sessions use secure cookies (HttpOnly, Secure, SameSite)
- [ ] Session timeout configured
- [ ] Session regeneration after login
- [ ] Session invalidated on logout
- [ ] Session fixation prevention

### 4.5 Cryptography

See `rules/cryptography.md`

- [ ] Passwords hashed with password_hash() / Hash::make()
- [ ] No custom encryption implementations
- [ ] Random values generated with random_bytes() / Str::random()
- [ ] No mt_rand() for security-sensitive randomness
- [ ] Encryption keys not hardcoded

### Blockers for Phase 4

| Blocker | Action |
|---------|--------|
| Missing `declare(strict_types=1)` | Record as finding; simple fix |
| No CSRF protection on forms | Record as finding; may be handled by framework |

### Phase 4 Output

```json
{
  "phase": 4,
  "psr_findings": [...],
  "laravel_findings": [...],
  "wcag_findings": [...],
  "session_findings": [...],
  "crypto_findings": [...],
  "blockers": [...]
}
```

---

## Phase 5 — Performance & Efficiency

**Goal:** Evaluate application performance characteristics.
**ISO 25010 Mapping:** Performance Efficiency
**Time estimate:** 15-30 minutes

### 5.1 Database Performance

See `rules/performance.md`

- [ ] N+1 queries in loops (Blade @foreach, collection operations)
- [ ] Missing eager loading on relationship access
- [ ] Missing database indexes on frequently queried columns
- [ ] Unbounded queries (no limit/pagination)
- [ ] SELECT * usage (fetching unnecessary columns)
- [ ] Missing foreign key constraints
- [ ] Large dataset handling (no pagination on potentially large lists)

### 5.2 Caching

- [ ] Expensive queries cached with appropriate TTL
- [ ] Cache keys are unique and scoped
- [ ] Cache invalidation implemented
- [ ] No stale cache serving
- [ ] Cache stampsede prevention

### 5.3 Memory & Resources

- [ ] No memory leaks in long-running processes
- [ ] File handles closed properly
- [ ] Large collections processed in chunks
- [ ] No synchronous processing of async-capable tasks
- [ ] Queue used for heavy operations (email, PDF generation, reports)

### 5.4 Frontend Performance

- [ ] CSS/JS bundled and minified
- [ ] Images optimized
- [ ] No render-blocking resources
- [ ] Lazy loading for below-fold content
- [ ] No unused CSS/JS shipped to browser

### Phase 5 Output

```json
{
  "phase": 5,
  "database_findings": [...],
  "caching_findings": [...],
  "resource_findings": [...],
  "frontend_findings": [...],
  "blockers": [...]
}
```

---

## Phase 6 — Report, Issues & Commit

**Goal:** Consolidate all findings, create GitHub Issues, commit changes, and report to user.
**Time estimate:** 15-30 minutes

### 6.1 Consolidate Findings

Merge all phase outputs into a single deduplicated findings list:

1. **Deduplicate** — If the same issue appears in multiple phases, keep the highest-severity instance and note the other phases where it appeared
2. **Cross-reference with `audit-protocol`** — Check if any QA findings overlap with project-internal audit findings (from `audit-protocol` skill). Note overlaps but file independently — the QA perspective may be different
3. **Assign final severity** using CVSS mapping (see `rules/owasp-top10.md` §Scoring)

### 6.2 Create GitHub Issues

For each finding, create a GitHub Issue using the `writing-issues` skill template format.

**Issue structure per finding:**
- **Title:** `[QA] {severity_emoji} {concise_title}`
- **Labels:** `qa-audit`, `security` (if applicable), severity label (`critical`/`high`/`medium`/`low`)
- **Body:**
  - Summary
  - Affected standard (OWASP A01, CWE-79, PSR-12, WCAG 2.1.1, etc.)
  - Evidence (file path, line number, code snippet)
  - Recommended fix direction (not implementation)
  - Overlap note (if also found by `audit-protocol`)

**Batch creation:** Use `gh issue create` for each finding. If many findings, group related ones into a single issue (e.g., "All XSS findings in Journals module").

### 6.3 Commit Skill Changes

Commit the skill file and any rules files that were created or updated during this session:

```bash
git add .agents/skills/qa-protocol/
git commit -m "docs(qa-protocol): add QA protocol skill with 19 rules files

- 5-phase blind audit protocol against global industry standards
- Rules: OWASP, CWE/SANS, ISO 25010, WCAG, PSR, Laravel, crypto, etc.
- Phase 6: consolidated reporting, GitHub Issues creation, user summary"
```

### 6.4 User Report

Deliver a final summary to the user:

```markdown
# QA Protocol — Audit Complete

## Summary
- Phases executed: 6/6
- Total findings: X (Critical: X, High: X, Medium: X, Low: X)
- GitHub Issues created: X
- Overlaps with `audit-protocol`: X findings

## Compliance Scorecard

| Standard | Score | Notes |
|----------|-------|-------|
| OWASP Top 10 | X/10 categories clean | ... |
| CWE/SANS Top 25 | X/25 CWEs absent | ... |
| ISO 25010 | X/8 characteristics met | ... |
| PSR-1/4/12 | Pass/Fail | ... |
| WCAG 2.1 AA | X/11 criteria met | ... |
| Laravel Best Practices | X/Y checks pass | ... |

## Top Critical/High Findings
1. [Issue #XXX] Title — Standard — Severity
2. ...

## Next Steps
- Triage findings and assign to roadmap
- Critical/High findings should be addressed before next release
- Low findings are tracked as technical debt
```

### Blockers for Phase 6

| Blocker | Action |
|---------|--------|
| `gh` CLI not authenticated | Report findings to user directly; skip Issue creation |
| No findings across all phases | Still create the report and commit the skill — a clean audit is valuable |

### Phase 6 Output

```json
{
  "phase": 6,
  "total_findings": 0,
  "by_severity": { "critical": 0, "high": 0, "medium": 0, "low": 0 },
  "issues_created": [],
  "overlaps_with_audit_protocol": [],
  "commit_sha": "..."
}
```

---

## Final Report Structure

After all 6 phases, produce a consolidated report:

```markdown
# Quality Assurance Report

## Executive Summary
- Total findings: X (Critical: X, High: X, Medium: X, Low: X)
- Phases completed: 6/6
- Blockers encountered: X (all resolved)

## Findings by Phase

### Phase 1: Automated Scanning
...

### Phase 2: Security Audit
...

### Phase 3: Quality & Reliability
...

### Phase 4: Standards Compliance
...

### Phase 5: Performance & Efficiency
...

### Phase 6: Report, Issues & Commit
- GitHub Issues created: X
- Skill committed: yes/no
- Overlaps with audit-protocol: X findings

## Findings by Severity

### Critical (fix immediately)
...

### High (fix before release)
...

### Medium (fix in next cycle)
...

### Low (technical debt)
...

## Compliance Scorecard

| Standard | Score | Notes |
|----------|-------|-------|
| OWASP Top 10 | X/10 categories clean | ... |
| CWE/SANS Top 25 | X/25 CWEs absent | ... |
| ISO 25010 | X/8 characteristics met | ... |
| PSR-1/4/12 | Pass/Fail | ... |
| WCAG 2.1 AA | X/11 criteria met | ... |
| Laravel Best Practices | X/Y checks pass | ... |
```

## Issue Writing

Each finding is written as a GitHub Issue using the `writing-issues` template format.
Findings that overlap with project-internal audit findings (from `audit-protocol`) should
note this overlap but still be filed independently — the QA perspective may be different.

## Key Rules

1. **Blind execution** — Do NOT load project documentation before or during execution
2. **External standards only** — All findings reference external standards (OWASP, CWE, PSR, etc.)
3. **Evidence-based** — Every finding includes file path, line number, and concrete evidence
4. **Severity follows CVSS** — Use Common Vulnerability Scoring System for security findings
5. **No fixes during audit** — Record findings, create issues; fixes happen downstream
6. **Blocker exception** — If a finding actively prevents the audit from running (e.g., app won't boot), fix minimally first
7. **Comprehensive scope** — Check every module, every route, every model — not just the changed code
8. **Independent of project rules** — C1-C8, D1-D6, etc. are NOT part of this audit; this audit uses only global standards
9. **Create Issues and commit** — Every audit must end with GitHub Issues created for each finding, skill files committed, and a summary report delivered to the user
10. **Overlap transparency** — When a QA finding overlaps with an `audit-protocol` finding, note the overlap in the Issue body but still file independently

## References

| Standard | Source |
|----------|--------|
| OWASP Top 10 (2021) | https://owasp.org/Top10/ |
| CWE/SANS Top 25 (2024) | https://cwe.mitre.org/top25/ |
| ISO 25010 | ISO/IEC 25010:2023 |
| WCAG 2.1 | https://www.w3.org/TR/WCAG21/ |
| PSR-1 | https://www.php-fig.org/psr/psr-1/ |
| PSR-4 | https://www.php-fig.org/psr/psr-4/ |
| PSR-12 | https://www.php-fig.org/psr/psr-12/ |
| CVSS v3.1 | https://www.first.org/cvss/v3.1/specification-document |
| Laravel Security | https://laravel.com/docs/master/security |
| PHP Security | https://www.php.net/security/ |
