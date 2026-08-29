# Six-Phase Protocol — Audit Execution Order and Blockers

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

A QA audit runs **6 phases in order**, each a major quality domain. The phases are the container:
they decide what to scan, in what sequence, what blockers stop forward motion, and what each phase
must output. This rule fixes the protocol itself; the per-domain checks live in the standard coverage
rules (OWASP, ISO 25010, WCAG, PSR, etc.).

```
PHASE 1: Automated Scanning
  → PHASE 2: Security Audit
    → PHASE 3: Quality & Reliability
      → PHASE 4: Standards Compliance
        → PHASE 5: Performance & Efficiency
          → PHASE 6: Report, Issues & Commit
```

## Rationale

Auditing without a sequence produces a survey, not an audit. Order matters because each phase
depends on the previous: you cannot assess security meaningfully before the automated tooling has
surfaced the mechanical defects (building on a broken build is noise), and you cannot write a
trustworthy report before every phase has produced its findings. Enforcing *blockers* — conditions
that halt a phase until resolved — keeps a phase that cannot run from silently passing:

- Running the full protocol against code with a critical known CVE (Phase 1 blocker) would report
  "clean" on a known-compromised dependency.
- Running Phase 2 while the app will not boot (Phase 6-style blocker) audits a ghost.
- A `dd()` in production code (Phase 3 blocker) means every downstream finding is tainted by
  debug-output code that should not exist.

## How to Apply — Phase by Phase

| Phase | Goal | Key checks (covered in depth by) |
|-------|------|----------------------------------|
| **1 — Automated Scanning** | Run all static analysis, dependency audit, and code-style tooling | `rules/dependency-audit.md`, `rules/static-analysis.md`, `rules/psr-standards.md`, dead-code + build |
| **2 — Security Audit** | Evaluate against OWASP Top 10 (2021) and CWE/SANS Top 25 | `rules/owasp-top10.md`, `rules/sans-top25.md`, `rules/cwe-sans.md` |
| **3 — Quality & Reliability** | Error handling, logging, input validation, auth/authz, test coverage, duplication | `rules/error-handling.md`, `rules/logging.md`, `rules/input-validation.md`, `rules/authentication-authorization.md`, `rules/test-coverage.md` |
| **4 — Standards Compliance** | PSR, Laravel best practices, WCAG, session, cryptography | `rules/psr-standards.md`, `rules/laravel-best-practices.md`, `rules/wcag.md`, `rules/session-management.md`, `rules/cryptography.md` |
| **5 — Performance & Efficiency** | Database, caching, memory, frontend | `rules/performance.md` |
| **6 — Report, Issues & Commit** | Consolidate, score, file issues, commit, report | `rules/github-issues-output.md`, `rules/compliance-scorecard.md` |

### Blocker policy

A blocker is a condition that prevents a phase from executing reliably. Fix it before continuing —
**minimal intervention only**, just enough to unblock:

| Phase | Blocker | Action |
|-------|---------|--------|
| 1 | `composer audit` finds a critical CVE | Record as finding; do **NOT** auto-update (out of scope) |
| 1 | PHPStan crashes (OOM) | Reduce memory or level; record inability to complete |
| 1 | Build fails | Record as finding; attempt minimal fix if simple |
| 2 | Critical stored XSS | Record; do NOT fix (audit scope) |
| 2 | Critical SQL injection | Record; **this IS a blocker** — flag for immediate fix |
| 2 | Hardcoded credentials in source | Record; flag for immediate rotation |
| 3 | `dd()`/`dump()` in production code | Record as finding; remove immediately (D2-level issue) |
| 3 | Test suite won't run | Record as blocker; fix if simple |
| 3 | Empty catch blocks swallowing errors | Record as finding |
| 4 | Missing `declare(strict_types=1)` | Record as finding; simple fix |
| 4 | No CSRF protection on forms | Record as finding; may be handled by framework |
| 6 | `gh` CLI not authenticated | Report findings to user directly; skip Issue creation |
| 6 | No findings across all phases | Still create the report and commit the skill — a clean audit is valuable |

### Size-awareness

Classify the audit per `agent-workflow` Size Triage. A full-project QA is **L** — inform the user,
propose a session plan (e.g. by module), and run each phase per session. Never attempt all 6 phases
on the entire codebase in a single pass.

## Examples

A Phase 1 run on this project:

```bash
composer audit 2>&1                                     # dependency vulnerabilities
vendor/bin/phpstan analyse --no-progress --memory-limit=1G 2>&1   # static analysis
vendor/bin/pint --test 2>&1                             # code style
python3 tools/scan_dead_code.py                       # dead code (project scanner first)
npm run build 2>&1                                      # build verification
```

Record, per artifact: advisory IDs (CVEs) and severities for dependencies; error counts by severity
and hottest files for PHPStan; violation counts/types for Pint; dead-code inventory; build status and
warnings.

## Anti-Patterns & Pitfalls

- **Skipping phases that "look fine."** Every phase produces an output record — an empty phase is a
  valid, reportable result; a skipped phase is not.
- **Fixing beyond minimal intervention.** A blocker fix is minimal by definition; expanding into a
  refactor turns the audit into an implementation session and stalls the protocol.
- **Running phases in parallel mentally.** Findings interleave between phases and dedup happens in
  Phase 6 — each phase still produces its own structured output first
  (`rules/github-issues-output.md`).
- **Ignoring the L-size split.** All 6 phases on a whole codebase in one pass exceeds the session; the
  audit degrades into selective sampling.

## Verification & Detection

- The audit report shows phases executed **6/6**, with no skipped phase.
- Every blocker encountered is listed as *resolved* (fix applied) or *recorded as finding* — never
  silently ignored.
- Each phase produced its structured output (JSON shape per phase in the SKILL.md, or the
  consolidated report) before Phase 6 consumed it.